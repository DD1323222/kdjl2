<?php
@session_start();
header('Content-Type:text/html;charset=utf-8');
error_reporting(0);

define("WG_CHECK",	0);

//die('服务器升级维护，预计时间1小时左右，请8点进入服务器！');
// Props base data
define(MEM_PROPS_KEY,			"db_props");

// Task base data.
define(MEM_TASK_KEY,			"db_task");

// Task base data.
define(MEM_SKILLSYS_KEY,		"db_skillsys");

// Memory order base data.
define(MEM_ORDER_KEY,			'db_memorder');

// Memory order base data.
define(MEM_GPC_KEY,				'db_gpc');

define(MEM_BB_KEY,				'db_bb');

// Memory map key
define(MEM_MAP_KEY,				'db_map');

// Memory online key
define(MEM_USER_LIST,			'userlist');

// Memory wx key
define(MEM_WX_KEY,				'db_wx');

// MEM EXP KEY
define(MEM_EXP_KEY,			    'db_exptolv');

define(MEM_SYSWORD_KEY,			'sysword');

define(MEM_TIME_KEY,			'db_timeconfig');

define(MEM_TIMENEW_KEY,			'db_timeconfignew');

define(MEM_AOYUN_KEY,			'db_aoyun');

define(MEM_EQUIP_KEY,			'db_equip');

define(MEM_GONGGAO_KEY,			'db_gonggao');

define(GAME_SERVER_FLAG,		'poke_2');
//	定义图片的路径
  define(IMAGE_SRC_URL,'../images');
// 定义用户数据
if (isset($_SESSION['id']) && $_SESSION['id']>0)
{
	define(MEM_USER_KEY,			$_SESSION['id']);
	define(MEM_USERBB_KEY,			$_SESSION['id'] . 'bb');
	define(MEM_USERSK_KEY,			$_SESSION['id'] . 'sk');
	define(MEM_USERBAG_KEY,		    $_SESSION['id'] . 'bag');
}
// Game Name
$_game['name']	=	'口袋妖怪';
// Game Version
$_game['version']	=	'v1';

// Game Keyword
$_game['keyword']   =	'';

// Game Desc
$_game['keydesc']	=	'';

$gameRoot = dirname(dirname(__FILE__)) . '/';
$_game['kernel']	=	$gameRoot . 'kernel/';
$_game['config']	=	$gameRoot . 'config/';
$_game['template']  =	$gameRoot . 'template/';
$_game['sec']  		=	$gameRoot . 'sec/';

$_mem['host']		=	'127.0.0.1';
$_mem['port']		=	'11211';

//Load mysql config.
require_once($_game['config'] . 'config.mysql.php');
require_once($_game['config'] . 'config.bbword.php');
require_once($_game['config'] . 'config.skill.php');
require_once($_game['config'] . 'config.gpc.php');
require_once($_game['config'] . 'config.pets.php');
require_once($_game['config'] . 'config.props.php');
require_once($_game['config'] . 'config.task.php');
require_once($_game['config'] . 'config.fuben.php');
require_once($_game['config'] . 'config.battle.php');
require_once($_game['config'] . 'config.function.php');//我自己加的
require_once($_game['sec'] . 'sec_common_fnc.php');


$_pm['mysql']	= new instance('mysql');
$_pm['mem']	= new instance('memory');
$_pm['user']	= new instance('user');
$_pm['equipment']=new instance('equipment');

// auto config .
function __autoload($class_name) {
	global $_game;
    require_once $_game['kernel']. $class_name . '.'.$_game['version'].'.php';
}

?>
