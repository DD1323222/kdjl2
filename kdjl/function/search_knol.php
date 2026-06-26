<?php
/**
 * search pocket knowledge from the knowledge server
 * it will handle for knowledge bugs posting transaction and title autocomplete
 * while searching knowledge
 *
 * @author Zheng.Ping
 * @date 2009-05-08
 */



require_once('../config/config.game.php');
require_once('../config/config.baike.php');
secStart($_pm['mem']);


/*
define('KNOL_SERVER_TEST_ADDR',   'http://pmtest.webgame.com.cn/baike/knol_ac.php');
define('KNOL_SERVER_SEARCH_ADDR', 'http://pmtest.webgame.com.cn/baike/search_knol.php');
define('KNOL_SERVER_SUBMIT_ADDR', 'http://pmtest.webgame.com.cn/baike/receive_poke_knol_bug.php');
define('KNOL_CACHE_TIME', 60 * 1);
 */
define('KNOL_SERVER_TEST_ADDR',   sprintf("%s/knol_ac.php", BAIKE_SERVER_URL));
define('KNOL_SERVER_SEARCH_ADDR', sprintf("%s/search_knol.php", BAIKE_SERVER_URL));
define('KNOL_SERVER_SUBMIT_ADDR', sprintf("%s/receive_poke_knol_bug.php", BAIKE_SERVER_URL));


/**
 * class for handle memory cache of knowledge
 */
class PokeKnolMemory extends memory
{
    /**
     * @var object $memHandler
     */
    private static $memHandler = null;

    /**
     * @var string $storePrefix
     */
    private static $storePrefix = 'd91670c1';

    /**
     * @var predefined constant
     */
    private static $storeFlag = MEMCACHE_COMPRESSED;
    //private static $storeFlag = false;

    /**
     * @var integer $memExpire
     */
    private static $memExpire = KNOL_CACHE_TIME;
    
    /**
     * constructor
     */
    public function __construct() {
        if (self::$memHandler === null) {
            parent::__construct();
            self::$memHandler = $this->getHandle();
        }
    }

    /**
     * destructor
     */
    public function __destruct() {
        if (self::$memHandler) {
            //self::$memHandler->close();
        }
    }

    /**
     * get the instance of the class
     */
    public static function getInstance() {
        if (self::$memHandler === null) {
            //self::__construct();
            //parent::__construct();
            //self::$memHandler = $this->getHandle();
        }
        return new self;
    }

    /**
     * put to the knowledge content to memcache
     *
     * @param string $key
     * @param string $content
     * @return boolean
     */
    public function putContentToCache($key, $content) {
        if (self::$memHandler) {
            $storeKey = sprintf("%s_%s", self::$storePrefix/* $key */, $key);
            //echo "put content to cache. $storeKey <br />";

            return self::$memHandler->set($storeKey /* $key */, $content, 
                self::$storeFlag, self::$memExpire);
        }

        return false;
    }

    /**
     * get content from memcache
     *
     * @param string $key
     * @return stirng or false on failure
     */
    public function getContentFromCache($key) {
        if (self::$memHandler) {
            $storeKey = self::$storePrefix . '_' . $key;
            //echo "get content from cache. $storeKey <br />";
            return self::$memHandler->get($storeKey, self::$storeFlag);
        }

        return false;
    }
}


/**
 * search pocket knowledge from remote server
 * there are two ways to search the knowledge
 * 1. search by knowledge's title
 * 2. search by knowledge's id
 *
 * @param string $title
 * @param integer $id
 * @return sting or false on failure
 */
function get_knol_from_remote($title, $id) {
    $server_url = false;

    //$test_res = @file_get_contents(KNOL_SERVER_TEST_ADDR);
	$url = KNOL_SERVER_TEST_ADDR;
	$test_res = curl($url);
    if ($test_res != '202') {
        // the remote server can not be accessed.
        return false;
    }
    if ($title != '') {
        $server_url = KNOL_SERVER_SEARCH_ADDR . sprintf("?key=%s", $title);
    } elseif ($id > 0) {
        $server_url = KNOL_SERVER_SEARCH_ADDR . sprintf("?id=%d", $id);
        if (isset($_GET['check']) && $_GET['check'] == 'tag') {
            // get the tag informatin
            $server_url .= '&check=tag';
        }
    } else {
        return false;
    }
    //return @file_get_contents($server_url);
	return curl($server_url);
}

