<?php
/**
 * check the server's run environment
 * 1. check the database connection
 * 2. check the memcached connection
 * if all running environment is ok, return ok
 */

require_once './config/config.mysql.php';
// define for mysql
define('MYSQL_DB_HOST', $_mysql['host']);
define('MYSQL_DB_USER', $_mysql['user']);
define('MYSQL_DB_PASS', $_mysql['pass']);
define('MYSQL_DB_NAME', $_mysql['db']);
// define for memcached
define('MEMCACHED_HOST', '222.73.57.101');
define('MEMCACHED_PORT', '11211');

// define return code
define('RETURN_CODE_SUCCESS', 'ok');
define('RETURN_CODE_FAIL', 'error');

/**
 * check if the MySQL DB connection is normal
 *
 * @return boolean
 */
function check_mysql_db_connection()
{
    $status = true;

    $link = @mysql_connect(MYSQL_DB_HOST, MYSQL_DB_USER, MYSQL_DB_PASS);
    if ($link == false) {
        $status = false;
    } else {
        $status = @mysql_select_db(MYSQL_DB_NAME);
        mysql_close($link);
    }

    return $status;
}

/**
 * check if the Memcached connection is normal
 *
 * @return boolean
 */
function check_memcached_connection()
{
    $memcache = new Memcache;
    $status = @$memcache->connect(MEMCACHED_HOST, MEMCACHED_PORT);
    if ($status) {
        $memcache->close();
    }

    return $status;
}


// ================== executable code ============================
// check the connection to MySQL
if (!check_mysql_db_connection()) {
    die(RETURN_CODE_FAIL);
} 

// check the connection to Memcached
if (!check_memcached_connection()) {
    die(RETURN_CODE_FAIL);
}

exit(RETURN_CODE_SUCCESS); // if all running environment is ok

?>
