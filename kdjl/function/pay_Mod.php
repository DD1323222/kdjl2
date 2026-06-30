<?php
session_start();
if(!isset($_SESSION['username'])||strlen($_SESSION['username'])<3){
	echo '<script language="javascript">alert("请先登陆!");window.location="http://gm.yibaoma.shop";</script>';exit();
}
if(isset($_SESSION['51_session'])){
	foreach($_SESSION['51_session'] as $k=>$v){	
		$_POST[$k]=$v;	
	}
}else{
	echo '<script language="javascript">alert("登陆失效!");window.location="http://gm.yibaoma.shop";</script>';exit();
}
/*if($_SESSION['username'] != 'xlj1983cn' && $_SESSION['username'] != 'zengtest16' && $_SESSION['username'] != 'antoni01' && $_SESSION['username'] != 'jianyaoli' && $_SESSION['username'] != 'tanwei200763' && $_SESSION['username'] != 'daniel1983cn')
{
	die("支付暂时关闭！");
}*/
//$_POST['51_sig_time'] += 40000;//echo $_POST['51_sig_time']."<br />".time();exit;
require_once('../config/config.game.php');
include_once("../sdk/appinclude.php");
$m	= $_pm['mem'];
$db = &$_pm['mysql'];
$u	= $_pm['user'];
secStart($m);
$user	= $u->getUserById($_SESSION['id']);
$bags    = $u->getUserBagById($_SESSION['id']);
$props = unserialize($m->get(MEM_PROPS_KEY));
$taskword= taskcheck($user['task'],3);
if($user===FALSE)
{
	die("信息错误！");
}

require_once(dirname(__FILE__).'/51_check_pay.php');

