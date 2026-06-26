<?php
set_time_limit(0);
if (strpos(',' . ini_get('disable_functions') . ',', ',system,') === false) {
    system("ulimit -n 3096");
}

//ini_set('display_errors',false);
if(!checkClose())
{
    die("Switch off!\r\n");
}
require_once('config.socket.php');
$addr = "0.0.0.0";
$port = $socket_port;
define('EC',chr(0));
$lastUpdateTeamList = 0;
define('TEAM_LIST_KEY','MEM_TEAM_LIST');//组队成员信息内存键
define('GUILD_LIST_KEY','MEM_GUILD_LIST');//组队成员信息内存键
define('TEAM_LEFT_MEMS','TEAM_LEFT_MEMS');//断线的人内存键
define('SINGLE_SERVER_MODLE',true);//单socket服务器模式

$remoteIP = "";
$remotePort = "";
$lastupDateUserOnline=0;



require_once(dirname(dirname(__DIR__)).'/config/config.mysql.php');
error_reporting(E_ALL);
$conn = NULL;//数据库连接
$mem = NULL;//内存连接
$teams = array();//从内存里面读出来的组队成员信息
connect();
memConnect();//连接内存服务器

$userid2nickname = array();//

$loginErrCount = array();//登陆错误次数计数
$loginIpList = array();//登陆ip记录
$maxConnectionOfAnIP = 60;//单ip允许最大连接数

$socketWriteFailed = array();//socket写失败次数记录
$sn2user = array();//
$nickname2sn = array();


$doOtherWorkLog = array();//多服模式发送记录
$usePcntlFork = false;//是否允许使用多进程
define('maxForkThreadNum',50);//最多允许同时打开的进程数
$currentThreadNum = 0;//当前的进程数
$currentThreadPids = array();//当前打开的进程pid

define('PCNTLFLAG', $usePcntlFork&&function_exists('pcntl_fork'));//多进程是否可用
define('PROCESS_RECYLE_TIME', 60);//进程回收时间
//define('ChildThreadMemKey','ChildThreadMemKeyOfPcntlFork');
//memSet(ChildThreadMemKey,0);

$lastupDateUserOnline = 0;//最后更新用户在线时间的时间
define('OnlineTimeForUserKey','OnlineUsers');//在线用户最后在线时间内存键
$totalBytesSend = 0;//总发送字节数
$totalBytesRecv = 0;//总接收字节数

//session_save_path('/tmp');

$socket = socket_create(AF_INET,SOCK_STREAM,SOL_TCP);

socket_set_option($socket,SOL_SOCKET, SO_SNDTIMEO,  array(		   "sec"=>0, 		   "usec"=>250000  		   )		  );
socket_set_option($socket,SOL_SOCKET, SO_RCVTIMEO,  array(		   "sec"=>0, 		   "usec"=>250000  		   )		  );
socket_set_option($socket,SOL_SOCKET,SO_REUSEADDR,1);

if($socket < 0) {
    echo "Socket create:".$socket_strerror($socket)."\n";
    exit;
}
if (! ($ret = @socket_bind($socket, $addr, $port)) ) {
    if(isset($argv[4]))
    {
        echo "//AS\r\n".("Couldn't create socket, error code is: ".socket_last_error().",error message is: ".socket_strerror(socket_last_error()))."\r\n";

        $cmd = "netstat -an -o | grep ".$port;
        $output = NULL;
        exec( $cmd, $output , $return_var);
        echo "
		-----------------------------------------------		
		$cmd		
		<pre>";
        var_dump($output, $return_var);
        echo "/<pre>";
    }
    //echo "//AS!\n";
    exit;
}

if ( ($ret = socket_listen($socket, 5)) < 0 ) {
    echo "socket listen:".socket_strerror()."\n";
    exit;
}

memSet('SNB_ON',$server_ip.'_'.date("Y-m-d H:i:s"));
$configuration = array();
getConfiguration();
wr('<font color="#f00">This is new version! '.'SNB_ON'.$server_ip.'_'.date("Y-m-d H:i:s")."</font>");

socket_set_nonblock($socket);

echo 'Server start at: '.date('Y-m-d H:i:s').', PCNTLFLAG='.(PCNTLFLAG?'true':'false')."\r\n";
echo "Wainting for a connection at $port:\n";

$lastDoOtherWorkTime = 0;

$clients = array($socket);

