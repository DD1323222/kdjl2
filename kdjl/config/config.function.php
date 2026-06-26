<?php
//公用函数
//取得指定地图的信息

function getBaseMapInfoById($map_id){
	global $_pm;
	$mapInfo = $_pm['mem']-> get("base_map_info_".$map_id);
	if($mapInfo){
		return $mapInfo;
	}
	$sql = "SELECT * FROM map WHERE id='{$map_id}'";
	$rs = $_pm['mysql'] -> getOneRecord($sql);
	$arr['k'] = "base_map_info_".$map_id;
	$arr['v'] = $rs;
	$_pm['mem'] -> setArr($arr);
	return $rs;
}

//取得指定怪物
function getBaseGpcInfoById($gpcid){
	global $_pm;
	$gpcInfo = $_pm['mem']-> get("base_gpc_info_".$gpcid);
	if($gpcInfo){
		return $gpcInfo;
	}
	$sql = "SELECT * FROM gpc WHERE id='{$gpcid}'";
	$rs = $_pm['mysql'] -> getOneRecord($sql);
	$arr['k'] = "base_gpc_info_".$gpcid;
	$arr['v'] = $rs;
	$_pm['mem'] -> setArr($arr);
	return $rs;
}

//取得sys技能
function getBaseSkillSysInfoById($id){
	global $_pm;
	$skInfo = $_pm['mem']-> get("base_skillsys_info_".$id);
	if($skInfo){
		return $skInfo;
	}
	$sql = "SELECT * FROM skillsys WHERE id='{$id}'";
	$rs = $_pm['mysql'] -> getOneRecord($sql);
	$arr['k'] = "base_skillsys_info_".$id;
	$arr['v'] = $rs;
	$_pm['mem'] -> setArr($arr);
	return $rs;
}

//取得sys技能
function getBaseSkillSysInfoByPId($pid){
	global $_pm;
	$skInfo = $_pm['mem']-> get("base_skillsys_info_pid_".$pid);
	if($skInfo){
		return $skInfo;
	}
	$sql = "SELECT * FROM skillsys WHERE pid='{$pid}'";
	$rs = $_pm['mysql'] -> getOneRecord($sql);
	$arr['k'] = "base_skillsys_info_pid_".$pid;
	$arr['v'] = $rs;
	$_pm['mem'] -> setArr($arr);
	return $rs;
}

//取得宠物基本信息
function getBaseBBInfoById($id){
	global $_pm;
	$bbInfo = $_pm['mem']-> get("base_bb_info_".$id);
	if($bbInfo){
		return $bbInfo;
	}
	$sql = "SELECT * FROM bb WHERE id='{$id}'";
	$rs = $_pm['mysql'] -> getOneRecord($sql);
	$arr['k'] = "base_bb_info_".$id;
	$arr['v'] = $rs;
	$_pm['mem'] -> setArr($arr);
	return $rs;
}

//通关宠物名字取得宠物信息
function getBaseBBNameInfoById($name){
	global $_pm;
	$bbInfo = $_pm['mem']-> get("base_bbname_info_".$name);
	if($bbInfo){
		return $bbInfo;
	}
	$sql = "SELECT * FROM bb WHERE name='{$name}'";
	$rs = $_pm['mysql'] -> getOneRecord($sql);
	$arr['k'] = "base_bbname_info_".$name;
	$arr['v'] = $rs;
	$_pm['mem'] -> setArr($arr);
	return $rs;
}

//通过id取得装备信息
function getBasePropsInfoById($id){
	global $_pm;
//	$pInfo = $_pm['mem']-> get("base_props_info_".$id);
//	if($pInfo){
//		return $pInfo;
//	}
	$sql = "SELECT * FROM props WHERE id='{$id}'";
	$rs = $_pm['mysql'] -> getOneRecord($sql);
	$arr['k'] = "base_props_info_".$id;
	$arr['v'] = $rs;
	$_pm['mem'] -> setArr($arr);
	return $rs;
}

//取得welcome表
 function getBaseWelcomeInfoByCode($code){
	global $_pm;
	$pInfo = $_pm['mem']-> get("base_welcome_info_".$code);
	if($pInfo){
		return $pInfo;
	}
	$sql = "SELECT * FROM welcome WHERE code='{$code}'";
	$rs = $_pm['mysql'] -> getOneRecord($sql);
	$arr['k'] = "base_welcome_info_".$code;
	$arr['v'] = $rs;
	$_pm['mem'] -> setArr($arr);
	return $rs;
}