if(isset($_GET['num'])&&intval($_GET['num'])>0){	
	$price = intval($_GET['num']);
	$paytype = isset($_GET['paytype']) ? intval($_GET['paytype']) : -1;
	if($paytype!==0&&$paytype!==1)
	{
		die('alert("支付方式错误！")');
	}
	if($paytype==1)
	{
		$houzhui = '_bank';
	}else{
		$houzhui = '_51coin';
	}
	$houzhui = "";
	if($price>5000)
	{
		die('alert("每次最多购买5000个元宝！")');
	}
	$host = strtoupper(preg_replace('/[^a-z0-9]/i','',str_replace("PM51","",strtok($_SERVER['HTTP_HOST'],'.'))));
	if($host === '') $host = 'LOCAL';
	$msg = "口袋精灵".$host."区元宝".$price."个。";
	/* 订单信息 */
	
	$ordid=substr($host,0,5).substr("000000000000".$_SESSION['id'],-8).substr(time(),-8).sprintf('%04d',mt_rand(0,9999));
	
	$_SESSION['buyyb_info'][$ordid]=array($price,$paytype);
	//if($paytype==1){
	$db->query("CREATE TABLE if not exists `yb` (
	  `Id` int(11) NOT NULL auto_increment,
	  `payname` varchar(60) default '0',
	  `paytime` varchar(14) default '0',
	  `paymoney` varchar(20) default '0',
	  `getyb` int(11) unsigned default '0',
	  `orderid` varchar(25) default '0',
	  `sn_platform` varchar(25) default '',
	  `user_id` int(11) default '0',
	  PRIMARY KEY  (`Id`)
	) ENGINE=InnoDB; 
	");
	$db->addColumnIfMissing('yb','sn_platform',"varchar(25) NOT NULL DEFAULT ''");
	$db->addColumnIfMissing('yb','user_id',"int(11) NOT NULL DEFAULT '0'");
	$payName = $db->escape('51'.($paytype==0?'币':'银行').'购买元宝(User:'.$_SESSION['username'].')');
	if($db->query("insert into yb set payname='{$payName}',paytime='0',getyb={$price},orderid='{$ordid}',user_id=".intval($_SESSION['id'])) === false)
	{
		die('alert("订单创建失败，请稍后重试！")');
	}
	//}
	$order = 
	array(
		'order_id'          => $ordid,
		'order_price'       => $paytype==0?$price/10:$price*10,
		'order_num'         => $price,
		'pay_shipping'      => 'http://'.$_SERVER['HTTP_HOST'].'/function/success_Mod'.$houzhui.'.php',
		'order_msg'=>$msg,
		'order_callback_url'=> 'http://'.$_SERVER['HTTP_HOST'].'/function/success_Mod'.$houzhui.'.php',//?order_code=0
		'order_check_url'   => 'http://'.$_SERVER['HTTP_HOST'].'/function/success_Mod'.$houzhui.'.php', // 支付成功后的对帐通知地址，最多 200 字节。
		'order_cancel_url'  => 'http://'.$_SERVER['HTTP_HOST'].'/function/success_Mod'.$houzhui.'.php?cancel=1'
	);
	
	//print_r($order);exit;
	/* 用create_post_string签名请求参数 */
	$OpenApp_51->api_client->set_encoding("GBK");
	$post = $OpenApp_51->api_client->create_post_string('51_pay', $order); // 请注意$post字符串长度不要超过1024
	echo 
	'
	<meta http-equiv="Content-Type" content="text/html; charset=gbk" />
	<span style="font-size:12px">跳转中，请稍等，请勿刷新页面…</span>
	<form method="post" id="buyform" action="'.($paytype==0?'http://apps.51.com/payment/pay.php':'http://apps.51.com/paybank.php').'">
	 <input type="hidden" value="'.str_replace(array('"',"\r","\n"),"",$post).'" name="51_pay" id="51_pay"/> 	 
	</form> 
	<script language="javascript">	
		  document.getElementById("buyform").submit();
	</script>
	';
	die();
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=gbk" />
<script language="javascript" src="../javascript/prototype.js"></script>
<title></title>
<style type="text/css">
<!--
body {
	margin-left: 3px;
	margin-top: 3px;
	background-color: #033E00;
	margin-right: 0px;
	margin-bottom: 0px;
	font-size: 12px;
}
a {
	font-size: 12px;
}
-->
</style>
<script language="javascript">
function   killErrors(){  
	return   true;
}     
window.onerror =  killErrors; 

var paytype = 0;
function $(id){return document.getElementById(id);}
function buy(){
	var  price = parseInt($("num").value);
	$("num").value = price;
	if(price<1){alert("最少需要购买一个元宝！");return;}
	if(price>5000){alert("最多购买5000个元宝！");return;}

	//if(confirm("确定要购买吗？")){
		$('buyform').action = "pay_Mod.php?num="+price+"&paytype="+paytype+"&rd="+Math.random();
		$("btnbuy").disabled = true;
		document.getElementById("buyform").submit();
	//}
}
function calc(){
	var  price = parseInt($("num").value);
	$('51bi').innerHTML = price/10;
	setTimeout("calc()",100)
}
function payType(id){
	paytype = id;
	var obj = $("pay"+id);
	for(var i=0;i<2;i++)
	{
		 $("pay"+i).style.backgroundColor = "#FFFFFF";
		 $("pay"+i).style.color = "#BF7D1A";
		 $("pay"+i).style.fontWeight = '';
	}
	obj.style.backgroundColor = "#FFFFFF";
	obj.style.color = "red";
	obj.style.fontWeight = 'bold';
}

</script>
<body onLoad="calc();">
<table width="778" height="311" border="0" cellpadding="0" cellspacing="0" bgcolor="F3EDC9">
  <tr>
    <td width="140" valign="top"><table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td><img src="../images/ui/shop/gm01.gif" width="140" height="149"></td>
      </tr>
      <tr>
        <td height="176" align="left" valign="top" background="../images/ui/shop/gm02.gif">
			<div style="width:95%;padding:3px;color:#7a4f27; line-height:1.7;font-size:12px;margin-left:3px;height:150px; overflow:auto;scrollbar-face-color:#DFDABC;scrollbar-highlight-color:#ffffff;scrollbar-3dlight-color:#DFDABC;scrollbar-shadow-color:#ffffff;scrollbar-darkshadow-color:#DFDABC;scrollbar-track-color:#DFDABC;scrollbar-arrow-color:#ffffff;">
				<?=$taskword?>
			</div>
		</td>
      </tr>
    </table></td>
    <td valign="top" >
		<div id="tag1" style="position:absolute;left:250px;top:15px;z-index:1; color:#F1EABA;font-weight:bold">
          <table width="100" border="0" cellspacing="0" cellpadding="0">
            <tr>
              <td width="12"><img src="../images/ui/shop/gm07.gif" width="12" height="25" /></td>
              <td align="right" valign="middle" background="../images/ui/shop/gm08.gif">&nbsp;</td>
              <td width="12"><img src="../images/ui/shop/gm06.gif" width="12" height="25" /></td>
            </tr>
          </table>
		</div>
		
		<div id="tag2" style="position:absolute;left:141px;top:15px;z-index:2;color:#F1EABA;font-weight:bold">
          <table width="100" border="0" cellspacing="0" cellpadding="0">
            <tr>
              <td width="12"><img src="../images/ui/shop/gm07.gif" width="12" height="25" /></td>
              <td align="left" valign="middle" background="../images/ui/shop/gm08.gif" onclick="window.parent.$('gw').src='./function/Shopsm_Mod.php?op=cmp';" style="cursor:pointer" >神密商店</td>
              <td width="12"><img src="../images/ui/shop/gm06.gif" width="12" height="25" /></td>
            </tr>
          </table>
		</div>
		
		
		<div id="tag3" style="position:absolute;left:195px;top:15px;z-index:3; color:#F1EABA;font-weight:bold">
          <table width="100" border="0" cellspacing="0" cellpadding="0">
            <tr>
              <td width="12"><img src="../images/ui/shop/gm03.gif" width="12" height="25" /></td>
              <td align="center" valign="middle" background="../images/ui/shop/gm05.gif" style="color:#3F3706"><b>充值元宝</b></td>
              <td width="12"><img src="../images/ui/shop/gm04.gif" width="12" height="25" /></td>
            </tr>
          </table>
	  </div>
	 
		
	  <div style="width:635px; height:290px; background-color:#DFD496; position:absolute; top: 38px; left: 143px;">
<div style="width:595px; height:275px; position:absolute; left: 10px;">
				<img src="../images/ui/shop/gm09.gif" /><br/>
			<div style='position:absolute; text-align:left; font-size:12px; line-height:1.5; width:593px; height:235px; color:#BF7D1A; border:1px solid #C6B764; background-color:#fff; top: 28px;'>
			
			<table width="594" height="139" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td style="height:24px; overflow:hidden">
    <span id="pay0" style=" font-weight:bold; color:red ;border:0px; background-color:#FFFFFF; width:120px; cursor:pointer; height:24px; font-size:12px ;padding:4px" 
    onClick="payType(0);$('buyform').action='http://apps.51.com/payment/pay.php';$('mtype').innerHTML='个51币';">使用51币</span>
	
	<span id="pay1" style="border:0px;cursor:pointer;background-color:#ffffff; width:120px; height:21px; font-size:12px; padding:4px" 
    onClick="payType(1);$('buyform').action='http://apps.51.com/paybank.php';$('mtype').innerHTML='元人民币';">使用网上银行</span>    </td>
  </tr>
  <tr>
    <td style="padding-left:4px;padding-top:6px;">
    购买：<input type="text" id="num" value="10" size="4" maxlength="4" />个元宝,将花费您<span id="51bi" style="color:#FF0000">10</span><span id="mtype">个51币</span>。    </td>
  </tr>
  <tr id="pt0">
    <td style="padding-left:4px;padding-top:6px;">
<form method="post" x="http://apps.51.com/sandbox_pay.php" action="http://apps.51.com/payment/pay.php" id="buyform" target="_blank">
 <input type="hidden" value="" name="51_pay" id="51_pay"/> 
 <input style="background-image:url(../images/ui/shop/gm13.gif);border:0px;width:39px;height:15px;color:#2F291D;cursor:hand;" type="button" value="充值" onClick="buy()" id="btnbuy"/>
</form>    </td>
  </tr>
</table>
			
		    <table width="100%" height="83" border="0" cellpadding="0" cellspacing="0">
              <tr>
                <td><span tag="span">&nbsp;&nbsp;如果您在支付过程中遇到任何问题，请及时与客服联系：<br />
&nbsp;&nbsp;客服电话：021-61631151<br />
                </span></td>
              </tr>
            </table>
		  </div>
		  </div>
		
		<!-- self -->
		<!-- self end-->
        <!-- left option.-->
        <!-- left option end.-->
        </div>
		</td>
  </tr>
</table>

<div style="position:absolute; left: 6px; top: 308px; width: 44px;">
	<input name="button" type="button" style="background-image:url(../images/ui/shop/gm13.gif);border:0px;width:39px;height:15px;color:#2F291D;cursor:hand;"  onclick="window.parent.$('gw').src='./function/City_Mod.php';" value="离开"/>
</div>

</body>
