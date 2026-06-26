<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %xueyuan%

*@Write Date: 2011.08.31
*@Update Date: /
*@Usage: 跨服战场报名页面
*请求后台公开接口
*/
require_once('../config/config.game.php');
require_once('../config/fight_zb_config.php');
require_once('../login/curl.php');
$interface = "http://pmmg1.webgame.com.cn/interface/kffight_bm.php";
$reskey = 'xueyuan';
//宠物分值
$score = array('zb'=>0,'czl'=>0,'luck'=>0,'group'=>0,'sx'=>0);

$petsAll = $_pm['user']->getUserPetById($_SESSION['id']);
$user = $_pm['user']->getUserById($_SESSION['id']);
$bag = $_pm['user']->getUserBagById($_SESSION['id']);

foreach($petsAll as $info)
{
	if($info['id'] == $user['mbid'])
	{
		$petinfo = $info;
		break;
	}
}
//宠物bb分值逻辑
$adv = ($petinfo['ac']*1.1 + $petinfo['mc']*1.05 + $petinfo['hp']*1 +$petinfo['hits'] * 0.95 +$petinfo['miss']*0.9+$petinfo['speed']*0.85)/6;
if(floatval($petinfo['czl']) > 1500)
{
	$score['group'] = 3;
	$score['sx'] = log($adv,2)==0?0:round(log($adv,2)/100+2,2);
	$score['czl'] = round(log($petinfo['czl'],3)/10,2);
}
elseif(floatval($petinfo['czl']) > 500)
{
	$score['group'] = 2;
	$score['sx'] = log($adv,3)==0?0:round(log($adv,3)/100+1,2);
	$score['czl'] = round(log($petinfo['czl'],3)/10,2);
}
else
{
	$score['group'] = 1;
	$score['sx'] = log($adv,4)==0?0:round(log($adv,4)/100,2);
	$score['czl'] = round(log($petinfo['czl'],3)/10,2);
}
$zb_info_m = explode(',',$petinfo['zb']);
foreach($zb_info_m as $info)
{
	$zb_info_m_arr = explode(':',$info);
	$zb_info[] = $zb_info_m_arr[1];
}
//装备分值
foreach($bag as $info)
{
	if($info['varyname'] == 9 && in_array($info['id'],$zb_info))
	{
		$zb_has = true;
		if(!empty($info['plus_tmes_eft']))
		{
			$qh_level = explode(',',$info['plus_tmes_eft']);
			$score['zb'] += ($qh_level[0]+1)*0.005;	//强化加分
		}
		$zb_need_info = $_pm['mysql'] -> getOneRecord(" SELECT propscolor,F_item_hole_info FROM props,userbag WHERE props.id=userbag.pid AND userbag.id = ".$info['id']);
		if(isset($zb_need_info['F_item_hole_info']) && !empty($zb_need_info['F_item_hole_info']) && $zb_need_info['F_item_hole_info'] != '')	//镶嵌有宝石
		{
			$stone_type = explode(':',$zb_need_info['F_item_hole_info']);
			$stone = $_pm['mysql']->getOneRecord(" SELECT name FROM props WHERE effect like '%".str_replace(array(':','%'),array('_','\%'),$zb_need_info['F_item_hole_info'])."%' AND varyname = 25 ");
			preg_match("/[0-9]+/",$stone['name'],$arr_level_e);
			$stone_mid = $arr_level_e[0]*0.04;	//宝石等级加分
			switch($stone_type[0])
			{
				case 'ac':
				{
					$score['zb'] += $stone_mid*1.1;break;
				}
				case 'crit':
				{
					$score['zb'] += $stone_mid*1.1;break;
				}
				case 'dxsh':
				{
					$score['zb'] += $stone_mid*1.05;break;
				}
				case 'shjs':
				{
					$score['zb'] += $stone_mid*1.05;break;
				}
				default:
				{
					$score['zb'] +=$stone_mid;break;
				}	
			}
		}
		switch($zb_need_info['propscolor'])	//颜色分值
		{
		 	case 1:
		 	{
				$score['zb'] += 0.1;break;
		 	}
		 	case 2:
		 	{
				$score['zb'] += 0.13;break;
		 	}
		 	case 3:
		 	{
				$score['zb'] += 0.15;break;
		 	}
		 	case 4:
		 	{
				$score['zb'] += 0.18;break;
		 	}
		 	case 5:
		 	{
				$score['zb'] += 0.25;break;
		 	}
		 	case 6:
		 	{
				$score['zb'] += 0.35;break;
		 	}
		}
		if( $info['series'] != '' && $info['series'] != '0')
		{
			$tz_info = explode(':',$info['series']);
			//$tz[$tz_info[0]]++;
			if(!in_array($tz_info[0],array('情殇','厄菲斯套装')))
			{
				$tz[$tz_info[0]][1]++;
			}
			else
			{
				$mid_array_qs = explode('|',$tz_info[1]);
				switch($mid_array_qs[0])
				{
					case 2905 :
					case 3126 :
					{
						$tz[$tz_info[0]][1]++;
						break;
					}
					case 1621 :
					case 1702 :
					{
						$tz[$tz_info[0]][2]++;
						break;
					}
					
				}
			}
		}
	}
}
if($zb_has)
{
	if(is_array($tz))
	{
		foreach($tz as $key => $val)
		{
			foreach($val as $k => $v)
			{
				if( isset($zb_config[$key][$k]) )
				{
					foreach($zb_config[$key][$k] as $ke => $va)
					{
						if($v >= $ke)
						{
							$ins = $va;
						}
					}
					$score['zb'] += $ins;
				}
			}
		}
	}
}
else
{
	$score['zb'] = 0;
}
//运气
$score['luck'] = rand(1,5);
$score['nickname'] = urlencode($_SESSION['nickname']);
$score['host'] = $_SERVER['HTTP_HOST'];
$score['time'] = time();
ksort($score);
foreach($score as $info)
{
	$sign .= $info; 
}
$sign .= $reskey;
$score['sign'] = md5($sign);
$a = curl_post($interface,$score);
switch($a)
{
	case 'error':
	{
		die("错误");
	}
	case 'has' :
	{
		die("已经报过名了");
	}
	case 'noopen' :
	{
		die("战场未开启");
	}
	case 'nobm':
	{
		die("战场报名未开启");
	}
	case 'ok':
	{
		die("报名成功,系统根据您宠物的成长自动为您分组,感谢您的参与");
	}
}

?>