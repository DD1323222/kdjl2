<?php
/* 
 * 
 */
require_once('../config/config.game.php');
define(MEM_FIGHTUSER_KEY, $_SESSION['id'] . 'fuser');
secStart($_pm['mem']);
$bag		= $_pm['user']->getUserBagById($_SESSION['id']);

function msg($m)
{
	die($m);
}

$setting = $_pm['mem']->get('db_welcome1');
if(!is_array($setting)) $setting=unserialize($setting);
if(!is_array($setting))
{
	msg('后台配置数据读取失败(1)！'.print_r($setting,1));
}

if(!isset($setting['fortress_time']))
{
	msg('缺少活动开启设定(fortress_time)！');
}

$stolenStr="";
$stolen_arr=array();
$stolen_arr1=array();
$stolen_arr2=array();
foreach($bag as $v)
{
	if(strpos($v["effect"],'stolen_yaosai_jifen:')!==false)
	{
		if($v["expire"]>0){
			$stolen_arr1[]=$v;
		}else{
			$stolen_arr2[]=$v;
		}
	}
}

$stolen_arr=array_merge($stolen_arr1,$stolen_arr2);
function tosecond($str)
{
	return substr($str,0,2)*3600+substr($str,2,2)*60+substr($str,-2);
}

$time_settings=explode("|",$setting['fortress_time']);
$w=date('w');
$hm=date('His');
if($w==0)
{
	$w=7;
}
$time_flag=false;
$timejs='';
foreach($time_settings as $s)
{
	$tmp=explode(',',$s);
	//1,210000,210459,212959,213459
	if($w==$tmp[0])
	{
		if($hm>=$tmp[3]&&$hm<=$tmp[4])
		{
			$timejs='var times=['.tosecond($tmp[3]).','.tosecond($tmp[4]).','.tosecond($hm).'];';
			$time_flag=true;
		}
		break;
	}
}


$table_name   = "`fortress_users_".date("Ymd")."`";
$user_fortress= $_pm['mysql']->getOneRecord('select v_times,f_times,fv_result,at_section_num,bb_id from '.$table_name.' where user_id='.$_SESSION['id']);

if(!$user_fortress)
{
	msg('<script language="javascript">parent.Alert("您没有参加要塞活动！");history.back();</script>');
}


$key='fortress_score_'.date("Ymd").'_'.$user_fortress['at_section_num'];
$fortress_over=$_pm['mem']->get($key);

if(!$fortress_over||isset($_GET['update_score_final']))
{
	$_pm['mem']->set(array('k'=>$key,'v'=>1));
	$_pm['mysql']->query('update '.$table_name.' set score_final=score where score_final=0');
}
	
	