while(true) {
    $read = $clients;

    checkClose();
    upDateUserOnline();
    if($currentThreadNum>maxForkThreadNum)
    {
        recylePid();
    }

    if(@socket_select($read, $writes=NULL, $execs=NULL, 2) < 1){
        recylePid();
        //mEcho(".");
        continue;
    }

    if(in_array($socket,$read)) {
        $newsock = socket_accept($socket);
        socket_set_nonblock($newsock);
        socket_getpeername($newsock,$remoteIP,$remotePort);
        $userSN = $remoteIP.'_'.$remotePort;
        $clients[$userSN] = $newsock;

        $loginIpList[$remoteIP] = isset($loginIpList[$remoteIP])?$loginIpList[$remoteIP]+1:1;
        if($loginIpList[$remoteIP]>$maxConnectionOfAnIP)
        {
            //更新、调整IP登陆数!
            $tmpOfIP = array();
            foreach($read as $read_sock0) {
                @socket_getpeername($read_sock0,$remoteIP0,$remotePort0);
                if(isset($tmpOfIP[$remoteIP0])){
                    $tmpOfIP[$remoteIP0] ++;
                }else{
                    $tmpOfIP[$remoteIP0] = 1;
                }
            }
            $loginIpList[$remoteIP]--;
            socket_write($newsock,'SYSM|您的IP与服务器连接数过大!');
            wr($remoteIP.":".$remotePort."(".$loginIpList[$remoteIP].'-'.$tmpOfIP[$remoteIP].") was refused to conect in.\r\n",0);
            $loginIpList = $tmpOfIP;
            unset($clients[$userSN]);
            unset($read[$userSN]);
            socket_close($newsock);
        }

        $key = array_search($socket,$read);
        unset($read[$key]);
        //echo $remoteIP.":".$remotePort." conected in.\r\n";
    }

    foreach($read as $read_sock) {
        $data = @socket_read($read_sock,8192,PHP_BINARY_READ);
        @socket_getpeername($read_sock,$remoteIP,$remotePort);
        $userSN = $remoteIP.'_'.$remotePort;
        if($data == false) {
            $key = array_search($read_sock,$clients);
            $user2snKey = "";
            if(isset($sn2user[$key])){
                $user2snKey=$sn2user[$key]['name'];

                $sql='update player_ext set onlinetime=onlinetime+'.(time()-$sn2user[$key]['loginTime']).' where uid='.$sn2user[$key]['id'];
                query($sql);
                //echo $user2snKey." left\r\n";
                //wrt('*****************************$user2snKey(username)='.$user2snKey.',$nickname2sn[$user2snKey]='.$nickname2sn[$user2snKey].' deleted, and $userid2nickname[$sn2user[$key][\'id\']]='.$userid2nickname[$sn2user[$key]['id']].".");
                unset($nickname2sn[$user2snKey]);
                //加入掉线列表
                $old = memGet(TEAM_LEFT_MEMS);
                if(!is_array($old)) $old = array();
                $old[$sn2user[$key]['id']]=$sn2user[$key]['id'];
                memSet(TEAM_LEFT_MEMS,$old);
                sendMsg('__ALL__','UL|'.$user2snKey);
                unset($userid2nickname[$sn2user[$key]['id']]);
                unset($sn2user[$key]);
            }

            $loginIpList[$remoteIP]--;
            unset($clients[$key]);
            socket_close($read_sock);
            //userListRefreshMessage();
            continue;
        }
        $totalBytesRecv += strlen($data);
        if (preg_match("/Sec-WebSocket-Key:\s*(.*?)\r\n/i",$data,$match)) {
            $webSocketKey = trim($match[1]);
            $res= "HTTP/1.1 101 Switching Protocols\r\n"
                ."Upgrade: websocket\r\n"
                ."Connection: Upgrade\r\n"
                ."Sec-WebSocket-Accept: " .base64_encode(sha1($webSocketKey."258EAFA5-E914-47DA-95CA-C5AB0DC85B11", true))."\r\n\r\n"; // 注意这里，需要两个换行
            socket_write($read_sock, $res, strlen($res));
            continue;
        }
        if(substr($data,0,1)!=chr(1)){
            $decoded = decode($data);
            if($decoded === false || $decoded === ''){
                continue;
            }
            $data=$decoded;
        }
        proceedMsg($userSN,$data);
    }
}
socket_close($socket);
//检查是否被要求关闭

function frame($s) {
    if(strlen($s)<126)
    {
        return "\x81" . chr(strlen($s)) . $s;
    }else if(strlen($s)<0xffff){
        return "\x81" . chr(126) . chr(strlen($s) >>8) . chr(strlen($s) & 0xff) .$s;
    }else{
        return "\x81" . chr(127) . pack('N', 0) . pack('N', strlen($s)) . $s;
    }
}

function decode($buffer)  {
    $bufferLen = strlen($buffer);
    if($bufferLen < 2) return false;

    $firstByte = ord($buffer[0]);
    $opcode = $firstByte & 0x0f;
    if($opcode === 8) return false; // close frame
    if($opcode !== 1 && $opcode !== 2 && $opcode !== 0) return false;

    $secondByte = ord($buffer[1]);
    $isMasked = ($secondByte & 0x80) === 0x80;
    $len = $secondByte & 127;
    $offset = 2;

    if ($len === 126)  {
        if($bufferLen < 4) return false;
        $len = (ord($buffer[2]) << 8) + ord($buffer[3]);
        $offset = 4;
    } else if ($len === 127)  {
        if($bufferLen < 10) return false;
        $len = 0;
        for($i=2; $i<10; $i++){
            $len = ($len * 256) + ord($buffer[$i]);
        }
        $offset = 10;
    }

    if($isMasked){
        if($bufferLen < $offset + 4) return false;
        $masks = substr($buffer, $offset, 4);
        $offset += 4;
    } else {
        $masks = '';
    }

    if($bufferLen < $offset + $len) return false;
    $data = substr($buffer, $offset, $len);
    if(!$isMasked) return $data;

    $decoded = '';
    for ($index = 0; $index < $len; $index++) {
        $decoded .= $data[$index] ^ $masks[$index % 4];
    }
    return $decoded;
}

function checkClose()
{
    global $clients;
    $content = @file_get_contents(dirname(__FILE__).'/Switch.txt');
    if(strpos($content,'off')!==false)//||date("Hi")=='2359')
    {
        if(!$clients){
            exit("配置不允许服务器开启！");
        }
        if(date("Hi")=='2359'){
            sendMsg('__ALL__',"SYSI|服务器即将重起，此期间您将无法正常聊天，天梯魔塔也无法正常战斗，需要大约三分钟，请三分钟后刷新页面，带来的不便，敬请原谅！");
        }else{
            sendMsg('__ALL__',"SYSI|服务器即将关闭，稍后会自动连接，带来的不便，敬请原谅！");
        }
        sleep(5);
        foreach($clients as $k => $v)
        {
            socket_close($clients[$k]);
        }
        recylePid(true);
        exit;
    }else{
        return true;
    }
}

//为新登陆用户准备在线人员列表
function userListRefreshMessage($userSN)
{
    global $nickname2sn,$clients;
    $msg = frame('L|'.implode('|',array_keys($nickname2sn)).EC);
    //$msg = 'L|所有人';
    socket_write($clients[$userSN], $msg,strlen($msg));
}

