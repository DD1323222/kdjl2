<?php
session_start();
require_once(dirname(__FILE__).'/config/config.game.php');
secStart($_pm['mem']);
$tm = time() - 15 * 24 * 3600;
$_pm['mysql'] -> query("DELETE FROM gamelog WHERE ptime < {$tm}");
?>