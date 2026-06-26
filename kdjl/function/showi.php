<?php 

session_start();
require_once('../config/config.game.php');

if(isset($_GET['k']))
print_r(unserialize($_pm['mem']->get($_GET['k'])));

if(isset($_GET['s'])){
echo '<pre>
line='.__LINE__.'
';
var_dump($_SESSION);
echo '</pre>
';}
?>