//找出用户所在的组的成员
function getGroupMembers($user)
{
    global $lastUpdateTeamList,$mem,$userid2nickname,$sn2user,$nickname2sn,$teams;
    $userInfo = $sn2user[$user];

    $nowmicrotime = microtime_float();
    if($nowmicrotime-$lastUpdateTeamList>2){//每3秒只读一次内存
        $teams = memGet(TEAM_LIST_KEY);
        if(!$teams){
            mEcho('Team member info '.TEAM_LIST_KEY.' not found in memcache!'."\r\n");
        }
        $lastUpdateTeamList = $nowmicrotime;
    }

    if(!$teams){
        mEcho('Team member info '.TEAM_LIST_KEY.' not found in memcache!'."\r\n");
        return array();
    }

    $teamMemberIds = array();
    foreach($teams as $arr)
    {

        if(!empty($arr))
        {
        }else{
            continue;
        }

        if(in_array($userInfo['id'],$arr))
        {
            $teamMemberIds = $arr;
            break;
        }
    }
    $tmp =array();
    foreach($teamMemberIds as $mbid)
    {
        if(isset($userid2nickname[$mbid]))
            $tmp[] = $userid2nickname[$mbid];
    }
    return $tmp;
}
//找出用户所在的组的成员
function getGuildMembers($user)
{
    global $lastUpdateTeamList,$mem,$userid2nickname,$sn2user,$nickname2sn,$teams;
    $userInfo = $sn2user[$user];

    $nowmicrotime = microtime_float();
    if($nowmicrotime-$lastUpdateTeamList>2){//每3秒只读一次内存
        $teams = memGet(GUILD_LIST_KEY);
        if(!$teams){
            mEcho('Team member info '.GUILD_LIST_KEY.' not found in memcache!'."\r\n");
        }
        $lastUpdateTeamList = $nowmicrotime;
    }

    if(!$teams){
        mEcho('Team member info '.GUILD_LIST_KEY.' not found in memcache!'."\r\n");
        return array();
    }
    $teamMemberIds = array();

    foreach($teams as $arr)
    {
        if(!empty($arr))
        {
        }else{
            continue;
        }

        if(in_array($userInfo['id'],$arr))
        {
            $teamMemberIds = $arr;
            break;
        }
    }

    $tmp =array();
    foreach($teamMemberIds as $mbid)
    {
        if(isset($userid2nickname[$mbid]))
            $tmp[] = $userid2nickname[$mbid];
    }
    return $tmp;
}
//回收子进程
function recylePid($flag=false)
{
    global $currentThreadNum,$currentThreadPids,$pwd;
    $pwd = md5(date("Ymd").PWD);
    $time = time();
    $stime = microtime_float();
    foreach($currentThreadPids as $k=>$v){
        if($time-$v>PROCESS_RECYLE_TIME||$flag){
            if(!posix_kill($k,9)){//杀死再回收
                echo "Kill $k failed!\r\n";
            }
            pcntl_waitpid($k,$status);
            unset($currentThreadPids[$k]);
            $currentThreadNum--;
        }
    }
    return;
}

function upDateUserOnline()
{
    global $lastupDateUserOnline,$configuration;
    $minute = date("i");
    if($lastupDateUserOnline!=$minute)
    {
        if($minute%5==0)
        {
            getConfiguration();
        }

        doGongGao($configuration);
        $lastupDateUserOnline=$minute;
    }
}
//发送公告
function doGongGao(&$configuration)
{
    $dt = date("YmdHi");
    $minute = round(time()/60);
    if(!isset($configuration['gonggao'])||!is_array($configuration['gonggao']))
    {
        return;
    }
    foreach($configuration['gonggao'] as $k=>$row)
    {
        if($row['endtime']<$dt)//过期了
        {
            unset($configuration['gonggao'][$k]);
        }else if($row['starttime']<=$dt&&date("i")%$row['times']==0){
            $msg = 'SYSI|'.$row['msg'];
            sendMsg('__ALL__',$msg);
        }
    }
}

//实际执行更新用户在线
function doUpDateUserOnline(&$userid2nickname)
{
    $users = memGet(OnlineTimeForUserKey);

    $time = time();
    foreach($userid2nickname as $k=>$v)
    {
        if(isset($users[$k])) $users[$k] = $time;
    }
    memSet(OnlineTimeForUserKey, $users);
}

