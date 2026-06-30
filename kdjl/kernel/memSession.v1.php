<?php 
//设定 SESSION 有效时间，单位是 秒
if(!isset($_game['version'])){
	// Game Name
	$_game['name']	=	'口袋妖怪';
	
	// Game Version
	$_game['version']	=	'v1';
	
	// Game Keyword
	$_game['keyword']   =	'';
	
	// Game Desc
	$_game['keydesc']	=	'';
	
	$_game['kernel']	=	$_SERVER['DOCUMENT_ROOT'].'/kernel/';
	$_game['config']	=	$_SERVER['DOCUMENT_ROOT'].'/config/';
	$_game['template']  =	$_SERVER['DOCUMENT_ROOT'].'/template/';
	$_game['sec']  		=	$_SERVER['DOCUMENT_ROOT'].'/sec/';
	
	$_mem['host']		=	'127.0.0.1';
	$_mem['port']		=	'11211';
}

define('SESS_LIFTTIME', 3600);
//定义memcache配置信息
define('MEMCACHE_HOST', $_mem['host']);
define('MEMCACHE_PORT', $_mem['port']);

if (!class_exists('memSession'))
{
	class memSession{
		static  $mSessSavePath;
		static  $mSessName;
		static  $mMemcacheObj;
		public function __construct()
		{
			if (!class_exists('Memcache') || !function_exists('memcache_connect'))
			{
				die('Memcache extension not exists!');
			}
	
			if (!empty(self::$mMemcacheObj) && is_object(self::$mMemcacheObj))
			{
				return false;
			}
			self::$mMemcacheObj = new Memcache;
			if (!self::$mMemcacheObj->connect(MEMCACHE_HOST , MEMCACHE_PORT))
			{
				die('Fatal Error: Can not connect to memcache host '. MEMCACHE_HOST .':'. MEMCACHE_PORT);
			}
			return TRUE;
		}

		public static function sessOpen($pSavePath = '', $pSessName = '')
		{
			self::$mSessSavePath    = $pSavePath;
			self::$mSessName        = $pSessName;
			return TRUE;
		}
		
		public static function sessClose()
		{
			return TRUE;
		}
		
		public static function sessRead($wSessId = '')
		{
			if(strlen($wSessId) == 0) return;

			$wData = self::$mMemcacheObj->get($wSessId);
			//先读数据，如果没有，就初始化一个
			if (!empty($wData))
			{
				return $wData;
			}
			else
			{
				//初始化一条空记录
				$ret = self::$mMemcacheObj->set($wSessId, '', 0, SESS_LIFTTIME);
	
				if (TRUE != $ret)
				{
					die("Session error(".__LINE__.")!");
	
					return FALSE;
				}
	
				return TRUE;
			}
		}
		
		public static function sessWrite($wSessId = '', $wData = '')
		{
			$ret = self::$mMemcacheObj->replace($wSessId, $wData, 0, SESS_LIFTTIME);
			if (TRUE != $ret)
			{
				die("Fatal Error: SessionID $wSessId Save data failed!");
	
				return FALSE;
			}
			return TRUE;
		}
		
		public static function sessDestroy($wSessId = '')
		{
			self::sessWrite($wSessId);
	
			return FALSE;
		}
		
		public static function sessGc()
		{
			//无需额外回收,memcache有自己的过期回收机制
	
			return TRUE;
		}
		
		public function initSess()
		{
			//$domain = '.imysql.cn';
			//不使用 GET/POST 变量方式
			//ini_set('session.use_trans_sid',    0);
	
			//设置垃圾回收最大生存时间
			//ini_set('session.gc_maxlifetime',   SESS_LIFTTIME);
	
			//使用 COOKIE 保存 SESSION ID 的方式
			//ini_set('session.use_cookies',      1);
			//ini_set('session.cookie_path',      '/');
	
			//多主机共享保存 SESSION ID 的 COOKIE
			//ini_set('session.cookie_domain',    $domain);
	
			//将 session.save_handler 设置为 user，而不是默认的 files
			//session_module_name('memcache');
			session_write_close();
			session_module_name('memcache');
			//定义 SESSION 各项操作所对应的方法名：
			session_set_save_handler(
					array('memSession', 'sessOpen'),   //对应于静态方法 My_Sess::open()，下同。
					array('memSession', 'sessClose'),
					array('memSession', 'sessRead'),
					array('memSession', 'sessWrite'),
					array('memSession', 'sessDestroy'),
					array('memSession', 'sessGc')
					);
	
			session_start();			
			return TRUE;
		}
		// }}}
	
	}//end class
	$memSess    = new memSession;
	$memSess->initSess();

}//end define

?>
