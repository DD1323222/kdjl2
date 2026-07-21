<?php

require_once('../config/config.game.php');
$iosPayConfig = dirname(dirname(__FILE__)).'/config/config.alipay.php';
if(!is_file($iosPayConfig))
{
	echo 'pay_error:config';
	die();
}
require_once($iosPayConfig);

$receipt = (isset($_REQUEST['receipt']) && !is_array($_REQUEST['receipt'])) ? $_REQUEST['receipt'] : '';
$product_id = (isset($_REQUEST['product_id']) && !is_array($_REQUEST['product_id'])) ? $_REQUEST['product_id'] : '';
$produts = isset($backObj['apple']) ? $backObj['apple'] : array();
//$user_id = intval($_REQUEST['user_id']);
$user_id = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($user_id < 1){
	echo "not_find user_id";
	die();
}
if($receipt == '' || $product_id == ''){
	echo "pay_error:param";
	die();
}
if(strlen($receipt) > 1048576 || strlen($product_id) > 32){
	echo "pay_error:param";
	die();
}

//log_result("user_id={$user_id}");
//log_result("product_id={$product_id}");
//log_result("receipt={$receipt}");

//调用验证
$iosPayTransactionActive = false;
$iosPayLockNameSql = '';
register_shutdown_function('iosPayShutdown');
purchaseVerification($user_id,$receipt,$product_id,$produts);

function iosPayReleaseLock()
{
	global $_pm, $iosPayLockNameSql;
	if($iosPayLockNameSql !== '')
	{
		$_pm['mysql']->getOneRecord("SELECT RELEASE_LOCK('{$iosPayLockNameSql}')");
		$iosPayLockNameSql = '';
	}
}

function iosPayAbort($message)
{
	global $_pm, $iosPayTransactionActive;
	if($iosPayTransactionActive)
	{
		$_pm['mysql']->query('ROLLBACK');
		$iosPayTransactionActive = false;
	}
	iosPayReleaseLock();
	echo $message;
	die();
}

function iosPayShutdown()
{
	global $_pm, $iosPayTransactionActive;
	if($iosPayTransactionActive && isset($_pm['mysql']))
	{
		$_pm['mysql']->query('ROLLBACK');
		$iosPayTransactionActive = false;
	}
	iosPayReleaseLock();
}

function iosPayLogFailure($logId, $message)
{
	global $_pm;
	$logId = intval($logId);
	if($logId > 0)
	{
		$errorSql = $_pm['mysql']->escape(substr($message, 0, 1000));
		$_pm['mysql']->query("UPDATE t_shop_log SET error_detail='{$errorSql}' WHERE id={$logId} AND trade=0");
	}
	echo 'pay_error:'.$message;
	die();
}

function iosPayReceiptLogValue($receipt)
{
	if(strlen($receipt) <= 4096) return $receipt;
	$prefix = 'sha256:'.hash('sha256', $receipt).';truncated:';
	return $prefix.substr($receipt, 0, 4096-strlen($prefix));
}

