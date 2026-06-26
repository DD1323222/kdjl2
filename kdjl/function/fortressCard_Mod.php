<?php
require_once('../config/config.game.php');
secStart($_pm['mem']);
require_once('../sec/dblock_fun.php');
function msg($m)
{
	realseLock();
	die($m);
}
$a = getLock($_SESSION['id']);
if(!is_array($a)){
	msg('服务器繁忙，请稍候再试！');
}
//$_SESSION['fortress_pass'] = 1;
/*if($_SESSION['fortress_pass'] != 1){
	msg('非法进入'.$_SESSION['fortress_pass']);
}*/
//$_SESSION['fortress_pass_last_time']=$_SESSION['fortress_pass_time'];
function microtime_float()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}
//unset($_SESSION['fortress_pass_time']);
if(!is_array($_SESSION['fortress_pass_time']))
{
	$_SESSION['fortress_pass_time']=array();
}
$_SESSION['fortress_pass_time'][]=microtime_float();

//var_dump($_SESSION['fortress_pass_time']);

if(count($_SESSION['fortress_pass_time'])>3)
{
	array_shift($_SESSION['fortress_pass_time']);
}

$sql = 'SELECT bb_id,v_times,f_times,fv_result,cur_gpc_id FROM fortress_users_'.date("Ymd").' WHERE user_id = '.$_SESSION['id'];
$fortress_arr = $_pm['mysql'] -> getOneRecord($sql);
$c = time()-$_SESSION['last_f_v_t'];
if($fortress_arr['cur_gpc_id']!=0){
	$cur_cards=unserialize($_pm['mem']->get('fortress_card_info_'.date('md').'_'.$_SESSION['id']));
	$cur_cards[]=array('id'=>$_SESSION['fortress_card_id'],'img' =>'<img src=" ../images/ys/miss.png" width="62">');
	$_pm['mem']->set(array('k'=>'fortress_card_info_'.date('md').'_'.$_SESSION['id'],'v'=>$cur_cards));
	 $_SESSION['fortress_card_id'] = 0;
	 $_pm['mysql'] -> query('UPDATE fortress_users_'.date("Ymd").' SET cur_gpc_id = 0 WHERE user_id = '.$_SESSION['id']);
}

if($_SESSION['fortress_pass'] != 1 && $c > 1 && $_SESSION['fortress_pass'] != 3){
	msg('cur_gpc_id:'.$fortress_arr['cur_gpc_id'].'fortress_card_id:'.$_SESSION['fortress_card_id'].'fortress_pass:'.$_SESSION['fortress_pass']);
}
$_SESSION['fortress_pass'] = 2;
$_SESSION['last_f_v_t']=time();
$setting = $_pm['mem']->get('db_welcome1');
if(!is_array($setting)) $setting=unserialize($setting);
if(!is_array($setting))
{
	msg('后台配置数据读取失败(1)！'.print_r($setting,1));
}
if(!isset($setting['fortress']))
{
	msg('缺少活动开启设定(fortress)！');
}

if(!isset($setting['fortress_time']))
{
	msg('缺少活动开启设定(fortress_time)！');
}

$time_settings=explode("|",$setting['fortress_time']);
$w=date('w');
$hm=date('His');
if($w==0)
{
	$w=7;
}
$time_flag=false;
foreach($time_settings as $s)
{
	$tmp=explode(',',$s);
	//1,210000,210459,212959,213459
	if($w==$tmp[0])
	{
		if($hm>=$tmp[1]&&$hm<=$tmp[3])
		{
			$time_flag=true;
		}
		if($hm>=$tmp[3]&&$hm<=$tmp[4]){
			header("Location:/function/fortress_stolen_Mod.php");
			exit;
		}
		$tmp2 = timetos($tmp[2]);
		$tmp3 = timetos($tmp[3]);
		$c = $tmp2-time();
		$touqu = ($tmp3-time())>0?($tmp3-time()):0;
		break;
	}
}
if($c < 0){
	$ctime = 0;
}else{
	$ctime = $c;
}
if(!$time_flag){
	msg('现在不是要塞开启时间！');
}



$jsstr='var openstr=[];';

$ar = unserialize($_pm['mem']->get('fortress_card_info_'.date('md').'_'.$_SESSION['id']));//print_r($ar);

if(is_array($ar)){		
	$i=0;
	foreach($ar as $v){
		$jsstr.='openstr['.($i).']=["'.$v['id'].'",\''.$v['img'].'\'];';
		$i++;
	}
}//echo $jsstr;


$finfo = '<table width="230" border="0" cellspacing="0" cellpadding="0" style="margin:10px 0 0 65px;">
            <tr>
              <td height="22">击败次数：'.$fortress_arr['f_times'].' </td>
              <td>胜利次数：'.$fortress_arr['v_times'].'</td>
            </tr>
            <tr>
              <td height="22">连败次数：'.($fortress_arr['fv_result']>0?0:abs($fortress_arr['fv_result'])).'</td>
              <td>连胜次数：'.($fortress_arr['fv_result']>0?$fortress_arr['fv_result']:0).'</td>
            </tr>
          </table>';

$marr = $_pm['mysql'] -> getOneRecord('SELECT player.nickname as nickname,hp,mp,srchp,srcmp,addhp,addmp,level,player.headimg FROM player,userbb WHERE player.id = '.$_SESSION['id'].' AND userbb.id='.$fortress_arr['bb_id']);
$playerinfo = '<div class="team">
            <div class="name">'.$marr['nickname'].'</div>
            <div class="level">'.$marr['level'].'</div>
            <div class="avatar"><img src="../images/tarot/face'.$marr['headimg'].'.gif" /></div>
            <div class="red"><p style="width:'.(100*($marr['hp']+$marr['addhp'])/($marr['srchp']+$marr['addhp'])).'%"></p></div>
            <div class="blue"><p style="width:'.(100*($marr['mp']+$marr['addmp'])/($marr['srcmp']+$marr['addmp'])).'%"></p></div>
        </div>';

$tn = $_game['template'] . 'tpl_fortressCard.html';
if (file_exists($tn)){
	$tpl = @file_get_contents($tn);
		
	$src = array(
				 '#js#',
				 '#finfo#',
				 '#ctime#',
				 '#touqu#',
				 '#playerinfo#'
				 );
	$des = array(
				  $jsstr,
				  $finfo,
				  $ctime,
				  $touqu,
				  $playerinfo
				);
	$pinfo = str_replace($src, $des, $tpl);
}

// gzip echo. if maybe.
ob_start('ob_gzip');
echo $pinfo;
ob_end_flush();
$_pm['mem']->memClose();

function timetos($num){
	$h = substr($num,0,2);
	$i = substr($num,2,2);
	$s = substr($num,4,2);
	$date = date('Y-m-d').' '.$h.':'.$i.':'.$s;
	$ds = strtotime($date);
	return $ds;
}
?>