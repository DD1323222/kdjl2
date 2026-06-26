<?php
/**
*@Author: %xueyuan%

*@Write Date: 2011.05.27
*@Update Date: 2011.05.27
*@Usage:Fightting saolei Mod
*@Note: none
*/
ini_set('display_errors',true);
error_reporting(E_ALL);
require_once('../config/config.game.php');
if($_SESSION['insl'] != $_SESSION['id'])
{
	die("error");
}
//全平台公告接口
$gonggao_interface = '';
$host = explode('.',$_SERVER['HTTP_HOST']);
if($host[1] == 'my4399')	//4399
{
	$gonggao_interface = 'http://pmmg1.webgame.com.cn/interface/sl_gg_4399.php';
	$pingtai = '4399';
}
elseif($host[1] == 'youxi567')	//7k7k
{
	$gonggao_interface = 'http://pmmg1.webgame.com.cn/interface/sl_gg_7k7k.php';
	$pingtai = '7k7k';
}
elseif($host[1] == 'webgame' && substr($host[0],0,4) != 'pm51')	//pm
{
	$gonggao_interface = 'http://pmmg1.webgame.com.cn/interface/sl_gg_pm.php';
	$pingtai = '官方';
}
 

$chooseid = $_GET['id'];
$props = unserialize($_pm['mem']->get('db_props'));
$user = $_pm['user']->getUserById($_SESSION['id']);
$bag  = $_pm['user']->getUserBagById($_SESSION['id']);
//扫雷验证
$a = unserialize($_pm['mem']->get('today_sl_user'));
$b = unserialize($_pm['mem']->get('today_is_use_ticket'));
$deal = true;
if(is_array($a))
{
	foreach($a as $info)
	{
		if($info == $_SESSION['id'])
		{
			$deal = false;
			if(is_array($b))
			{
				foreach($b as $info1)
				{
					if($info1 == $_SESSION['id'])
					{
						$deal = true;	//合法
					}
				}
			}
		}
	}
}
if(!$deal)
{
	$_pm['mysql'] -> query("INSERT INTO gamelog (seller,buyer,ptime,pnote,vary) VALUES({$_SESSION['id']},{$_SESSION['id']},".time().",'扫雷恶意玩家',253)");
	die("error");
	
}
$deal = new task;
$get_fh = 0;
require_once('../sec/dblock_fun.php');
$a = getLock($_SESSION['id']);
if(!is_array($a)){
	realseLock();
	die('服务器繁忙，请稍候再试！');
}
$res = $_pm['mysql'] -> getOneRecord("SELECT F_saolei_points FROM player_ext WHERE uid = ".$_SESSION['id']);
if($res['F_saolei_points']  == 3 || $res['F_saolei_points'] == 6 || $res['F_saolei_points'] == 9)
{
	if(rand(1,10) > 9)
	{
		$deal->saveGetPropsMore(4038,1);	//赠送复活卡
		$get_fh = 1;
	}
}
$sl_fhtime = $_pm['mysql'] -> getOneRecord(" SELECT sums FROM userbag WHERE pid = 4038 AND uid =  {$_SESSION['id']}");
$sl_fhtime = empty($sl_fhtime)?0:$sl_fhtime['sums'];
$configWelcome = unserialize($_pm['mem']->get('db_welcome'));
foreach($configWelcome as $info)
{
	if($info['code'] == 'sl_probability_'.$res['F_saolei_points'])
	{
		$this_probability = $info['contents'];
	}
	if($info['code'] == 'sl_prize_other_'.$res['F_saolei_points'])
	{
		$this_other = $info['contents'];
	}
}
$this_other = explode(',',$this_other);
//5.3
$luck = rand(1,100);	//幸运数,判断中大奖品,一般奖,或死亡
$arr = explode(',',$this_probability);
foreach($arr as $info)
{
	$mid_arr[] = explode(':',$info);
	
}
$prize_info_best = unserialize($_pm['mem']->get('sl_prize_info'));
$prize_info_best = $prize_info_best[$_SESSION['id']];
if(count($mid_arr) == 1)
{
	$num = explode('-',$mid_arr[0][1]);
	$i = 1;
	foreach($this_other as $info)
	{
		$mid = explode(':',$info);
		$this_other_thing[$i] = $mid[0];
		$i++;
	}
	shuffle($this_other_thing);
	while(count($this_other_thing) > 9)
	{
		array_pop($this_other_thing);
	}
	foreach($this_other_thing as $key => $val)
	{
		$key_end = $key+1;
		$this_thing_end[$key_end] = $val;
	}
	if($luck >= $num[0] && $luck <= $num[1])	//中好东西了
	{
		$this_thing_end[$chooseid] = $prize_info_best[$res['F_saolei_points']]['id'];
		$deal->saveGetPropsMore($this_thing_end[$chooseid],1);	//发奖品	
	}
	else										//中普通东西
	{
		while($goodthingarea == $chooseid || !is_int($goodthingarea) )
		{
			$goodthingarea = rand(1,9);
		}
		$this_thing_end[$goodthingarea] = $prize_info_best[$res['F_saolei_points']]['id'];
		$luck = rand(1,100);
		foreach($this_other as  $info)
		{
			$oarr = explode(':',$info);
			$othingnum = explode('-',$oarr[1]);
			if($luck >= $othingnum[0] && $luck <= $othingnum[1])
			{
				$this_thing_end[$chooseid] = $oarr[0];
				$deal->saveGetPropsMore($this_thing_end[$chooseid],1);	//发奖品
				break;
			}
		}
	}
	$_pm['mysql'] -> query("UPDATE player_ext SET F_saolei_points = F_saolei_points +1  WHERE uid = ".$_SESSION['id']);
}
else
{
	$type = 0;
	$bob_num = $res['F_saolei_points']-1;
	$other_num =  9-$bob_num-1;
	$good_num = 1;
	foreach($mid_arr as $info)
	{
		$m = explode('-',$info[1]);
		if($luck >= $m[0] && $luck <= $m[1] && $info[0] == 'good')
		{
			$this_thing_end_chooseid = $prize_info_best[$res['F_saolei_points']]['id'];
			$good_num--;
			$type = 1;
			$best_props_name = $_pm['mysql'] -> getOneRecord('SELECT name FROM props WHERE id = '.$prize_info_best[$res['F_saolei_points']]['id']);
			if($res['F_saolei_points'] == 9)
			{
				$_pm['mysql'] -> query("UPDATE player_ext SET F_saolei_points = 1  WHERE uid = ".$_SESSION['id']);
				$word = ",通过扫雷最终关,得到本关最极品奖励:".$best_props_name['name'];
				$_pm['mysql'] -> query("INSERT INTO gamelog (seller,buyer,ptime,pnote,vary) VALUES({$_SESSION['id']},{$_SESSION['id']},".time().",'扫雷通过第9关玩家',254)");
				//$_pm['mem']->set(array('k' => 'sl_die_option'.$_SESSION['id'], 'v' => $res['F_saolei_points']));
			}
			else
			{
				$_pm['mysql'] -> query("UPDATE player_ext SET F_saolei_points = F_saolei_points +1  WHERE uid = ".$_SESSION['id']);
				$word = ",通过扫雷第".$res['F_saolei_points']."关,得到本关最极品奖励:".$best_props_name['name'];
				$_pm['mysql'] -> query("INSERT INTO gamelog (seller,buyer,ptime,pnote,vary) VALUES({$_SESSION['id']},{$_SESSION['id']},".time().",'".$res['F_saolei_points']."关最极品:".$best_props_name['name']."',254)");
			}
			$deal->saveGetPropsMore($prize_info_best[$res['F_saolei_points']]['id'],1);	//发奖品
			if($gonggao_interface == '')
			{
				$deal ->saveGword($word);
			}
			else
			{
				$data['text'] = '恭喜'.$pingtai.'平台['.$host[0].']区玩家:'.$_SESSION['nickname'].$word;
				curl_post($gonggao_interface,$data);
			}
			break;
		}
		elseif($luck >= $m[0] && $luck <= $m[1] && $info[0] == 'die')
		{
			$today_sl_user = unserialize($_pm['mem']->get('today_sl_user'));
			if(!is_array($today_sl_user))
			{
				$today_sl_user = array($_SESSION['id']);
			}
			else
			{
				if( !in_array($_SESSION['id'],$today_sl_user) )
				{
					$today_sl_user[] = $_SESSION['id'];
				}
			}
			$_pm['mem']->set(array('k' => 'today_sl_user', 'v' => $today_sl_user));
			$today_sl_ticket_use = unserialize($_pm['mem']->get('today_is_use_ticket'));
			if(is_array($today_sl_ticket_use))
			{
				foreach($today_sl_ticket_use as $key => $info)
				{
					if($info == $_SESSION['id'])
					{
						unset($today_sl_ticket_use[$key]);
					}
				}
			}
			$_pm['mem']->set(array('k' => 'today_is_use_ticket', 'v' => $today_sl_ticket_use));
			$this_thing_end_chooseid = 'bob';
			$bob_num--;
			$type = 1;
			$_pm['mysql'] -> query("UPDATE player_ext SET F_saolei_points = 1  WHERE uid = ".$_SESSION['id']);
			//将死亡关卡数存入内存
			$sl_die_option = unserialize($_pm['mem']->get('sl_die_option'));
			$sl_die_option[$_SESSION['id']] = $res['F_saolei_points'];
			$_pm['mem']->set(array('k' => 'sl_die_option', 'v' => $sl_die_option));
			break;
		}
	}
	if($type != 1)	//中普通东西
	{
		$luck = rand(1,100);
		$other_num--;
		$num = explode('-',$mid_arr[0][1]);
		$i = 1;
		foreach($this_other as $info)
		{
			$mid = explode(':',$info);
			$this_other_thing[$i] = $mid[0];
			$i++;
		}
		foreach($this_other as  $info)
		{
			$oarr = explode(':',$info);
			$othingnum = explode('-',$oarr[1]);
			if($luck >= $othingnum[0] && $luck <= $othingnum[1])
			{
				$this_thing_end_chooseid = $oarr[0];
				$deal->saveGetPropsMore($this_thing_end_chooseid,1);	//发奖品
				$_pm['mysql'] -> query("INSERT INTO gamelog (seller,buyer,ptime,pnote,vary) VALUES({$_SESSION['id']},{$_SESSION['id']},".time().",'".$res['F_saolei_points']."关普通:".$this_thing_end_chooseid."',254)");
				break;
			}
		}
		$_pm['mysql'] -> query("UPDATE player_ext SET F_saolei_points = F_saolei_points +1  WHERE uid = ".$_SESSION['id']);		
	}
	if($other_num != 0)
	{
		foreach($this_other as $info)
		{
			$mid = explode(':',$info);
			$this_other_thing[] = $mid[0];
			$i++;
		}
		shuffle($this_other_thing);
		while(count($this_other_thing) > $other_num)
		{
			array_pop($this_other_thing);
		}
	}
	if($other_num == 0)
	{
		$this_other_thing = array();
	}
	if($bob_num != 0)
	{
		for($i=0;$i<$bob_num;$i++)
		{
			$this_other_thing[] = 'bob';
		}
	}
	if($good_num != 0)
	{
		$this_other_thing[] = $prize_info_best[$res['F_saolei_points']]['id'];
	}
	shuffle($this_other_thing);
	$type = 0;
	foreach($this_other_thing as $key => $val)
	{
		if($type == 0)
		{
			if($key+1 == $chooseid )
			{
				$this_thing_end[$key+1] = $this_thing_end_chooseid;
				$this_thing_end[$key+2] = $val;
				$type = 1;
			}
			else
			{
				$this_thing_end[$key+1] = $val;
			}
		}
		else
		{
			$this_thing_end[$key+2] = $val;
		}
	}
	if( $chooseid == 9)
	{
		$this_thing_end[9] = $this_thing_end_chooseid;
	}
	ksort($this_thing_end);
}
foreach($props as $key => $val)
{
	if(in_array($val['id'],$this_thing_end))
	{
		$return_thing_info[$val['id']] = $val;
	}
}
$echo = '<table id="leiqu" width="283" height="283"><tr>';
foreach($this_thing_end as $key => $info)
{
	if(($key-1)%3 == 0 && ($key-1) != 0)
	{
		$echo .= '</tr><tr>';
	}
	if(!$return_thing_info[$info]['img'])
	{
$echo .= '<td><div id="lq_'.$key.'" class="open_lei"><img title = "'.$return_thing_info[$info]['name'].'" src="'.IMAGE_SRC_URL."/props/bob.gif".'" /></div></td>';
	}
	else
	{
		$echo .= '<td><div id="lq_'.$key.'" class="open"><img width="40px" height="40px" title = "'.$return_thing_info[$info]['name'].'" src="'.IMAGE_SRC_URL."/props/".$return_thing_info[$info]['img'].'" /></div></td>';
	}
	if($key == $chooseid)
	{
		if(!$return_thing_info[$info]['img'])
		{
			$echo2 = '<img title = "'.$return_thing_info[$info]['name'].'" src="'.IMAGE_SRC_URL."/props/bob.gif".'" />';
			$echo3 = "在".$res['F_saolei_points']."关中,踩中地雷不幸身亡";
		}
		else
		{
			$echo2 = '<img width="40px" height="40px" title = "'.$return_thing_info[$info]['name'].'" src="'.IMAGE_SRC_URL."/props/".$return_thing_info[$info]['img'].'" />';
			$echo3 = '获得第'.$res['F_saolei_points'].'关物品:'.$return_thing_info[$info]['name'];
		}
	}
}
$echo .= '</tr></table>';
$echo .= "<Boundaries>";
$echo .=$echo2."<Boundaries>".$echo3."<Boundaries>".$sl_fhtime."<Boundaries>".$get_fh;
echo $echo;
realseLock();
function curl_post($url,$data,$port=80)
{
	$post = 1;
	$returntransfer = 1;
	$ch = curl_init();
	$options = array(	CURLOPT_URL => $url,
						CURLOPT_PORT => $port,
						CURLOPT_POST => $post,
						CURLOPT_POSTFIELDS => $data,
						CURLOPT_RETURNTRANSFER => $returntransfer,
						);
	curl_setopt_array($ch, $options);
	$result = curl_exec($ch);
	curl_close($ch);
	return $result;
}

?>
