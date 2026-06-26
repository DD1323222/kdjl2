<?php
/**
 * It's a class use to log some information of a player,
 * such as the entrance of the player's action, the request
 * time of the player, etc.
 *
 * Date:2009-03-16
 * Author:Zheng.Ping
 */

class UserInfoLogger {
    /**
     * the user's id
     * integer 
     */
    private $id;

    /**
     * the log file's path
     * string
     */
    private $logPath;

    /**
     * the prefix name of custom log file
     * string
     */
    private $prefix;

    /**
     * constructor
     *
     * @param integer $id
     * @param string  $logPath
     * @param string  $prefix
     */
    public function __construct($id = 0, $logPath = '', $prefix = '') {
        $this->id = $id;
        $this->logPath = $logPath;
        $this->prefix  = $prefix;
    }

    /**
     * log the player's entrance
     * it will log the the user's address, request url, etc.
     */
    public function logPlayerEntrance() {
        if (file_exists($this->logPath) && is_file($this->logPath) && is_writable($this->logPath)) {
            $logMsg = $this->getPlayerEntranceInfo();
            $this->logMsg2File($logMsg);

        } else {
            $dirName = is_dir($this->logPath) ? $this->logPath : dirname($this->logPath);
            //check if the file exists or try to create a new file to log
            if (is_dir($dirName) && is_writable($dirName)) {
                $this->setPrefix('player');
                $logFilePath = $dirName . '/' . $this->prefix . '_' . $this->id . '.log';
                $this->setLogPath($logFilePath);

                $logMsg = $this->getPlayerEntranceInfo();
                $this->logMsg2File($logMsg);
            }
        }
    }

    /**
     * set the log file's path
     *
     * @param string $logPath
     */
    public function setLogPath($logPath) {
        $this->logPath = $logPath;
    }

    /**
     * set the log file's prefix name
     *
     * @param string $prefix
     */
    public function setPrefix($prefix) {
        $this->prefix = $prefix;
    }
    /**
     * log the a msg to the log file
     * 
     * @param string $logMsg
     * @return void
     */
    private function logMsg2File($logMsg) {
        if (is_string($logMsg) && $logMsg) {
            $fHandler = @fopen($this->logPath, 'a+');
            if ($fHandler) {
                $msg = sprintf("[%s] %s", date('Y-m-d H:i:s'), $logMsg);
                fwrite($fHandler, $msg);
                fclose($fHandler);
            }
        }
    }

    /**
     * get the information of the player's entrance
     *
     * @return string
     */
    function getPlayerEntranceInfo() {
        $remoteAddr = $_SERVER['REMOTE_ADDR'];
        $requestUrl = $_SERVER['REQUEST_URI'].',post:'.print_r($_POST,1);
        $userAgent  = $_SERVER['HTTP_USER_AGENT'];

        return sprintf("Player:%d from %s request %s with [%s]\n\n\n", $this->id, $remoteAddr, $requestUrl, $userAgent);
    }
}
?>