//支付验证
function purchaseVerification($user_id,$receipt,$product_id,$produts){
	global $_pm, $iosPayTransactionActive, $iosPayLockNameSql;
	$productArr = productFromat($produts);

	if(!isset($productArr[$product_id])){
		echo "not find proudct";
		die();
	}
	//写购买记录日志
	$buy_time = time();
	$productIdSql = $_pm['mysql']->escape($product_id);
	$receiptSql = $_pm['mysql']->escape(iosPayReceiptLogValue($receipt));
	$sql = "INSERT INTO t_shop_log(uid,product_id,buy_time,receipt)
			VALUES('{$user_id}','{$productIdSql}','{$buy_time}','{$receiptSql}')";
	if(!$_pm['mysql']->query($sql) || mysql_affected_rows($_pm['mysql']->getConn()) != 1)
	{
		echo "pay_error:log";
		die();
	}
	$logId = $_pm['mysql']->last_id();
	$VerifResult = verifyAppleReceipt($receipt, $product_id);
	if(isset($VerifResult['err'])){
		iosPayLogFailure($logId, $VerifResult['err']);
	}
	if(!isset($VerifResult['product_id']) || !isset($VerifResult['transaction_id'])){
		iosPayLogFailure($logId, 'receipt');
	}
	if($product_id != $VerifResult['product_id']){//防止破解外挂特别加的判断
		iosPayLogFailure($logId, 'product_mismatch');
	}
	$transaction_id = strval($VerifResult['transaction_id']);
	if($transaction_id === '' || strlen($transaction_id) > 32)
	{
		iosPayLogFailure($logId, 'transaction_id');
	}
	$transactionIdSql = $_pm['mysql']->escape($transaction_id);
	$iosPayLockNameSql = $_pm['mysql']->escape('ios_pay_'.md5($transaction_id));
	$lockRow = $_pm['mysql']->getOneRecord("SELECT GET_LOCK('{$iosPayLockNameSql}',10) AS locked");
	if(!is_array($lockRow) || intval($lockRow['locked']) != 1)
	{
		$iosPayLockNameSql = '';
		iosPayLogFailure($logId, 'busy');
	}
	if(!$_pm['mysql']->query('START TRANSACTION'))
	{
		iosPayAbort("pay_error:db");
	}
	$iosPayTransactionActive = true;
	$sql = "SELECT id FROM t_shop_log WHERE transaction_id='{$transactionIdSql}' and trade=1 LIMIT 1 FOR UPDATE";
	$rs = $_pm['mysql']->getOneRecord($sql);
	if($rs){
		$sql = "UPDATE t_shop_log SET error_detail='repeat_submitted' WHERE id='{$logId}'";
		if(!$_pm['mysql']->query($sql) || !$_pm['mysql']->query('COMMIT')) iosPayAbort("pay_error:db");
		$iosPayTransactionActive = false;
		iosPayReleaseLock();
		echo "pay_ok1";
		die();
	}

	//发充值的元宝
	$unitPrice = floatval($productArr[$product_id]['price']);
	$quantity = isset($VerifResult['quantity']) ? intval($VerifResult['quantity']) : 1;
	$unitYb = intval(round($unitPrice * 30));
	$buy_number = kdjlSafePositiveProduct($unitYb, $quantity, 8388607);
	$totalPrice = $unitPrice * $quantity;
	if($unitPrice <= 0 || $quantity < 1 || $quantity > 100 || $buy_number === false || $totalPrice > 99999999.99)
	{
		iosPayAbort("pay_error:product");
	}
	$priceSql = number_format($totalPrice, 2, '.', '');
	$out_trade_no = $VerifResult['transaction_id'];
	$subject = "{$priceSql}RMB购买{$buy_number}元宝";
	$subjectSql = $_pm['mysql']->escape($subject);
	$ybOrderId = strlen($transaction_id) <= 25 ? $transaction_id : 'i'.substr(md5($transaction_id), 0, 24);
	$ybOrderIdSql = $_pm['mysql']->escape($ybOrderId);
	$ybExtra = '';
	if($_pm['mysql']->columnExists('yb', 'user_id')) $ybExtra .= ',user_id='.intval($user_id);
	if($_pm['mysql']->columnExists('yb', 'sn_platform')) $ybExtra .= ",sn_platform='AppleIAP'";
	$sql = "INSERT INTO yb SET payname='{$subjectSql}',paytime='{$buy_time}',paymoney='{$priceSql}',getyb={$buy_number},orderid='{$ybOrderIdSql}'{$ybExtra}";
	if(!$_pm['mysql']->query($sql) || mysql_affected_rows($_pm['mysql']->getConn()) != 1)
	{
		iosPayAbort("pay_error:db");
	}
	$sql = "UPDATE player SET yb = COALESCE(yb,0) + {$buy_number} WHERE id = {$user_id}";
	if(!$_pm['mysql']->query($sql) || mysql_affected_rows($_pm['mysql']->getConn()) != 1)
	{
		iosPayAbort("pay_error:db");
	}
	//更新购买日志
	$sql = "UPDATE t_shop_log SET trade=1,item_id='{$productIdSql}',transaction_id='{$transactionIdSql}',product_id='{$productIdSql}',buy_number='{$buy_number}',buy_price='{$priceSql}' WHERE id='{$logId}'";
	if(!$_pm['mysql']->query($sql) || mysql_affected_rows($_pm['mysql']->getConn()) != 1)
	{
		iosPayAbort("pay_error:db");
	}

	if(!$_pm['mysql']->query('COMMIT'))
	{
		iosPayAbort("pay_error:db");
	}
	$iosPayTransactionActive = false;
	iosPayReleaseLock();
	$_pm['mem']->del(intval($user_id));
	echo "pay_ok";

}

