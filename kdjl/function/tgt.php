<?php 
session_start();
require_once('../config/config.game.php');
$_pm['mysql'] -> query('UPDATE player_ext SET tgt = 29 WHERE uid = '.$_SESSION['id']);
?>
OK