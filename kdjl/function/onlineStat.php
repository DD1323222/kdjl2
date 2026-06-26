<?php 
/*
* 在线玩家统计。每$frequency分钟统一次
*/
@session_start();
//set_time_limit(10);
require_once('../config/config.game.php');

$frequency = 10;//多少分钟记录一次
$domainPrefix = substr($_SERVER['HTTP_HOST'],0,strpos($_SERVER['HTTP_HOST'],"."));
//$domainPrefix = preg_replace('/[^\w]/','_',$_SERVER['HTTP_HOST']);
$domainPrefix = "pokeelf";
$memKey= "last_update_user_onlie_stat_time_".$domainPrefix;
$rs['name'] = $_SESSION['username'];
$isGm=false;


$_pm['mysql']->query("UPDATE player SET lastvtime=".time()." WHERE id=".$_SESSION['id']."");
$_pm['mem'] -> set(array('k' => 'last_visit_'.$_SESSION['id'],'v' => time()));
$old = unserialize($_pm['mem']->get($domainPrefix.'_online_user_list'));
if(empty($old)||!is_array($old)) $old =array();
$old[$_SESSION['id']] = time();
$_pm['mem']->set(array('k'=>$domainPrefix.'_online_user_list','v'=>$old));



if(isset($_SESSION[$keyrf])&&time()-$_SESSION[$keyrf]>=40)
{
	echo  '
		<script language="javascript">
			setTimeout("window.parent.goToIndex();",500);
		</script>
	';
	$_SESSION[$keyrf] = NULL;
	unset($_SESSION[$keyrf]);
}else{
	echo time().'-'.$_SESSION[$keyrf].'&='.(time()-$_SESSION[$keyrf])."\n";
}
$fcmflag = false;
//网易用户与混服用户不用防沉迷
if($_SESSION['lys_id'] == 'webgame' && substr($_SESSION['username'],0,4) != 'weby')
{
	$fcmflag=true;
}
if($fcmflag){//防沉迷
	if(!isset($_SESSION['onlinetimelog']))
	{
		$_SESSION['onlinetimelog']=time();
	}
	function curlSN($url,$port=80){
		$post = 1;
		$returntransfer = 1;
		$header = 0;
		$nobody = 0;
		$followlocation = 1;
		
		$ch = curl_init();
		$options = array(CURLOPT_URL => $url,
							CURLOPT_HEADER => $header,
							CURLOPT_NOBODY => $nobody,
							CURLOPT_PORT => $port,
							CURLOPT_POST => $post,
							CURLOPT_POSTFIELDS => $request,
							CURLOPT_RETURNTRANSFER => $returntransfer,
							CURLOPT_FOLLOWLOCATION => $followlocation,
							CURLOPT_COOKIEJAR => $cookie_jar,
							CURLOPT_COOKIEFILE => $cookie_jar,
							CURLOPT_REFERER => $url
							);
		curl_setopt_array($ch, $options);
		$result = curl_exec($ch);
		curl_close($ch);
		return $result;
	}
	if($_SESSION['fcm_flag']==1||isset($_GET['fcmme'])){
		if(time()-$_SESSION['onlinetimelog']>=360||isset($_GET['fcmme']))
		{
			$td=time()-$_SESSION['onlinetimelog'];
			if($td>1800) $td=1799;
			$key='*)(OJI(*77786*(**(8';
			//$url='http://61.160.192.12/update.php?username='.$_SESSION['username'].'&host='.$_SERVER['HTTP_HOST'].'&time='.($td).'&sn='.md5($_SERVER['HTTP_HOST'].$_SESSION['username'].date("Ymd").$key);
			$url='http://47.121.183.254/update.php?username='.$_SESSION['username'].'&host='.$_SERVER['HTTP_HOST'].'&time='.($td).'&sn='.md5($_SERVER['HTTP_HOST'].$_SESSION['username'].date("Ymd").$key);
			$rs=curlSN($url);
			$chenmitime=18000;
			$_SESSION['onlinetimelog']=time();
			if(strpos($rs,'ok')===false)
			{
				//unset($_SESSION['id']);
				//unset($_SESSION['username']);	
				$x=explode('|',$rs);
				echo  '<script language="javascript">
						window.parent.fcmdiv(5);
					</script>';			
			}
			//$url='http://61.160.192.12/query.php?username='.$_SESSION['username'].'&host='.$_SERVER['HTTP_HOST'].'&time='.($td).'&sn='.md5($_SERVER['HTTP_HOST'].$_SESSION['username'].date("Ymd").$key);
			$url='http://47.121.183.254/query.php?username='.$_SESSION['username'].'&host='.$_SERVER['HTTP_HOST'].'&time='.($td).'&sn='.md5($_SERVER['HTTP_HOST'].$_SESSION['username'].date("Ymd").$key);
				
			$rs=curlSN($url);
			echo $rs;
			if(substr($rs,0,5) == 'error')
			{
				//unset($_SESSION['id']);
				//unset($_SESSION['username']);	
				$x=explode('|',$rs);
				echo  '<script language="javascript">
						window.parent.fcmdiv(5);
					</script>';

			}
			$time_arr = explode("|",$rs);
			if($time_arr[0] == 'ok')
			{
				
				switch($time_arr[1])
				{
					case "1" :
					{
							echo  '<script language="javascript">
			window.parent.fcmdiv(1);
		</script>';
						break;
					}
					case "2" :
					{
							echo  '<script language="javascript">
			window.parent.fcmdiv(2);
		</script>';
						break;
					}
					case "3" :
					{
							echo  '<script language="javascript">
			window.parent.fcmdiv(3);
		</script>';
						break;
					}
					case "4" :
					{
							echo  '<script language="javascript">
			window.parent.fcmdiv(4);
		</script>';
						break;
					}
					
				}
			}
			if(intval(str_replace('ok|','',$rs))>$chenmitime-750)
			{
				die('<script language="javascript">parent.fcmct++;parent.Alert("您已经进入疲劳游戏时间，您的游戏收益将降为正常值的50％，为了您的健康，请尽快下线休息，做适当身体活动，合理安排学习生活!");setTimeout("window.location.reload();",3000);</script>');
			}
			
		}
	}
}
// 在线统计。
/*
$curminute = intval(date("i"));
if($curminute%2==0) 
{	
	$cur = $_pm['mysql']->getOneRecord('select left(from_unixtime(ctime),16) lastct from game_count order by id desc limit 1');
	if(!$cur||$cur['lastct']!=date("Y-m-d H:i")){
		//$domainPrefix = substr($_SERVER['HTTP_HOST'],0,strpos($_SERVER['HTTP_HOST'],"."));
		//$domainPrefix = preg_replace('/[^\w]/','_',$_SERVER['HTTP_HOST']);
		$old = unserialize($_pm['mem']->get($domainPrefix.'_online_user_list'));
		if(!is_array($old)) $old=array();
		$time = time()-300;
		foreach($old as $k=>$t)
		{
			if($t<$time) unset($old[$k]);
		}
		$_pm['mem']->set(array('k'=>$domainPrefix.'_online_user_list','v'=>$old));
		$_pm['mem']->set(array('k'=>$domainPrefix.'_online_user','v'=>count($old)));
		$_pm['mysql']->close();
		$_pm['mysql']	= new mysql();
		$sql = "insert into game_count(ctime,online) values('".time()."','".(count($old))."')";
		//$_pm['mysql']->query('delete from game_count where left(from_unixtime(ctime),16)="'.date("Y-m-d H:i").'"');
		$_pm['mysql']->query($sql);
		echo $sql;
	}
}
*/
?>
<script language="javascript">
setTimeout('window.location.reload()',60000+<?php echo rand(1,60)*1000; ?>);
</script>
<script src="/guard_thread.php"></script>