//检查是否有其它需要socket发送的信息
function callDoOtherWork()
{
    global $lastDoOtherWorkTime,$currentThreadNum,$currentThreadPids,$userid2nickname,$nickname2sn;
    if( time() == $lastDoOtherWorkTime)
    {
        mEcho( "|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx--+--xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx|");
        return;
    }
    $memKey = 'ttmt_data_notice';
    $noticeData=memGet($memKey);
    if(!is_array($noticeData)||empty($noticeData))
    {
        return;
    }
    $time = time();
    $needToSend = array();
    if(!SINGLE_SERVER_MODLE){
        global $doOtherWorkLog;

        foreach($noticeData as $userId=>$v)
        {
            if(!isset($doOtherWorkLog[$userId]))
            {
                $doOtherWorkLog[$userId] = array($v, $time);
                $needToSend[$userId] = $v;
            }else if($doOtherWorkLog[$userId][0]!= $v){
                $echo = $userId.' :> '.$doOtherWorkLog[$userId][0].' + '.$v.'=>|';
                if(strlen($doOtherWorkLog[$userId][0])>strlen($v)||(strlen($doOtherWorkLog[$userId][0])==strlen($v)&&$v!=$doOtherWorkLog[$userId][0])){
                    $new = $v;
                }else{
                    if(strpos($v, $doOtherWorkLog[$userId][0]) !== false){
                        $new = substr($v,strlen($doOtherWorkLog[$userId][0]));
                    }else{
                        $new = $v;
                    }
                }
                $doOtherWorkLog[$userId]=array($v, $time);
                if(!empty($new)){
                    $needToSend[$userId] = $new;
                }
                mEcho( "|-------------------new-------------------------|");
                mEcho( $echo.$new);
            }else if($time - $doOtherWorkLog[$userId][1]>30){
                mEcho( date("Y-m-d H:i:s",$time).' -> clear data: '. $userId.'=>'.$v.' created at: '.date("Y-m-d H:i:s",$doOtherWorkLog[$userId][1])."");
                unset($doOtherWorkLog[$userId]);
                unset($noticeData[$userId]);
            }
        }

        if($time%60<3)
        {
            foreach($doOtherWorkLog as $k=>$v)
            {
                if($time-$v[1]>45)
                {
                    mEcho( '%%%%%%%%%%%%%%$doOtherWorkLog['.$k.']'."  Cleeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeear!");
                    unset($doOtherWorkLog[$k]);
                }
            }
        }
        memSet($memKey,$noticeData);
    }else{
        $needToSend = $noticeData;
        memSet($memKey,array());
    }

    if(empty($needToSend)){
        mEcho( "|---------------------  NOTHING...   --------------------|");
        return;
    }

    mEcho( "|----------------A-needToSend-A--------------------------|".print_r($needToSend,1));
    mEcho( "|----------------B-needToSend-B--------------------------|");


    $lastDoOtherWorkTime = $time;
    $sentByPcntl = false;
    if(PCNTLFLAG)
    {
        $pid = pcntl_fork();
        if ($pid == -1) {

        } else if ($pid) {
            mEcho( 'DotherWork forked '.$pid);
            $sentByPcntl = true;
            $currentThreadNum++;
            $currentThreadPids[$pid]=$time;
        } else {
            $sentByPcntl = true;
            mEcho( "|----------------Will Send By Fork--------------------------|\r\n").print_r($needToSend,1);
            mEcho( "|-----------------------------------------------------------|");
            doOtherWork($needToSend,0,$userid2nickname,$nickname2sn);
            //posix_kill(getmypid(),SIGKILL);
            exit;
        }

    }
    if(!$sentByPcntl){
        mEcho( "|-------------------------- not fork -----------------------|").print_r($needToSend,1);
        mEcho( "|-----------------------------------------------------------|");
        doOtherWork($needToSend,0,$userid2nickname,$nickname2sn);
    }

}
//发送其它信息
function doOtherWork(&$noticeData,$failedCt=0,&$userid2nickname,&$nickname2sn)
{
    global $sn2user;
    if($failedCt>0) sleep(1);
    mEcho( "|-------------------------- doOtherWork Got Data -----------------------|").print_r($noticeData,1);
    mEcho( "|-----------------------------------------------------------------------|");

    //global $userid2nickname,$nickname2sn;
    $log = '';
    $failedMsg = array();
    foreach($noticeData as $userId=>$v)
    {
        if($v==';') continue;
        if(intval($userId)==0)
        {
            wr("A data: ".$userId.'|'.$v.",should be sent to all user!");
            sendMsg('__ALL__',$userId.'|'.$v);
            continue;
        }
        if(!isset($userid2nickname[$userId])||!isset($nickname2sn[$userid2nickname[$userId]])){
            if(isset($userid2nickname[$userId])){
                mEcho(
                    'x===> $userId='.$userId.',
					$userid2nickname[$userId]='.$userid2nickname[$userId].',
					$nickname2sn[$userid2nickname[$userId]]='.$nickname2sn[$userid2nickname[$userId]].'
					$nickname2sn='.
                    print_r($nickname2sn,1).'
					$userid2nickname='.
                    print_r($userid2nickname,1).'
					$sn2user='.
                    print_r($sn2user,1)
                );
            }else{
                mEcho(
                    'x===> $userId='.$userId.' nothing found in data!'
                );
            }
            continue;
        }
        $log .= $nickname2sn[$userid2nickname[$userId]].'->'.$v."";

        mEcho( "doOtherWork call send: $userId -> ".'SYSN|'.$v."");

        if(!sendMsg($nickname2sn[$userid2nickname[$userId]],'SYSN|'.$v))
        {
            if(debugLevel==-1) echo '$userId='.$userId.',$v='.$v." failed\r\n";
            $failedMsg[$userId] = $v;
        }
    }

    if(count($failedMsg)>0&&$failedCt<3)//重试一次
    {
        mEcho( "|-------------------------- failedMsg -----------------------|").print_r($failedMsg,1);
        mEcho( "|-----------------------------------------------------------|");

        doOtherWork($failedMsg,$failedCt+1);
    }

    //wr('doOtherWork rs ==>'.date('Y-m-d H:i:s').' -> '."\r\n".$log."\r\n -------------------------------- \r\n");
}
//发送给所有用户
function sendToManyClients(&$clients,&$msg)
{
    foreach($clients as $k => $v){
        if($k == 0) continue;
        sendMsg($k,$msg);
    }
}
//发送信息
function sendMsg($aimSign,$msg)
{
    if($msg!='W') mEcho( 'sendMsg got: '.$aimSign.','.$msg."",1);
    global $nickname2sn,$clients,$socketWriteFailed,$userid2nickname,$sn2user,$currentThreadNum,$currentThreadPids,$totalBytesSend;
    if(substr($msg,-1)!=EC) $msg.=EC;
    if($aimSign == '__ALL__' && is_array($clients)){
        $stime = microtime_float();
        $sentByPcntl = false;
        if(PCNTLFLAG)
        {
            $pid = pcntl_fork();
            if ($pid == -1) {
            } else if ($pid) {
                mEcho( 'SendMsg forked '.$pid);
                $sentByPcntl = true;
                $currentThreadNum++;
                $currentThreadPids[$pid]=time();
            } else {
                $sentByPcntl = true;
                sendToManyClients($clients,$msg);
                if(count($clients)>300) wr('Send msg to '.count($clients).' clients took '.((microtime_float()-$stime)."").' seconds.',0);
                exit;
            }
        }
        if(!$sentByPcntl){
            sendToManyClients($clients,$msg);
            if(count($clients)>300) wr('Send msg to '.count($clients).' clients took '.((microtime_float()-$stime)."").' seconds.',0);
        }
        return true;
    }else if(preg_match("/(\d+\.){3}\d+_\d+/",$aimSign)&&isset($clients[$aimSign])){
        if(debugLevel==-1) echo $sn2user[$aimSign]['id'] .'='.$msg."\r\n";
        $msg=frame($msg);
        $dl = strlen($msg);
        $totalBytesSend += $dl;
        $wRs = @socket_write($clients[$aimSign], $msg, $dl);
        $sendct = 0;
        while(true){
            if($sendct>3) return false;
            if($wRs===false)
            {

                if(debugLevel==-1) $sn2user[$aimSign]['name'].' -> '.$dl.'>'.$wRs.' ip: '.preg_replace("/_\d+/",'',$aimSign).',msg = '.$msg.' write failed.'."\r\n";

                wr($aimSign.', ip: '.preg_replace("/_\d+/",'',$aimSign).',msg = '.$msg.' write failed.',1);
                $socketWriteFailed[preg_replace("/_\d+/",'',$aimSign)]=isset($socketWriteFailed[preg_replace("/_\d+/",'',$aimSign)])?$socketWriteFailed[preg_replace("/_\d+/",'',$aimSign)]+1:1;
                return false;
            }
            if($dl>$wRs)
            {
                $dl = $dl - $wRs;
                $msg = substr($msg,$wRs);
                $wRs = @socket_write($clients[$aimSign], $msg, $dl);
            }else{
                return true;
            }
            $sendct ++;
        }
    }else if(!isset($clients[$aimSign])){
        mEcho( $aimSign." is not valid clients id.");
        return true;
    }
    return false;

}
//处理信息
function proceedMsg($userSN,$msg)
{
    global $sn2user,$nickname2sn,$clients,$userid2nickname,$loginIpList,$configuration,$totalBytesRecv,$totalBytesSend,$pwd;
    $strs = preg_split("/\s+/", $msg, 2, PREG_SPLIT_NO_EMPTY);
    if(strpos($strs[0],"|") !== false){
        $chatarr = explode('|',$strs[0]);
        $strs[0] = $chatarr[0];
    }
    $command = strtoupper($strs[0]);
    $msg = $strs[1];
    if((!isset($sn2user[$userSN])||!isset($sn2user[$userSN]['name'])) && $command!='LOGIN')
    {
        if(substr($command,0,1)==chr(1))//web服务器直接发送过来的信息
        {
            $pwd = md5(date("Ymd").PWD);
            if(strtoupper($pwd)==substr($command,1)){
                $msgs=explode('|',$msg,2);
                if(count($msgs)>1)
                {
                    if($msgs[1]=='updateUserOnline')
                    {
                        $users=explode(',',$msgs[0]);
                        $rtn=array();
                        foreach($users as $userId)
                        {
                            if(isset($userid2nickname[$userId]) && isset($nickname2sn[$userid2nickname[$userId]]))
                            {
                                $key=$nickname2sn[$userid2nickname[$userId]];
                                $sql='update player_ext set onlinetime=onlinetime+'.(time()-$sn2user[$key]['loginTime']).' where uid='.$sn2user[$key]['id'];
                                query($sql);
                                $rtn[]=$userId;
                            }
                        }
                        $writer = "OK|".implode(',',$rtn);
                        sendMsg($userSN,$writer);
                        return;
                    }

                    $users=explode(',',$msgs[0]);
                    $rtn=array();
                    foreach($users as $userId)
                    {
                        if($userId=='__ALL__')
                        {
                            sendMsg($userId,$msgs[1]);
                        }
                        else if(isset($userid2nickname[$userId])&&isset($nickname2sn[$userid2nickname[$userId]]))
                        {
                            sendMsg($nickname2sn[$userid2nickname[$userId]],$msgs[1]);
                            $rtn[]=$userId;
                        }
                    }
                }
                $writer = "OK|".implode(',',$rtn);
                unset($rtn);
                sendMsg($userSN,$writer);
            }
        }else{
            $writer = "SYSM|Error command:$command, login first.".chr(substr($command,0,1)).'&&'.strtoupper($pwd).'=='.substr($command,1);
            sendMsg($userSN,$writer);
            socket_close($clients[$userSN]);
            $remoteIP = preg_replace("/_\d+/",'',$userSN);
            $loginIpList[$remoteIP]--;
        }
        return;
    }
    switch ($command)
    {
        case "LOGIN":
            $loginInfo = preg_split("/\s+/", $msg, 2, PREG_SPLIT_NO_EMPTY);
            if(count($loginInfo)==2)
            {
                session_write_close();
                if(strlen($loginInfo[0])!=26 && strlen($loginInfo[0])!=32)
                {
                    socket_close($clients[$userSN]);
                    $remoteIP = preg_replace("/_\d+/",'',$userSN);
                    $loginIpList[$remoteIP]--;
                    break;
                }

                session_id($loginInfo[0]);
                //echo '$loginInfo[0]='.$loginInfo[0]."";
                @session_start();
                if(!isset($_SESSION['id'])||!isset($_SESSION['username'])||intval($_SESSION['id'])<1)
                {
                    $safeSid = mysql_real_escape_string($loginInfo[0]);
                    $authRows = false;
                    for($authTry=0; $authTry<3; $authTry++){
                        $authRows = query("SELECT uid,username,nickname,guild_id,team_id,lock_time,vip FROM chat_login_auth WHERE sid='".$safeSid."' ORDER BY is_online DESC,id DESC LIMIT 1");
                        if(is_array($authRows) && isset($authRows[0]) && intval($authRows[0]['uid'])>0){
                            break;
                        }
                        usleep(200000);
                    }
                    if(is_array($authRows) && isset($authRows[0]) && intval($authRows[0]['uid'])>0){
                        $_SESSION['id'] = intval($authRows[0]['uid']);
                        $_SESSION['username'] = $authRows[0]['username'];
                        $_SESSION['nickname'] = $authRows[0]['nickname'];
                        $_SESSION['name'] = $authRows[0]['nickname'];
                        $_SESSION['guild_id'] = intval($authRows[0]['guild_id']);
                        $_SESSION['team_id'] = intval($authRows[0]['team_id']);
                        $_SESSION['password'] = intval($authRows[0]['lock_time']);
                        $_SESSION['vip'] = intval($authRows[0]['vip']);
                        $_userNickName = $_SESSION['nickname'];
                    }else{
                        $loginErrCount[$userSN] = isset($loginErrCount[$userSN])?$loginErrCount[$userSN]+1:1;
                        if($loginErrCount[$userSN]>2)
                        {
                            unset($loginErrCount[$userSN]);
                            socket_close($clients[$userSN]);
                            $remoteIP = preg_replace("/_\d+/",'',$userSN);
                            $loginIpList[$remoteIP]--;
                            break;
                        }
                        $writer = "SYSM|登陆失败！";
                        sendMsg($userSN,$writer);
                        break;
                    }
                }else{
                    if(!isset($_SESSION['nickname'])){
                        $_userNickName = $_SESSION['name'];
                    }
                    else
                    {
                        $_userNickName = $_SESSION['nickname'];
                        //mb_detect_encoding($_SESSION['nickname'])=='UTF-8'?$_SESSION['nickname']:
                    }
                    //echo "*Got name = ".$loginInfo[0]."";
                }

                mEcho( "".$_SESSION['nickname']."TRY TO LOGIN..................................................................................");


                $kick = false;
                if(isset($nickname2sn[$_userNickName]))
                {
                    //kick user
                    $oldSN = $nickname2sn[$_userNickName];

                    $remoteIP = preg_replace("/_\d+/",'',$oldSN);
                    if(isset($loginIpList[$remoteIP])) $loginIpList[$remoteIP]--;

                    $writer = "SYSM|{$_userNickName}从别处(".preg_replace("/_\d+/",'',$userSN).")登陆了,您被强制断线！";
                    sendMsg($oldSN,$writer);
                    //echo $_userNickName." kicked\r\n";

                    if($loginInfo[0]!=$sn2user[$oldSN]['sid']){//因为ie6刷新的时候不会断开.
                        session_id($sn2user[$oldSN]['sid']);

                        mEcho("*----------------------------------------------------");
                        mEcho($sn2user[$oldSN]['sid']);
                        mEcho("*----------------------------------------------------");

                        unset($_SESSION['id']);
                        unset($_SESSION['nickname']);
                        unset($_SESSION['username']);
                        session_write_close();
                    }
                    unset($nickname2sn[$_userNickName]);

                    unset($userid2nickname[$sn2user[$oldSN]['id']]);
                    unset($sn2user[$oldSN]);

                    socket_close($clients[$oldSN]);
                    unset($clients[$oldSN]);

                    $remoteIP = preg_replace("/_\d+/",'',$oldSN);
                    $loginIpList[$remoteIP]--;

                    session_id($loginInfo[0]);
                    @session_start();

                }

                session_write_close();

                userListRefreshMessage($userSN);

                $sn2user[$userSN]=array(
                    'name'      =>$_userNickName,
                    'id'        =>$_SESSION['id'],
                    'sid'       =>$loginInfo[0],
                    'loginTime' =>time(),
                    'username'  =>$_SESSION['username'],
                    'password'  =>$_SESSION['password'],
                    'vip' 		=> $_SESSION['vip'],
                      'now_Achievement_title' => isset($_SESSION['now_Achievement_title'])?$_SESSION['now_Achievement_title']:''
                );

                $userid2nickname[$_SESSION['id']] = $_userNickName;

                //从掉线列表清除
                $old = memGet(TEAM_LEFT_MEMS);
                if(is_array($old)&&isset($old[$_SESSION['id']])){
                    unset($old[$_SESSION['id']]);
                    memSet(TEAM_LEFT_MEMS,$old);
                }

                //wr('set $userid2nickname['.$_SESSION['id'].']='.$loginInfo[0].',$_SESSION["nickname"]='.$_SESSION['nickname'].',encode of session='.mb_detect_encoding($_SESSION['nickname']),1);

                $nickname2sn[$_userNickName] = $userSN;

                $writer = 'SYSI|欢迎, '.$_userNickName.'.';
            }else{
                $writer = 'SYSM|Error format: '.$msg.',use command like:login someone password.';
            }
            mEcho('WWWWWWWWWWWWWWWwwwwwwwwwwwwwwwwwwwwwwwwwwwwwwwwwwwww'.$userSN.'-'.$writer);
            sendMsg($userSN,$writer);
            sendMsg('__ALL__','UA|'.$_userNickName);
            break;
        case "W":
            $data = 'W';
            sendMsg($userSN,$data);
            break;
        case "VAR":
            $data = 'SYS|'.preg_split("/\s+/", $msg, 2, PREG_SPLIT_NO_EMPTY);
            sendMsg($userSN,print_r($GLOBALS[$data[0]],1));
            break;
        case "CHAT":
            $now_Achievement_title_str = '';
            $vipStr = "";
            if($sn2user[$userSN]['vip']==1)
            {
                $vipStr = '<font color="#ff0000">(Vip)</font>';
            }elseif($sn2user[$userSN]['vip']==2)
            {
                $vipStr = '<font color="#ff0000">(Vip)</font>';
                $vipStr = '<img src="../images/merge.gif" />';
            }elseif($sn2user[$userSN]['vip']==3)
            {
                $vipStr = '<font color="#ff0000">(Vip)</font>';
                $vipStr = '<img src="../images/merge.gif" />'.$vipStr;
            }
            /**if ($sn2user[$userSN]['now_Achievement_title'] != '') {
                $now_Achievement_title_str = '<img src="../images/Achievement_title/title_' . $sn2user[$userSN]['now_Achievement_title'] . '.gif" style="vertical-align: sub;width: 16px;height: 16px;">';
            }*/
            $achievementTitleArray = array('kbzl','hcbswqd');//新添加的称号，图片大小为宽100px，高20px
            if ($sn2user[$userSN]['now_Achievement_title'] != '') {
                if(in_array($sn2user[$userSN]['now_Achievement_title'],$achievementTitleArray)){
                    $now_Achievement_title_str = '<img src="../images/Achievement_title/title_' . $sn2user[$userSN]['now_Achievement_title'] . '.gif" style="vertical-align: sub;width: 80px;height: 16px;">';
                }else{
                    $now_Achievement_title_str = '<img src="../images/Achievement_title/title_' . $sn2user[$userSN]['now_Achievement_title'] . '.gif" style="vertical-align: sub;width: 16px;height: 16px;">';
                }
            }
            if($sn2user[$userSN]['password']>time()) continue;
            $msg = htmlspecialchars($msg);
            $msg = str_replace(array('$','`'),'-',$msg);
            sendMsg('__ALL__',trim('C|'.$now_Achievement_title_str.'$'.$sn2user[$userSN]['name']."`".$vipStr."说：".$msg));
            break;
        case "SGCHAT":
            $now_Achievement_title_str = '';
            if($sn2user[$userSN]['password']>time()) continue;
            $vipStr = "";
            if($sn2user[$userSN]['vip']==1)
            {
                $vipStr = '<font color="#ff0000">(Vip)</font>';
            }elseif($sn2user[$userSN]['vip']==2)
            {
                $vipStr = '<font color="#ff0000">(Vip)</font>';
                $vipStr = '<img src="../images/merge.gif" />';
            }elseif($sn2user[$userSN]['vip']==3)
            {
                $vipStr = '<font color="#ff0000">(Vip)</font>';
                $vipStr = '<img src="../images/merge.gif" />'.$vipStr;
            }
            /**if ($sn2user[$userSN]['now_Achievement_title'] != '') {
                $now_Achievement_title_str = '<img src="../images/Achievement_title/title_' . $sn2user[$userSN]['now_Achievement_title'] . '.gif" style="vertical-align: sub;width: 16px;height: 16px;">';
            }*/
            $achievementTitleArray = array('kbzl','hcbswqd');//新添加的称号，图片大小为宽100px，高20px
            if ($sn2user[$userSN]['now_Achievement_title'] != '') {
                if(in_array($sn2user[$userSN]['now_Achievement_title'],$achievementTitleArray)){
                    $now_Achievement_title_str = '<img src="../images/Achievement_title/title_' . $sn2user[$userSN]['now_Achievement_title'] . '.gif" style="vertical-align: sub;width: 80px;height: 16px;">';
                }else{
                    $now_Achievement_title_str = '<img src="../images/Achievement_title/title_' . $sn2user[$userSN]['now_Achievement_title'] . '.gif" style="vertical-align: sub;width: 16px;height: 16px;">';
                }
            }
            $msg = htmlspecialchars($msg);
            $msg = str_replace(array('$','`'),'-',$msg);
            $members = getGroupMembers($userSN);
            $msg = trim('SG|'.$now_Achievement_title_str.'$'.$sn2user[$userSN]['name']."`".$vipStr."说：".$msg);
            if(!empty($members)){
                foreach($members as $userName)
                {
                    if(!isset($nickname2sn[$userName])) continue;
                    if(!isset($clients[$nickname2sn[$userName]])) continue;
                    sendMsg($nickname2sn[$userName],$msg);
                }
            }else{
                sendMsg($userSN,'SYSM|你没有组队，或者其它队员不在线。');
            }
            break;
        case "GCHAT":
            $now_Achievement_title_str = '';
            if($sn2user[$userSN]['password']>time()) continue;
            $vipStr = "";
            if($sn2user[$userSN]['vip']==1)
            {
                $vipStr = '<font color="#ff0000">(Vip)</font>';
            }elseif($sn2user[$userSN]['vip']==2)
            {
                $vipStr = '<font color="#ff0000">(Vip)</font>';
                $vipStr = '<img src="../images/merge.gif" />';
            }elseif($sn2user[$userSN]['vip']==3)
            {
                $vipStr = '<font color="#ff0000">(Vip)</font>';
                $vipStr = '<img src="../images/merge.gif" />'.$vipStr;
            }
            /**if ($sn2user[$userSN]['now_Achievement_title'] != '') {
                $now_Achievement_title_str = '<img src="../images/Achievement_title/title_' . $sn2user[$userSN]['now_Achievement_title'] . '.gif" style="vertical-align: sub;width: 16px;height: 16px;">';
            }*/
            $achievementTitleArray = array('kbzl','hcbswqd');//新添加的称号，图片大小为宽100px，高20px
            if ($sn2user[$userSN]['now_Achievement_title'] != '') {
                if(in_array($sn2user[$userSN]['now_Achievement_title'],$achievementTitleArray)){
                    $now_Achievement_title_str = '<img src="../images/Achievement_title/title_' . $sn2user[$userSN]['now_Achievement_title'] . '.gif" style="vertical-align: sub;width: 80px;height: 16px;">';
                }else{
                    $now_Achievement_title_str = '<img src="../images/Achievement_title/title_' . $sn2user[$userSN]['now_Achievement_title'] . '.gif" style="vertical-align: sub;width: 16px;height: 16px;">';
                }
            }
            $msg = htmlspecialchars($msg);
            $msg = str_replace(array('$','`'),'-',$msg);
            $members = getGuildMembers($userSN);
            $msg = trim('GC|'.$now_Achievement_title_str.'$'.$sn2user[$userSN]['name']."`".$vipStr."说：".$msg);
            if(!empty($members)){
                foreach($members as $userName)
                {
                    if(!isset($nickname2sn[$userName])) continue;
                    if(!isset($clients[$nickname2sn[$userName]])) continue;
                    sendMsg($nickname2sn[$userName],$msg);
                }
            }else{
                sendMsg($userSN,'SYSM|你没有加入公会。');
            }
            break;
        case "WHISPER":
        case "WP":
            $now_Achievement_title_str = '';
            if($sn2user[$userSN]['password']>time()) continue;
            $data = preg_split("/\s+/", $msg, 2, PREG_SPLIT_NO_EMPTY);
            if($sn2user[$userSN]['name'] == $data[0])
            {
                $writer = "SYSM|不能对自己说话！";
                sendMsg($userSN,$writer);
                break;
            }
            $data[1] = htmlspecialchars($data[1]);
            $data[1] = str_replace(array('$','`'),'-',$data[1]);
            $vipStr = "";
        if($sn2user[$userSN]['vip']==1)
        {
            $vipStr = '<font color="#ff0000">(Vip)</font>';
        }elseif($sn2user[$userSN]['vip']==2)
        {
            $vipStr = '<font color="#ff0000">(Vip)</font>';
            $vipStr = '<img src="../images/merge.gif" />';
        }elseif($sn2user[$userSN]['vip']==3)
        {
            $vipStr = '<font color="#ff0000">(Vip)</font>';
            $vipStr = '<img src="../images/merge.gif" />'.$vipStr;
        }
        /**if ($sn2user[$userSN]['now_Achievement_title'] != '') {
            $now_Achievement_title_str = '<img src="../images/Achievement_title/title_' . $sn2user[$userSN]['now_Achievement_title'] . '.gif" style="vertical-align: sub;width: 16px;height: 16px;">';
        }*/
        $achievementTitleArray = array('kbzl','hcbswqd');//新添加的称号，图片大小为宽100px，高20px
        if ($sn2user[$userSN]['now_Achievement_title'] != '') {
            if(in_array($sn2user[$userSN]['now_Achievement_title'],$achievementTitleArray)){
                $now_Achievement_title_str = '<img src="../images/Achievement_title/title_' . $sn2user[$userSN]['now_Achievement_title'] . '.gif" style="vertical-align: sub;width: 80px;height: 16px;">';
            }else{
                $now_Achievement_title_str = '<img src="../images/Achievement_title/title_' . $sn2user[$userSN]['now_Achievement_title'] . '.gif" style="vertical-align: sub;width: 16px;height: 16px;">';
            }
        }
            if(isset($nickname2sn[$data[0]])){
                $writer = 'WP|'.$now_Achievement_title_str.'$'.$sn2user[$userSN]['name']."`".$vipStr."对你说：".$data[1];
                //echo $writer;
                sendMsg($nickname2sn[$data[0]],$writer);
                sendMsg($userSN,'WP|你对$'.$data[0]."`说：".$data[1]);
            }else{
                $writer = "SYSM|$data[0] 不在线！";
                sendMsg($userSN,$writer);
            }
            //print_r($data);
            break;
        default:
            $writer = "SYSM|Error command";
            echo $msg;
            sendMsg($userSN,$writer);
            break;
    }
}
//连接数据库
function connect(){
    global $conn,$_mysql;
    @mysql_close($conn);
    $conn = mysql_connect($_mysql['host'],$_mysql['user'],$_mysql['pass']) or wr(__LINE__.' a=>'.mysql_error(),1);
    mysql_select_db($_mysql['db'],$conn) or wr(__LINE__.' b=>'.mysql_error(),1);
    $row = mysql_fetch_row(mysql_query('show tables',$conn));
    $row = mysql_fetch_row(mysql_query('show create table '.$row[0],$conn));
    //wr(__FILE__." c:".print_r($row,1)."");
    if(strpos(strtolower($row[1]),"charset=gbk")!==false){
        mysql_query("SET NAMES gbk;",$conn);
    }else{
        mysql_query("SET NAMES gbk;",$conn);
    }
}
//查询数据库
function query($sql,$flag=false){
    global $conn;
    $result = @mysql_query($sql,$conn);//or die('sql='.$sql.'<br/>'.mysql_error());
    if($e=mysql_error())
    {
        connect();
        $result = @mysql_query($sql,$conn);
        if($e=mysql_error())
        {
            wr(__FILE__.":".$e." 2\r\n",1);
        }
    }
    //wr(__FILE__.":".$sql."\r\n");
    $rtn = array();
    if(is_resource($result)){
        while ($row = mysql_fetch_assoc($result))
        {
            $rtn[]=$row;
        }
    }else{
        return;
    }
    //wr(__FILE__.":".print_r($rtn,1)."\r\n",2);
    @mysql_free_result($result);
    return $rtn;
}

