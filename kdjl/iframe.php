<?php
require_once('config/config.game.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>口袋游戏调用</title>
<style>
body,div,dl,dt,dd,ul,ol,li,p {margin:0; padding:0; outline:none}
fieldset,img {border:0}
ol,ul {list-style:none}
body {font:0px/normal '宋体'; color:#000;}
a,a:visited {color:#330000; text-decoration:none; blr:expression(this.onFocus=this.blur());outline:none;}
a:hover {text-decoration:underline}
.news {margin:28px 0 0 53px; width:250px; height:66px; overflow:hidden}
.news div {margin:4px 0 0 0; height:18px; overflow:hidden}
.news li {height:18px; line-height:18px}
.news li span {float:right}
.news a,.news a:visited {color:#ffe468}
.news strong {font-weight:normal}
</style>
</head>

<body>
<div class="news" style="color:#330000;line-height:23px;font-size:12px;">
<?php
$c = $_pm['mysql'] -> getOneRecord('SELECT contents FROM welcome WHERE code = "ifrc"');
echo $c['contents'];
?>
</div>
</body>
</html>
