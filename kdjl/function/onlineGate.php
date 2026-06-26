<?php
/**
* @Usage: Online Check. include security check.
*/
session_start();
if ( !isset($_SESSION['id']) || intval($_SESSION['id']) < 0 )
{
	echo "goToLogin();";
}
?>