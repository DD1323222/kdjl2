<?php
session_start();
ini_set('display_errors',false);
//error_reporting(E_ALL);
require_once('../config/config.game.php');
secStart($_pm['mem']);
$sql = 'SELECT ticket_num FROM ticket_'.date('Ymd').' WHERE uid = '.$_SESSION['id'];
$ticket = $_pm['mysql'] -> getRecords($sql);
if(!is_array($ticket)){
	$t = ' <tr>
          <td align="center">您没有购买幸运数字</td>
          <td align="center"></td>
        </tr>';
}else{
	$t = ' <tr>
          <td align="center" colspan=2>您今日共开了<span color=red> '.count($ticket).' </span>个幸运数字</td>
        </tr>';
	$hit = $_pm['mysql'] -> getRecords('SELECT pnote FROM gamelog WHERE vary=107 AND seller='.$_SESSION['id'].' AND buyer = "'.date('Y-m-d').'"');
	
	if(count($hit) >= 1){
		foreach($hit as $hk => $hv){
			if($hk%2==0){
				$t .= ' <tr>
					  <td align="center" style="color:#9900FF">'.$hv['pnote'].'</td>';
			}else{
				$t .= '<td align="center" style="color:#9900FF">'.$hv['pnote'].'</td>
					</tr>';
			}
		}
		if(count($hit) % 2 == 1){
			$t .= '<td align="center"></td>
					</tr>';
		}
	}
	foreach($ticket as $k => $v){
		if($k%2==0){
			$t .= ' <tr>
				  <td align="center">'.$v['ticket_num'].'</td>';
		}else{
			$t .= '<td align="center">'.$v['ticket_num'].'</td>
				</tr>';
		}
	}
}
$str = '';
$config = $_pm['mysql'] -> getOneRecord("SELECT value2,contents FROM welcome WHERE code = 'ticket'");
if(!is_array($config)){
	$str = '';
}else{
	$timearr = explode(':',$config['value2']);
	if($timearr['0']!=1){
		$str = '';
	}else{
		if(date('H')>=$timearr['1']){
			$str = unserialize($_pm['mem']->get('luck_'.date('md')));
			if(!$str){
				$str = file_get_contents('http://pmmg1.webgame.com.cn/shell/ticket_log/'.date('Ymd').'.html');
				$_pm['mem'] -> set(array('k'=>'luck_'.date('md'),'v'=>$str));
			}
			
		}
	}
}
$_pm['mem']->memClose();

//@Load template.
$tn = $_game['template'] . 'tpl_luck.html';
if (file_exists($tn))
{
	$tpl = @file_get_contents($tn);
	
	$src = array('#ticket#',
				  '#str#'
				);
	$des = array($t,
				  $str
				);
	$shop = str_replace($src, $des, $tpl);
}

unset($uobj, $user, $userbag,$_pm['mem']);

// gzip echo. if maybe.
ob_start('ob_gzip');
echo $shop;

ob_end_flush();
?>