/**
 重新组织产品方便查找
*/
function productFromat($produts){
	$productArr = array();
	if(is_array($produts)){
		foreach($produts as $val){
			if(!is_array($val) || !isset($val['name']) || !isset($val['price']) || is_array($val['name'])) continue;
			$productArr[strval($val['name'])] = $val;
		}
	}
	return $productArr;
}

function verifyAppleReceipt($receipt, $expectedProductId)
{
	$result = getReceiptData($receipt, false, $expectedProductId);
	if(isset($result['status']) && intval($result['status']) === 21007)
	{
		return getReceiptData($receipt, true, $expectedProductId);
	}
	if(isset($result['err']) && isset($result['network']) && $result['network'])
	{
		$result = getReceiptData($receipt, false, $expectedProductId);
		if(isset($result['status']) && intval($result['status']) === 21007)
		{
			return getReceiptData($receipt, true, $expectedProductId);
		}
	}
	return $result;
}

//苹果支付验证：正式环境优先，只有 21007 才切换到沙盒。
function getReceiptData($receipt, $isSandbox = false, $expectedProductId = ''){
	$endpoint = $isSandbox
		? 'https://sandbox.itunes.apple.com/verifyReceipt'
		: 'https://buy.itunes.apple.com/verifyReceipt';
	$postData = json_encode(array('receipt-data' => $receipt));
	if($postData === false) return array('err'=>'Invalid receipt request');

	$ch = curl_init($endpoint);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
	curl_setopt($ch, CURLOPT_TIMEOUT, 15);
	$response = curl_exec($ch);
	$errno = curl_errno($ch);
	curl_close($ch);
	if($errno != 0 || $response === false) return array('err'=>'network error','network'=>1);

	$data = json_decode($response);
	if (!is_object($data) || !isset($data->status)) return array('err'=>'Invalid response data');
	$status = intval($data->status);
	if($status !== 0) return array('err'=>'Apple status '.$status,'status'=>$status);
	if(!isset($data->receipt) || !is_object($data->receipt)) return array('err'=>'Invalid receipt data');

	$receiptData = $data->receipt;
	$candidates = array();
	if(isset($receiptData->product_id) && isset($receiptData->transaction_id)) $candidates[] = $receiptData;
	if(isset($receiptData->in_app) && is_array($receiptData->in_app))
	{
		foreach($receiptData->in_app as $entry)
		{
			if(is_object($entry) && isset($entry->product_id) && isset($entry->transaction_id)) $candidates[] = $entry;
		}
	}

	$selected = false;
	$selectedScore = -1;
	foreach($candidates as $index=>$entry)
	{
		if($expectedProductId !== '' && strval($entry->product_id) !== strval($expectedProductId)) continue;
		if(isset($entry->cancellation_date) || isset($entry->cancellation_date_ms)) continue;
		$score = isset($entry->purchase_date_ms) ? floatval($entry->purchase_date_ms) : $index;
		if($selected === false || $score >= $selectedScore)
		{
			$selected = $entry;
			$selectedScore = $score;
		}
	}
	if($selected === false) return array('err'=>'Invalid receipt item');

	$quantity = isset($selected->quantity) ? intval($selected->quantity) : 1;
	if($quantity < 1 || $quantity > 100) return array('err'=>'Invalid receipt quantity');
	return array(
		'success'        => 1,
		'quantity'       => $quantity,
		'product_id'     => strval($selected->product_id),
		'transaction_id' => strval($selected->transaction_id),
		'purchase_date'  => isset($selected->purchase_date) ? strval($selected->purchase_date) : '',
		'app_item_id'    => isset($receiptData->app_item_id) ? strval($receiptData->app_item_id) : '',
		'bid'            => isset($receiptData->bundle_id) ? strval($receiptData->bundle_id) : (isset($receiptData->bid) ? strval($receiptData->bid) : ''),
		'bvrs'           => isset($receiptData->application_version) ? strval($receiptData->application_version) : (isset($receiptData->bvrs) ? strval($receiptData->bvrs) : '')
	);
}


function log_result($word) {
    $fp = fopen(dirname(__FILE__)."/paylog.txt","a");
    if(!$fp) return;
    flock($fp, LOCK_EX) ;
    fwrite($fp,"\n".$word."\n");
    flock($fp, LOCK_UN);
    fclose($fp);
}
