<?php
require_once('config/config.game.php');
define('CLIENT_MULTI_RESULTS', 131072);

//$_pm['mysql'] -> query("set @mysqlvar=$i");
//s$_pm['mysql'] -> query("call clear_dead_user(3)");
//$row = $_pm['mysql'] -> getRecords("call check_clear_row()");
//print_r($row);
$conn = mysql_connect($_mysql['host'],$_mysql['user'], $_mysql['pass'], NULL,131072);
mysql_select_db($_mysql['db']);
mysql_query("set max_sp_recursion_depth=4",$conn);
mysql_query("call clear_dead_user(3)",$conn);
mysql_query("call do_clear_user()",$conn);
echo mysql_error().'<hr />';
?>