/**
 * search pocket knowledge from memory
 * there are two ways to search the knowledge
 * 1. search by knowledge's title
 * 2. search by knowledge's id
 *
 * @param string $title
 * @param integer $id
 * @return sting or false on failure
 */
function get_knol_from_cache($title, $id) {
    $key = '';

    if (strlen($title) > 0) {
        $key = $title;
    } elseif ($id > 0) {
        if (isset($_GET['check']) && $_GET['check'] == 'tag') {
            $key = sprintf("tag_%d", $id);
        } else {
            $key = $id;
        }
    }

    if ($key) {
        $knolMem = PokeKnolMemory::getInstance();
        //$knolMem = new PokeKnolMemory();
        return $knolMem->getContentFromCache($key); 
    }
    return false;
}


/**
 * put the knowledge content to cache
 *
 * @param string  $content 
 * @param string  $title
 * @param integer $id
 * @return false
 */
function put_knol_to_cache($content, $title, $id) {
    $key = '';

    if (strlen($title) > 0) {
        $key = $title;
    } elseif ($id > 0) {
        if (isset($_GET['check']) && $_GET['check'] == 'tag') {
            $key = sprintf("tag_%d", $id);
        } else {
            $key = $id;
        }
    }

    if ($key) {
        $knolMem = PokeKnolMemory::getInstance();
        return $knolMem->putContentToCache($key, $content); 
    }
    return false;
}

/**
 * search the id of pocket knowledge from remote server
 *
 * @param string $title
 * @return string or false on failure
 */
function get_knol_id_from_remote_by_title($title) {
    $server_url = false;

    if ($title != '') {
        $server_url = KNOL_SERVER_SEARCH_ADDR . sprintf("?key=%s&ask_exist=query_title", $title);
    } else {
        return false;
    }

    //return @file_get_contents($server_url);
	return curl($server_url);
}

/**
 * search the id of pocket knowledge from remote server
 *
 * @param integer $id
 * @return string or false on failure
 */
function get_knol_id_from_remote_by_id($id) {
    $server_url = false;

    if (intval($id) > 0) {
        $server_url = KNOL_SERVER_SEARCH_ADDR . sprintf("?id=%d&ask_exist=query_id", $id);
    } else {
        return false;
    }

    //return @file_get_contents($server_url);
	return curl($server_url);
}

/**
 * generate bug reporting parameter
 *
 * @param array $post_array
 * @return array or false on failure
 */
function get_bug_reporting_params($post_array) {
    $ret = array();

    if (!isset($post_array['knol_title'])) {
        return false;
    }
    if (!isset($post_array['knol_id'])) {
        return false;
    }
    if (!isset($post_array['knol_content'])) {
        return false;
    }
    if (!isset($post_array['host']) || trim($post_array['host']) == '') {
        return false;
    }
    if (!isset($post_array['player']) || trim($post_array['player']) == '') {
        return false;
    }
    if (!isset($post_array['nickname']) || trim($post_array['nickname']) == '') {
        return false;
    }
    $search_title = trim($post_array['knol_title']);
    $knol_id = intval($post_array['knol_id']);
    $knol_content = strip_tags(trim($post_array['knol_content']));
    if ($search_title == '' && $knol_id == 0) {
        return false;
    }
    if ($knol_content == '') {
        return false;
    }
    $ret['knol_title'] = iconv('UTF-8', 'gbk', $search_title);
    $ret['knol_id'] = $knol_id;
    $ret['knol_content'] = iconv('UTF-8', 'gbk', $knol_content);
    $ret['host'] = trim($post_array['host']);
    $ret['player'] = trim($post_array['player']);
    $ret['nickname'] = iconv('UTF-8', 'gbk', trim($post_array['nickname']));
    $ret['client_addr'] = $_SERVER['REMOTE_ADDR'];

    return $ret;
}

/**
 * report knowlede bugs to remote server through cURL library
 *
 * @param array $post_array
 * @return boolean
 */
function post_knol_bug_to_remote_server($post_array)
{

    $ret = false;

    if (!empty($post_array)) {
        $url = KNOL_SERVER_SUBMIT_ADDR;

        /*foreach ($post_array as $k => $v) {
            $post_param[] = $k . '=' . $v;
        }
        $post_fields = implode('&', $post_param);*/
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_array);
        ob_start();
        /* $ret = */ curl_exec($ch);
        curl_close($ch);
        $ret = ob_get_contents();
        ob_end_clean();
    }

    return $ret;
}




