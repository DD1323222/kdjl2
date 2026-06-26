<?php

require_once('../config/config.game.php');
require_once('../config/config.alipay.php');

$receipt = $_REQUEST['receipt'];
$product_id = $_REQUEST['product_id'];
$produts = $backObj['apple'];
//$user_id = intval($_REQUEST['user_id']);
$user_id = intval($_SESSION['id']);
if($user_id < 1){
	echo "not_find user_id";
	die();
}

//log_result("user_id={$user_id}");
//log_result("product_id={$product_id}");
//log_result("receipt={$receipt}");

//调用验证
purchaseVerification($user_id,$receipt,$product_id,$produts);

//支付验证
function purchaseVerification($user_id,$receipt,$product_id,$produts){
	global $_pm;
	$productArr = productFromat($produts);
	
	if(!isset($productArr[$product_id])){
		echo "not find proudct";
		die();
	}
	//写购买记录日志
	$buy_time = time();
	$sql = "INSERT INTO t_shop_log(uid,product_id,buy_time,receipt)
			VALUES('{$user_id}','{$product_id}','{$buy_time}','{$receipt}')";
	$_pm['mysql']->query($sql);
	$logId = $_pm['mysql']->last_id();
	$VerifResult = getReceiptData($receipt,false);//true正式地址 false测试地址
	if(isset($VerifResult['err'])){
		$VerifResult = getReceiptData($receipt,false);//true正式地址 false测试地址
	}
	if(isset($VerifResult['err'])){
		$sql = "UPDATE t_shop_log SET error_detail='{$VerifResult['err']}' where id='{$logId}'";
		$_pm['mysql']->query($sql);
		echo "pay_error:".$VerifResult['err'];
		die();
	}
	if($product_id != $VerifResult['product_id']){//防止破解外挂特别加的判断
		echo "pay_error,waigua";
		die();
	}
	$transaction_id = $VerifResult['transaction_id'];
	$sql = "SELECT id FROM t_shop_log WHERE transaction_id='{$transaction_id}' and trade=1";
	$rs = $_pm['mysql']->getOneRecord($sql);
	if($rs){
		$sql = "UPDATE t_shop_log SET error_detail='repeat_submitted' WHERE id='{$logId}'";
		$_pm['mysql']->query($sql);
		echo "pay_ok1";
		die();
	}
	
	//发充值的元宝
	$price = $productArr[$product_id]['price'];
	$buy_number = $price * 30;
	$out_trade_no = $VerifResult['transaction_id'];
	$subject = "{$productArr[$product_id]['price']}RMB购买{$buy_number}元宝";
	$sql = "INSERT INTO yb (payname,paytime,paymoney,getyb,orderid) VALUES ('{$subject}','{$buy_time}','{$price}',{$buy_number},'{$out_trade_no}')";
	$_pm['mysql']->query($sql);
	$sql = "UPDATE player SET yb = yb + {$buy_number} WHERE id = {$user_id}";
	$_pm['mysql']->query($sql);
	//更新购买日志
	$sql = "UPDATE t_shop_log SET trade=1,item_id='{$product_id}',transaction_id='{$out_trade_no}',product_id='{$product_id}',buy_number='{$buy_number}' WHERE id='{$logId}'";
	$_pm['mysql']->query($sql);

	echo "pay_ok";
	
}

/**
 重新组织产品方便查找
*/
function productFromat($produts){
	$productArr = array();
	if($produts){
		foreach($produts as $val){
			$productArr[$val['name']] = $val;
		}
	}
	return $productArr;
}

//苹果支付验证
function getReceiptData($receipt, $isSandbox = true){
	
		if($isSandbox) {
			$endpoint = 'https://buy.itunes.apple.com/verifyReceipt';
        } else {   
        	$endpoint = 'https://sandbox.itunes.apple.com/verifyReceipt';
        } 

        $postData = json_encode(array('receipt-data' => $receipt));
        //$ch = curl_init();
        $ch = curl_init($endpoint);   
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);   
        curl_setopt($ch, CURLOPT_POST, true);   
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

        $response = curl_exec($ch);   
        $errno    = curl_errno($ch);   
        $errmsg   = curl_error($ch);   
        curl_close($ch);
        if ($errno != 0) {
        	return array('err'=>'network error');
            
        }
        $data = json_decode($response);   
       
        if (!is_object($data)) {
        	return array('err'=>'Invalid response data');
              
        }   
		
        return array( 
        	'sucess'       	 =>  1,  
            'quantity'       =>  $data->receipt->quantity,//购买商品的数量   
            'product_id'     =>  $data->receipt->product_id, //商品的标识  
            'transaction_id' =>  $data->receipt->transaction_id, //交易的标识  
            'purchase_date'  =>  $data->receipt->purchase_date, //交易的日期  
            'app_item_id'    =>  $data->receipt->app_item_id, //Store用来标识程序的字符串  
            'bid'            =>  $data->receipt->bid, //iPhone程序的bundle标识   
            'bvrs'           =>  $data->receipt->bvrs // iPhone程序的版本号
        );
}


function log_result($word) {
    $fp = fopen("paylog.txt","a");
    flock($fp, LOCK_EX) ;
    fwrite($fp,"\n".$word."\n");
    flock($fp, LOCK_UN);
    fclose($fp);
}	