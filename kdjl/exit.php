<?php
require_once('config/config.game.php');
if(intval($_SESSION['id']) > 0) {
    session_destroy();
    echo '<br/><br/><br/><center><div style="font-size:14px;border:solid #ccc 1px;width:200px;height:100px;padding:5px;">正常退出成功! <br/>
<a href=../passport/login.php>重新登陆</a>
</div></center>';
}else{
    echo("<script type='text/javascript'>window.location='index.php';</script>");
}
?>