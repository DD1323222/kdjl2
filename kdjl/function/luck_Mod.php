<?php
session_start();
ini_set('display_errors',false);
//error_reporting(E_ALL);
require_once('../config/config.game.php');
secStart($_pm['mem']);
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1) die('');
$t = '';
$shop = '';
$config = $_pm['mysql'] -> getOneRecord("SELECT value2 FROM welcome WHERE code = 'ticket'");
$value2 = is_array($config) && isset($config['value2']) ? $config['value2'] : '';
$timearr = explode(':',$value2);
$activityEnabled = trim((string)getenv('KDJL_LUCKY_NUMBER_DRAW_ENABLED')) === '1' &&
	count($timearr) >= 2 && intval($timearr[0]) === 1;
$ticket = array();
if($activityEnabled)
{
	$ticketTable = 'ticket_'.date('Ymd');
	$table = $_pm['mysql']->getOneRecord("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$ticketTable}'");
	if(is_array($table))
	{
		$rows = $_pm['mysql']->getRecords('SELECT ticket_num FROM '.$ticketTable.' WHERE uid='.$uid);
		if(is_array($rows)) $ticket = $rows;
	}
}
if(empty($ticket)){
	$t = ' <tr>
          <td align="center">您没有购买幸运数字</td>
          <td align="center"></td>
        </tr>';
}else{
	$t = ' <tr>
		  <td align="center" colspan=2>您今日共开了<span style="color:red"> '.count($ticket).' </span>个幸运数字</td>
        </tr>';
	$hit = $_pm['mysql'] -> getRecords('SELECT pnote FROM gamelog WHERE vary=107 AND seller='.$uid.' AND buyer = "'.date('Y-m-d').'"');
	if(!is_array($hit)) $hit = array();

	if(count($hit) >= 1){
		foreach($hit as $hk => $hv){
			if(!is_array($hv)) continue;
			$pnote = isset($hv['pnote']) ? htmlspecialchars((string)$hv['pnote'], ENT_QUOTES, 'UTF-8') : '';
			if($hk%2==0){
				$t .= ' <tr>
					  <td align="center" style="color:#9900FF">'.$pnote.'</td>';
			}else{
				$t .= '<td align="center" style="color:#9900FF">'.$pnote.'</td>
					</tr>';
			}
		}
		if(count($hit) % 2 == 1){
			$t .= '<td align="center"></td>
					</tr>';
		}
	}
	foreach($ticket as $k => $v){
		if(!is_array($v)) continue;
		$ticketNum = isset($v['ticket_num']) ? htmlspecialchars((string)$v['ticket_num'], ENT_QUOTES, 'UTF-8') : '';
		if($k%2==0){
			$t .= ' <tr>
				  <td align="center">'.$ticketNum.'</td>';
		}else{
			$t .= '<td align="center">'.$ticketNum.'</td>
				</tr>';
		}
	}
	if(count($ticket) % 2 == 1){
		$t .= '<td align="center"></td>
				</tr>';
	}
}
$str = '';
if($activityEnabled){
	$openHour = intval($timearr[1]);
	if(date('H')>=$openHour){
		$str = kdjlSafeMemValue($_pm['mem']->get('luck_'.date('md')), '');
		if(!is_string($str) || $str === '') $str = '<span class="text">今日开奖记录尚未生成。</span>';
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
