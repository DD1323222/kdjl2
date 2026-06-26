<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2008.05.08
*@Update Date: 2008.05.29
*@Usage:Fightting Function.
*@Note: NO Add magic props.
  本模块主要功能：
  1)计算攻击力，包括BB和怪物。
  2)同时记录用户战斗的怪物数据，包括HP,MP,
  3)掉落物品最后根据机率，
*/
session_start();

require_once('../config/config.game.php');

$rows=$_pm['mysql']->getRecords('select id ,name from props');
foreach($rows as $row)
{
	echo $row['id'].'-'.$row['name'].'<br/>';
}