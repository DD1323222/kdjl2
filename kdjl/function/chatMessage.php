<?php
/*
 * Legacy chat bridge kept only so old URLs fail predictably.
 * Active chat uses socketChat; rewriting messageData.php at request time is
 * unsafe and the bridge has no callers in the current game code.
 */
header('HTTP/1.1 404 Not Found');
header('Content-Type:text/plain;charset=utf-8');
header('Cache-Control:no-store, no-cache, must-revalidate');
exit('disabled');
?>