//连接内存服务
function memConnect()
{
    global $mem,$_mem;
    $mem = new Memcache;
    if ($mem->connect($_mem['host'], $_mem['port']) === FALSE)
    {
        $mem = false;
        wr("Memcache connet failed server:".print_r($_mem,1),0);
    }
}
//取内存数据
function memGet($key)
{
    global $mem;
    if(!$ver=$mem->getVersion())
    {
        memConnect();
    }
    //wr('ver = '.$ver);
    if($mem === false) return NULL;
    $val = $mem->get($key);
    if(!$val)
    {
        //wr($mem->get('db_map'));
    }else{
        //wr($key.' (mem)= '.print_r($val,1));
    }
    if(is_string($val))
        return unserialize($val);
    else
        return $val;
}
//存内存数据
function memSet($key,$value)
{
    global $mem;
    if(!$ver=$mem->getVersion())
    {
        memConnect();
    }
    //wr('ver = '.$ver);
    //wr('memSet('.$key.','.$value.')');
    if($mem === false) return NULL;
    //wr('memSet('.$key.','.$value.') maybe ok');
    return $mem->set($key,$value);
}
//取得微秒
function microtime_float()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}

//打印调试信息
function mEcho($str)
{
    if(debugLevel>1) echo $str."\r\n";
}