$js='';
if(isset($_GET['stolen']))
{
	
	if(!$time_flag){
		echo '<script language="javascript">parent.Alert("现在不是要塞夺取时间！");history.back();</script>';
	}else if(empty($stolen_arr)){
		echo '<script language="javascript">parent.Alert("您没有夺取道具！");history.back();</script>';
	}else{
		require_once('../sec/dblock_fun.php');			
		require_once(dirname(__FILE__).'/../socketChat/config.chat.php');
		$s=new socketmsg();
		$a = getLock($_SESSION['id']);
		
		$sql = 'select score,score_final,user_id,nickname from '.$table_name.' where score_final>0 and user_id<>'.$_SESSION['id'].' order by score_final desc';		
		$fortress_score=$_pm['mysql']->getRecords($sql);
		if(empty($fortress_score)){
			echo '<script language="javascript">parent.Alert("没有玩家可以夺取！");history.back();</script>';
		}
		foreach($stolen_arr as $k=>$arr)
		{
			if($arr['sums']>0)
			{
				$r1=rand(1,100);
				$eff=explode(':',$arr["effect"]);
				$eff=explode(';',$eff[1]);
	
				$point=1;
				$percent=1;
				foreach($eff as $e)
				{
					$tmp=explode(',',$e);
					$tmp1=explode('-',$tmp[0]);
					$tmp2=explode('-',$tmp[1]);
					if($tmp1[0]+0<=$r1&&$r1<=$tmp1[1]+0)
					{
						$percent=rand($tmp2[0],$tmp2[1]);
						break;
					}
				}
				$aim_name='';
				if(is_array($fortress_score)&&count($fortress_score))
				{
					$r2=rand(1,count($fortress_score));					
					getLock($fortress_score[$r2-1]['user_id']);	
					if($percent>50)
					{
						echo '<font color=#ff0000>将此信息报告管理员:'.$tmp2[0].','.$tmp2[1]."</font><br>\n";
					}
				
					
					$point=ceil(($percent/100)*$fortress_score[$r2-1]['score_final']);
					if($fortress_score[$r2-1]['score_final']-$point<$fortress_score[$r2-1]['score']/2)
					{
						$new_score=ceil($fortress_score[$r2-1]['score']/2);
					}else{
						$new_score=$fortress_score[$r2-1]['score_final']-$point;
					}
					$_pm['mysql']->query('update '.$table_name.' set score_final='.$new_score.' where user_id='.$fortress_score[$r2-1]['user_id']);
					$aim_name=$fortress_score[$r2-1]['nickname'];
					$_pm['mysql']->query('update '.$table_name.' set score_final=score_final+'.$point.' where user_id='.$_SESSION['id']);
					
					$nicknamea=iconv('gbk','utf-8',$_SESSION['nickname']);
					$nicknameb=iconv('gbk','utf-8',$aim_name);

					$msg='<strong>'.$nicknamea.iconv('gbk','utf-8','</strong>夺取了<strong>').$nicknameb.'</strong> '.$point.iconv('gbk','utf-8',' 点要塞积分。');
					$s->sendMsg('an|'.$msg,'__ALL__');
				}
				
				$_pm['mysql']->query('update userbag set sums='.($arr['sums']-1).' where id='.$arr['id']);
				$js='parent.Alert("成功夺取了'.$aim_name.$point.'点积分！")';
				$stolen_arr[$k]['sums']=$arr['sums']-1;				
				break;
			}
		}
		realseLock();
	}
}

foreach($stolen_arr as $v)
{
	$stolenStr.=$v['name'].'('.$v['sums'].')';
}

$sql = 'select score,score_final,user_id,nickname from '.$table_name.' where at_section_num='.$user_fortress['at_section_num'].' order by score_final desc';
		
$fortress_score=$_pm['mysql']->getRecords($sql);

$ph='';
foreach($fortress_score as $k=>$row)
{
	$ph.='
            <tr>
              <td align="center" class="text01">'.($k+1).'</td>
              <td align="center" class="text01">'.$row['nickname'].'</td>
              <td align="center" class="text01">'.$row['score_final'].'</td>
            </tr>';
}
$marr = $_pm['mysql'] -> getOneRecord('SELECT player.nickname as nickname,hp,mp,srchp,srcmp,addhp,addmp,level,player.headimg FROM player,userbb WHERE player.id = '.$_SESSION['id'].' AND userbb.id='.$user_fortress['bb_id']);
$playerinfo = '<div class="team">
            <div class="name">'.$marr['nickname'].'</div>
            <div class="level">'.$marr['level'].'</div>
            <div class="avatar"><img src=" ../images/tarot/face'.$marr['headimg'].'.gif" /></div>
            <div class="red"><p style="width:'.(100*($marr['hp']+$marr['addhp'])/($marr['srchp']+$marr['addhp'])).'%"></p></div>
            <div class="blue"><p style="width:'.(100*($marr['mp']+$marr['addmp'])/($marr['srcmp']+$marr['addmp'])).'%"></p></div>
        </div>';
$tn = $_game['template'].'tpl_fortress_stolen.html';
if (file_exists($tn))
{
	$tpl = file_get_contents($tn);

	$src = array(
				 "#stolenStr#",
				 "#v_times#",
				 "#sv_times#",
				 '#f_times#',
				 '#sf_times#',
				 '#ph#',
				 '#timejs#',
				 '#info#'
				);
	$des = array(
				 $stolenStr,
				 $user_fortress['v_times'],
				 $user_fortress['fv_result']>0?$user_fortress['fv_result']:0,
				 $user_fortress['f_times'],
				 $user_fortress['fv_result']<0?abs($user_fortress['fv_result']):0,
				 $ph,
				 $timejs,
				 $playerinfo
			);
	$ret = str_replace($src, $des, $tpl);
}

$_pm['mem']->memClose();
// gzip echo. if maybe.
ob_start('');
echo $ret;
ob_end_flush();
//$('gw').contentWindow.location='/function/fortress_Mod.php';
?>