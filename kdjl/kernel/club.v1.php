<?php
/**
@Usage: club base class
@Copyright:www.webgame.com.cn
@Version:1.0
@Write date: 2008-09-12
@Write By: GeFei SU
@Club logic descs:
  帮会系统主要由两个表构成：
   一个是帮会信息表，存储所有帮会的信息。=> clublist
   第二是帮会成员表, 存储的是某成员所在帮会的个人信息。
   
*/
class club{
	// 数据库对象
	public $m_db;

	// 内存对象
	public $m_m;
	
	public function __construct(){
		global $_pm;
		if (!is_array($_pm) || 
			!is_object($_pm['mysql']) || 
			!is_object($_pm['mem']) ||
			!is_object($_pm['user'])
			)
		return false;

		$this-> m_db = $_pm['mysql'];
		$this-> m_m	 = $_pm['mem'];
	}

	/** 
	*@Usage: 创建帮会
	*@Param: $name String 帮会名字
	*@Return true of false
	*/
	public function create($name)
	{
		$currentUid = intval($_SESSION['id']);
		$name = $this->m_db->escape($name);
		// 当前玩家是否拥有帮会。
		$hadclub = $this->m_db->getOneRecord("SELECT id 
		                                        FROM club_user 
											   WHERE uid={$_SESSION['id']} 
											   LIMIT 0,1");
		if (is_array($hadclub)) return false;

		$length = strlen($name);
		if ($length<6 || $length>20) return false;

		// 验证用户的基本信息。

		$exists = $this->m_db->getOneRecord("SELECT id 
											   FROM club
											  WHERE name='{$name}'
											  LIMIT 0,1
											");
		if (!is_array($exists))
		{
			// 开始创建帮会信息。
			$this->m_db->query("INSERT INTO club(name,masters,ctime,levels)
							    VALUES('{$name}','{$_SESSION['id']}',".time().",'帮主,帮会成员')
							   ");

			$self = $this->m_db->getOneRecord("SELECT id
											 FROM club
											WHERE FIND_IN_SET({$currentUid},masters)");
			if (!is_array($self)) return false;

			// 加入用户资料。
			$this->m_db->query("INSERT INTO club_user(cid,uid,levelname,ctime)
							    VALUES({$self['id']},{$_SESSION['id']},'帮主',".time().")
							  ");
			// 扣除玩家需要的条件内容。

			return true;
		}
		else return false;
	}

    // 解散帮会
    public function cancel(){}

	// 设置帮会
	public function setClub(){}
   
    /**
	*@Usage: 添加成员
	*@Param: $name String 玩家昵称
	*@Return: true of false
	**/
	public function addMember($uid)
	{
		$uid = intval($uid);
		$currentUid = intval($_SESSION['id']);
		if ($uid<1) return false;
		
		$self = $this->m_db->getOneRecord("SELECT id
											 FROM club
											WHERE FIND_IN_SET({$currentUid},masters)");
		if (!is_array($self)) return false;
		
		$had = $this->m_db->getOneRecord("SELECT id
										    FROM club_user
										   WHERE uid={$uid}
										 ");
		if (!is_array($had))
		{
			$this->m_db->query("INSERT INTO club_user(cid,uid,levelname,ctime)
							    VALUES({$self['id']},{$uid},'帮会成员',".time().")
							  ");
			return true;
		}
		else return false;
	}

	// 删除成员
	public function delMember(){}

	/**
	*@Usage:  修改成员所在帮会的级别。
	*@Param:  $levelName String 玩家的帮会级别层次。
	          $uid Int 玩家的用户ID。
	*@Return: true of false
	*/ 
	public function setMember($levelName, $uid)
	{
		$currentUid = intval($_SESSION['id']);
		$uid = intval($uid);
		$levelName = $this->m_db->escape($levelName);
		$master = $this->m_db->getOneRecord("SELECT id
											   FROM club
											  WHERE FIND_IN_SET({$currentUid},masters)
											  LIMIT 0,1
											");
		if (is_array($master))
		{
			// 检查玩家是否数据当前玩家的所在帮会。
			$inline = $this->m_db->getOneRecord("SELECT id 
												   FROM club_user 
												  WHERE cid={$master['id']} 
												  LIMIT 0,1");
			if (is_array($inline))
			{
				$this->m_db->query("UPDATE club_user
				                       SET levelname='{$levelName}'
									 WHERE uid={$uid}
								   ");
				return true;
			}
		}
		return false;
	}

	/**
	*@Usage: 显示帮会公告
	*@Param: $cid int 帮会ID
	*@Return: String
	*/
	public function getClubInfo($cid)
	{
		if (intval($cid)<1) return false;
		$word = $this->m_db->getOneRecord("SELECT name,aword,king,maxmember,masters,levels,ctime
		                                     FROM club
											WHERE id={$cid}
		                                    LIMIT 0,1
										 ");
		return $word;
	}

	/**
	*@Usage: 设置帮会信息
	*@Param: $info array 设置帮会的基本信息。
	*@Return: true of false;
	*/
	public function setBasePar($info)
	{
		if (!is_array($info)) return false;
		else
		{
			$currentUid = intval($_SESSION['id']);
			$updatestr = '';
			foreach ($info as $k => $rs)
			{
				if ($k == 'aword') $updatestr = $updatestr==''?"aword='{$info['aword']}'":",aword='{$info['aword']}'";
				else if ($k == 'masters') 
					$updatestr = $updatestr==''?"masters='{$info['masters']}'":",masters='{$info['masters']}'";
				else if ($k == 'levels')
					$updatestr = $updatestr==''?"levels='{$info['levels']}'":",levels='{$info['levels']}'";
			}
			if ($updatestr == '') return false;
			$this->m_db->query("UPDATE club
									   SET {$updatestr}
								     WHERE FIND_IN_SET({$currentUid},masters)
							   ");	
			return true;
		}
	}

	// Default destruct.
	public function __destruct(){
	
	}
}

class kdclub extends club{

	public $m_maxperson	=	10;	//帮会最大成员数

	public $m_name		=	'';	//帮会名字


	public function __construct(){parent::__construct();}

	public function task(){}	// 帮会任务系统。

	public function chat(){}	// 帮会聊天系统

	public function fb(){}		// 帮会副本

	public function __destruct(){parent::__destruct();}
}
?>