function formatSize($int)
{
    if($int>1024*1024)
    {
        return number_format($int/(1024*1024),2).' M';
    }
    else if($int>1024)
    {
        return number_format($int/(1024),2).' K';
    }else{
        return $int.' Byte';
    }

}
//取得配置信息
function getConfiguration(){
    global $configuration;
    $dt = date("YmdHi");
    $configWelcome = memGet('db_welcome');
    if(empty($configWelcome)||!is_array($configWelcome))
    {
        $configWelcome = array();
        echo "db welcome not found!\r\n";
    }
    foreach($configWelcome as $row)
    {
        if($row['code']=='admin'){
            //echo $row['contents']."\r\n";
            $configuration[$row['code']] = explode(',',$row['contents']);
            break;
        }
    }
    $configuration['admin'] = array_flip($configuration['admin']);

    $configWelcome = memGet('db_gonggao');
    if(empty($configWelcome)||!is_array($configWelcome))
    {
        echo "db gonggao not found!\r\n";
        return;
    }

    foreach($configWelcome as $row)
    {
        if(!isset($row['endtime']))
        {
            mEcho('Gonggao error:'.print_r($row,1));
            continue;
        }
        if($row['endtime']>$dt)//没有结束的
        {
            if(!isset($configWelcome['gonggao'])) $configWelcome['gonggao']=array();
            $configWelcome['gonggao'][] = $row;
        }
    }
    if(isset($configWelcome['gonggao']))    $configuration['gonggao'] = $configWelcome['gonggao'];

}