// handle for ajax request
// submit the bug reporting of the knowledge to remote server
if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])
    && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    $bug_report_param = get_bug_reporting_params($_POST); 
    header('Content-Type:text/html;charset=GBK');

    try {
        if ($_GET['acKey'] && trim($_GET['acKey']) == 'searchTitle') {
            // search the key words of the title, return the matched title list
            // this response is used for autocomplete
            $titleKey = isset($_GET['q']) ? trim($_GET['q']) : '';
            if (strlen($titleKey) > 0) {
                if (!isset($_GET['m'])) {
                    $titleKey = iconv('UTF-8', 'gbk', $titleKey);
                }
                $titleSearchURL = KNOL_SERVER_SEARCH_ADDR . '?acTitle=' . $titleKey;
                if (isset($_GET['m'])) {
                    $titleSearchURL .= '&m=prototype';
                }
                //$tList = file_get_contents($titleSearchURL);
				$tList = curl($titleSearchURL);
                echo $tList;
            }

            exit();
        }

        if ($bug_report_param === false) {
            //die('{code:1, msg:"不好意思，不能提交你的建议!"}');
            throw new Exception("不好意思，不能提交你的建议!", 2);
        } elseif (!extension_loaded('curl')) {
            throw new Exception("不好意思，不能提交你的建议!", 2);
        } elseif ($bug_report_param['knol_id'] == 0) {
            $knol_id = get_knol_id_from_remote_by_title($bug_report_param['knol_title']);
            if ($knol_id > 0) {
                $bug_report_param['knol_id'] = intval($knol_id);
            }
        } elseif ($bug_report_param['knol_id'] > 0) {
            $knol_id = get_knol_id_from_remote_by_id($bug_report_param['knol_id']);
            if ($knol_id != $bug_report_param['knol_id']) {
                throw new Exception("不好意思，不能提交你的建议!", 2);
            }
        }
    } catch (Exception $e) {
        echo json_encode(array('code' => $e->getCode(),
            'msg' => iconv('gbk', 'UTF-8', $e->getMessage())));
        //echo json_encode(array('code' => $e->getCode(), 'msg' => '不好意思，不能提交你的建议!'));
    }

    $res = post_knol_bug_to_remote_server($bug_report_param); //echo '{code:1, msg:"'.$res.'"}';
    if ($res == 'Add Success') {
        echo '{code:1, msg:"谢谢您的举报，我们将稍后进行校正!"}';
    } else {
        echo '{code:2, msg:"举报失败!"}';
        //echo '{code:2, msg:"举报失败!' . $res . '"}';
    }
    // ajax should exit at here
    exit();
}

// handle for searching knoledge
$search_key = isset($_GET['key']) ? trim($_GET['key']) : '';
$search_id  = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($search_key == '' && $search_id <= 0) {
    die('未找到相关词条!');
} elseif ($search_key == '口袋百科') {
    // if the user search the special word
    // load the index page of the knowledge
    $search_key = '';
    $search_id = 1;
}

// try to get content from memory
$knol = get_knol_from_cache($search_key, &$search_id); 
$knol = get_knol_from_remote($search_key, &$search_id); 

preg_match("/#key_id(\d+)#/",$knol,$outsearch_id);
if(!$out)
{
	$knol = false;
}

if ($knol === false) {

    $knol = get_knol_from_remote($search_key, &$search_id); 
	preg_match("/#key_id(\d+)#/",$knol,$outsearch_id);

	//var_dump($knol);
    if ($knol !== false && !in_array($knol, array('未找到相关词条!', 'Server Error!'))) {
        // put the content to memory
        $ret = put_knol_to_cache($knol, $search_key, $search_id);
        //var_dump($ret);
    } elseif ($knol === false || $knol == 'Server Error!') {
        $knol = '请等待，百科服务稍后开放!';
    }
} else {
    //echo 'get content from memory!<br />';
}
if($outsearch_id&&$search_id<1)
{
	$search_id = $outsearch_id[1];
}

if ($knol !== false) {
    $content = html_entity_decode($knol);

    //@Load template.
    $tn = $_game['template'] . 'tpl_search_knol.html';
    if (file_exists($tn))
    {
       $tpl = @file_get_contents($tn);
        $src = array('#knol#', '#search_key#', '#search_id#', '#player_name#', '#player_nick#');
        $des = array($content, $search_key, $search_id, $_SESSION['username'], $_SESSION['nickname']);
        $res = str_replace($src, $des, $tpl);
        // gzip echo. if maybe.
        ob_start('ob_gzip');
        echo $res;
        ob_end_flush();
    }
}
function curlS($url,$port=80){
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
?>
