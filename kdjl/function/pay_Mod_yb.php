<?php
session_start();
if(!isset($_SESSION['username'])||strlen($_SESSION['username'])<3){
	echo '<script language="javascript">alert("请先登陆!");window.location="http://www.51.com";</script>';exit();
}
if(isset($_SESSION['51_session'])){
	foreach($_SESSION['51_session'] as $k=>$v){	
		$_POST[$k]=$v;	
	}
}else{
	echo '<script language="javascript">alert("登陆失效!");window.location="http://www.51.com";</script>';exit();
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
if($user===FALSE)
{
	die("信息错误！");
}

if(isset($_GET['num'])&&intval($_GET['num'])>0){	
	$price = intval($_GET['num']);
	$paytype = intval($_GET['paytype']);
	if($paytype!==0&&$paytype!==1)
	{
		die('alert("支付方式错误！")');
	}
	if($price>5000)
	{
		die('alert("每次最多购买5000个元宝！")');
	}
	$host = str_replace("PM51","",strtoupper(substr($_SERVER['HTTP_HOST'],0,strpos($_SERVER['HTTP_HOST'],'.'))));
	$msg = "口袋精灵".$host."区元宝".$price."个。";
	/* 订单信息 */
	$ordid=$_SESSION['id'].date("mdHis");
	$_SESSION['buyyb_info'][$ordid]=array($price,$paytype);
	//if($paytype==1){
	$db->query("CREATE TABLE if not exists `yb` (
	  `Id` int(11) NOT NULL auto_increment,
	  `payname` varchar(60) default '0',
	  `paytime` varchar(14) default '0',
	  `getyb` int(11) unsigned default '0',
	  `orderid` varchar(25) default '0',
	  `sn_platform` varchar(25) default '',
	  `user_id` int(11) default '0',
	  PRIMARY KEY  (`Id`)
	) TYPE=MyISAM; 
	");
	$db->query("insert into yb set payname='51".($paytype==0?'币':'银行')."购买元宝(User:".$_SESSION['name'].")',paytime='0',getyb=".$price.",orderid='".$ordid."',user_id=".$_SESSION['id']);
	//}
	$order = 
	array(
		//'environment'=>"production",
		//'version'=>"2.0",
		//'user'=>$_SESSION['name'],
		'order_id'          =>$ordid,
		'order_price'       =>$paytype==0?$price/10:$price*10,
		'order_num'         =>$price,
		'pay_sandbox'       =>1,
		'pay_code'          =>1,
		'pay_shipping'      =>'http://'.$_SERVER['HTTP_HOST'].'/function/success_Mod_yb.php',
		'order_msg'=>$msg,
		'order_callback_url'=> 'http://'.$_SERVER['HTTP_HOST'].'/function/success_Mod_yb.php',//?order_code=0
		'order_check_url'   => 'http://'.$_SERVER['HTTP_HOST'].'/function/success_Mod_yb.php', // 支付成功后的对帐通知地址，最多 200 字节。
		'order_cancel_url'  => 'http://'.$_SERVER['HTTP_HOST'].'/function/success_Mod_yb.php?cancel=1'
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
<meta http-equiv="Content-Type" content="text/html; charset=gbk" />
<script language="javascript">
var paytype = 0;
function $(id){return document.getElementById(id);}
function buy(){
	var  price = parseInt($("num").value);
	$("num").value = price;
	if(price<1){alert("最少需要购买一个元宝！");return;}
	if(price>5000){alert("最多购买5000个元宝！");return;}

	if(confirm("确定要购买吗？")){
		$('buyform').action = "pay_Mod_yb.php?num="+price+"&paytype="+paytype+"&rd="+Math.random();
		$("btnbuy").disabled = true;
		document.getElementById("buyform").submit();
	}
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
		 $("pay"+i).style.backgroundColor = "#c0c0c0";
		 $("pay"+i).style.color = "#000000";
		 $("pay"+i).style.fontWeight = '';
	}
	obj.style.backgroundColor = "#006600";
	obj.style.color = "#ffffff";
	obj.style.fontWeight = 'bold';
}
</script>
<style>
td,div,body{
font-size:12px;}
</style>
<body onLoad="calc();" leftmargin="40" topmargin="40" bgcolor="#FFFFFF">
<table width="400" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td style="height:24px; overflow:hidden">
    <span id="pay0" style=" font-weight:bold; color:#ffffff ;border:1px solid #006633; background-color:#006600; width:120px; cursor:pointer; height:24px; font-size:12px ;padding:4px" 
    onClick="payType(0);$('buyform').action='http://apps.51.com/payment/pay.php';$('mtype').innerHTML='个51币';">使用51币</span><span id="pay1" style="border:1px solid #006633;  cursor:pointer;background-color:#c0c0c0; width:120px; height:21px; font-size:12px; padding:4px" 
    onClick="payType(1);$('buyform').action='http://apps.51.com/paybank.php';$('mtype').innerHTML='元人民币';">使用网上银行</span>
    </td>
  </tr>
  <tr>
    <td style="padding-left:4px;padding-top:6px; background-color:#006600">
    购买：<input type="text" id="num" value="1" size="4" maxlength="4" />个元宝,将花费您<span id="51bi" style="color:#FF0000">10</span><span id="mtype">个51币</span>。
    </td>
  </tr>
  <tr id="pt0">
    <td style="padding-left:4px;padding-top:6px; background-color:#006600">
<form method="post" x="http://apps.51.com/sandbox_pay.php" action="http://apps.51.com/payment/pay.php" id="buyform" target="_blank">
 <input type="hidden" value="" name="51_pay" id="51_pay"/> 
 <input type="button" value="确定购买" onClick="buy()" id="btnbuy"/>
</form>    
    </td>
  </tr>

</table>



</body>