//
function wrt($somecontent,$flag=0){
    if(debugLevel<1) return;
    $filename = dirname(__FILE__).'/logtt.txt';
    $handle = fopen($filename, 'a+');

    if (fwrite($handle, $somecontent."\r\n") === FALSE) {
        //exit;
    }

    fclose($handle);
}

function wrr($somecontent,$flag=0){
    $filename = dirname(__FILE__).'/logr.txt';
    $handle = fopen($filename, 'a+');

    if (fwrite($handle, $somecontent."\r\n") === FALSE) {
        //exit;
    }

    fclose($handle);
}

function wrs($somecontent,$flag=0){
    $filename = dirname(__FILE__).'/logs.txt';
    $handle = fopen($filename, 'a+');

    if (fwrite($handle, $somecontent."\r\n") === FALSE) {
        //exit;
    }

    fclose($handle);
}
//
function wro(){
    global $socket_port,$userid2nickname;
    $port=$socket_port;
    $num=count($userid2nickname);
    $filename = dirname(__FILE__).'/onlineData.php';
    $data = file_get_contents($filename);
    if(preg_match("/online\['".$port."\']=\d+;/",$data))
    {
        $data = preg_replace("/online\['".$port."\']=\d+;/","online['".$port."']=".$num.";",$data);
    }else{
        $data .= "\r\n\$online['".$port."']=".$num.";";
    }
    $handle = fopen($filename, 'w+');

    if (fwrite($handle, $data) === FALSE) {
        //exit;
    }

    fclose($handle);
}
//记录日志
function wr($somecontent,$flag=0){
    if(debugLevel<1) return;
    $filename = dirname(__FILE__).'/log.txt';

    $handle = fopen($filename, 'a+');

    if (fwrite($handle, $somecontent."\r\n") === FALSE) {
        //exit;
    }

    fclose($handle);
}
?>
