<?php
require_once('../config/config.game.php');
secStart($_pm['mem']);
require_once(dirname(__FILE__).'/../socketChat/config.chat.php');
$s=new socketmsg();
$userId = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($userId < 1) die('');
$teamId = isset($_SESSION['team_id']) ? intval($_SESSION['team_id']) : 0;
$requestId = (isset($_GET['id']) && !is_array($_GET['id'])) ? intval($_GET['id']) : 0;
function teamJsString($value)
{
	$value = str_replace("\\", "\\\\", $value);
	$value = str_replace("'", "\\'", $value);
	$value = str_replace(array("\r", "\n"), array("\\r", "\\n"), $value);
	$value = str_replace("</", "<\\/", $value);
	return $value;
}
//5.3
$team=new team($teamId,$s);
$team->checkMyTeam();
$teamId = isset($_SESSION['team_id']) ? intval($_SESSION['team_id']) : 0;
if(isset($_GET['checkOnly']))
{
//header("refresh:27;url=".$_SERVER['REQUEST_URI']);
	die('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="refresh" content="26" />
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>当前地图的队伍</title>
<script language="javascript">
setTimeout("window.location=\'/function/team.php?checkOnly=1&rd=\'+Math.random();",24000);
setTimeout("window.location.reload();",25000);
</script></head>
<body>
</body>
</html>');
}
/*********************************************************************************/
/*下面是这个文件当iframe使用时的相关处理*/
/*********************************************************************************/
if(isset($_GET['showAllTeamsTime']))
{
	if($teamId < 1){
		$showAllTeamsTime = (isset($_GET['showAllTeamsTime']) && !is_array($_GET['showAllTeamsTime'])) ? intval($_GET['showAllTeamsTime']) : 0;
		$echo = $team->getTeamList($showAllTeamsTime);
		if(isset($_GET['check']))
		{
			if($echo!='latest')
			{
				echo 'window.location="/function/team.php?showAllTeamsTime=0";';
			}else{
				echo '//'.$echo;
			}
			die();
		}
	}else{
		$echo = $team->getMyTeamInfo();
		$isleader=$team->isTeamLeader($userId,$teamId);
	}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>当前地图的队伍</title>
</head>
<script language="javascript" src="/javascript/prototype.js"></script>
<style>
body,td{font-size:12px}
ul { margin: 0px; padding: 0px; }
li { display: inline; list-style-type: none; }
.title { width:95px; height:22px; float:left;line-height:22px; overflow:hidden; }
.title2 { width:50px; height:22px; float:left;line-height:22px;}
.title3 { width:80px; height:17px; float:left;line-height:22px; text-align:center; padding-top:5px}

</style>
<body leftmargin="0" topmargin="0" rightmargin="0">
<div style="position:absolute; left:230px; z-index:10; cursor:pointer" onclick="window.parent.location.reload();"><img src="../new_images/ui/add08.gif" border="0" /></div>

<script language="javascript">
var inteam=false;
var curtime=<?php echo time(); ?>;
var data='<?php echo teamJsString($echo); ?>';
function teamHtmlText(value)
{
	return String(value).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}
function activeButtons()
{
	var btn=document.getElementsByTagName('input');
	for(var i=0;i<btn.length;i++)
	{
		if(btn[i].type=='button')
		{
			btn[i].disabled=false;
		}
	}
}
</script>
<?php if ($teamId < 1){ ?>
<table border="0" style="border-bottom:1px solid #CCCCCC; color:#005500" cellpadding="0" cellspacing="0">
  <tr>
    <td align="center" height="21" width="95" style="border-bottom:1px solid #005500; ">队长</td>
    <td align="center" width="50" style="border-bottom:1px solid #005500; ">队员人数</td>
    <td align="center" width="80" style="border-bottom:1px solid #005500; ">申请加入</td>
  </tr>
</table>
<script language="javascript">
var datas=data.split('`');

document.write('<ul>');
for(var i=0;i<datas.length;i++)
{
	var tmp=datas[i].split('|');
	if(tmp.length==3){
		document.write('\
		<li>\
            <div class="title" align="center">\
            '+teamHtmlText(tmp[1])+'</div>\
            <div class="title2" align="center">\
            '+tmp[2]+'</div>\
            <div class="title3" align="center">\
            <img src="../images/ui/team/anniu1.gif" style="cursor:pointer" width="69" height="17" onclick="parent.applyTeam('+tmp[0]+');this.disabled=true;" /></div>\
            </li>\
			');
	 }
}
document.write('</ul>');

function cnew()
{
	var o = document.createElement('script');
	o.src = '/function/team.php?check=1&showAllTeamsTime='+curtime+'&rd='+Math.random();
	document.body.appendChild(o);
	setTimeout('cnew();',5000);
}
setTimeout('cnew();',5000);
</script>
<!--/table-->
<?php } else { ?>
<script language="javascript">
var inteam=true;
var isleader=<?php echo $isleader+0; ?>;
var myuid=<?php echo $userId; ?>;
var data=(data || '@').split('@');

document.write('<strong>'+teamHtmlText(data[0])+'</strong> 的队伍');

</script>
<table width="100%" border="0" cellpadding="0" cellspacing="0" style="; color:#005500">
  <tr style="border-bottom:1px solid #005500; color:#005500">
    <td align="center" height="21" style="border-bottom:1px solid #005500; ">成员</td>
    <td align="center" style="border-bottom:1px solid #005500; ">状态</td>
    <td align="center" style="border-bottom:1px solid #005500; ">操作</td>
  </tr>

<script language="javascript">
var datas=(data[1] || '').split('`');
for(var i=0;i<datas.length;i++)
{
	var tmp=datas[i].split('|');

	if(tmp.length==3){
		if(tmp[2]=='-1')
		{
			var str='申请中';
		}
		else if(tmp[2]=='1')
		{
			str='已归队';
		}
		else if(tmp[2]=='-2')
		{
			continue;
		}else{
			str='暂离';
		}
		document.write('  <tr>\
		<td align="center" height="21">'+teamHtmlText(tmp[1])+'</td>\
		<td align="center">'+str+'</td>\
		<td align="center">'+(
			isleader&&myuid!=tmp[0]?
			(tmp[2]!='-1'?'<input type="image" src="../new_images/ui/team_kick.png" name="Submit" value="踢出" onclick="parent.kickMember('+tmp[0]+');this.disabled=true;" style="cursor:pointer"/>':'<input  type="image" src="../new_images/ui/team_accept.png" style="cursor:pointer" name="Submit" value="批准" onclick="parent.permitTeam('+tmp[0]+');this.disabled=true;" /><input  type="image" style="cursor:pointer" src="../new_images/ui/team_refuse.png" name="Submit" value="拒绝" onclick="parent.unPermitTeam('+tmp[0]+');this.disabled=true;" />')
			:
			'-')+'</td>\
	  </tr>');
	 }
}
</script>
</table>
<?php } ?>
</body>
</html>
<?php
	die();
}


/*********************************************************************************/
/*下面是这个文件当ajax请求的后台使用时的相关处理，比如用户创建队伍，离开队伍等等 */
/*********************************************************************************/

//$rs=$s->sendMsg('SYSN|'.iconv('utf-8','utf-8','这个消息发送给id为97和620的用户,如果他们在线则可以收到').'.',array(97,620));
header("Content-type: text/plain; charset=utf-8");

$act = (isset($_GET['act']) && !is_array($_GET['act'])) ? $_GET['act'] : '';
switch($act)
{
	case 'create':
		$rs=$team->createTeam();
		if($rs!==true)
		{
			die($rs);
		}else{
			die("OK");
		}
		break;
	case 'permit':
		$rs=$team->permitTeam($requestId);
		if($rs!==true)
		{
			die($rs);
		}else{
			die("OK");
		}
		break;
	case 'unpermit':
		$rs=$team->unpermitTeam($requestId);
		if($rs!==true)
		{
			die($rs);
		}else{
			die("OK");
		}
		break;
	case 'invite':
		$rs=$team->inviteTeam($requestId);
		if($rs!==true)
		{
			die($rs);
		}else{
			die("OK");
		}
		break;
	case 'apply':
		$rs=$team->applyTeam($requestId);
		if($rs!==true)
		{
			die($rs);
		}else{
			die("OK");
		}
		break;
	case 'leave':
		$rs=$team->leaveTeam();
		if($rs!==true)
		{
			die($rs);
		}else{
			die("OK");
		}
		break;
	case 'disbandTeam':
		$rs=$team->disbandTeam();
		if($rs!==true)
		{
			die($rs);
		}else{
			die("OK");
		}
		break;
	case 'kickMember':
		$rs=$team->kickMember($requestId);
		if($rs!==true)
		{
			die($rs);
		}else{
			die("OK");
		}
		break;
	case 'swapState':
		$rs=$team->swapTeamState();
		if($rs!==true)
		{
			die($rs);
		}else{
			die("OK");
		}
		break;
	case 'teamInfo':
		$rs=$team->getMyTeamInfo();
		if($rs!==true)
		{
			die($rs);
		}else{
			die("OK");
		}
		break;
	default:
		echo  '错误请求（'.$act.'）！';
		break;
}
?>
