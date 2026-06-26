<?php
/**
@Usage: User class
@Copyright:www.webgame.com.cn
@Version:1.0
@Write: 2008.07.13
*/
class user
{
	private $m_db;	//	Db Handle

	private $m_m;	//	Memory Handle

	function __construct(){
		global $_pm;
		if (!is_array($_pm) || 
			!is_object($_pm['mysql']) || 
			!is_object($_pm['mem'])
			)
		return false;

		$this-> m_db = &$_pm['mysql'];
		$this-> m_m	 = &$_pm['mem'];
	}

	/**
	@Parameter check
	*/
	public function check($chkarr)
	{
		if (!is_array($chkarr)) return false;
		foreach ($chkarr as $type => $value)
		{
			if ($type =='int')
			{
				if (intval($value)<1) return false;
			}
			else if($type =='string')
			{
				if (empty($value) || $value=='' || strlen($value)<1) return false;
			}
			else if($type == 'object')
			{
				if (!is_object($value)) return false;
			}
			else if($type == 'array')
			{
				if (!is_array($value)) return false;
			}
			else return false;
		}
		return true;
	}
	/**
	@ Get user info.
	@ Param: user id,field list.
	@ Return: array.
	*/
	public function getUserById($id, $field='*')
	{
		if ($this->check(array('int' => $id,'string' => $field)) === true)
		{
			$rs = $this->m_db->getOneRecord("SELECT {$field} FROM player WHERE id={$id} LIMIT 0,1");
			if (!is_array($rs)) return false;
			return $rs;
		}else return false;
	}
	
	/**
	@ From memory get user info.
	*/
	public function mgetUserById()
	{
		if (!defined(MEM_USER_KEY))
			define(MEM_USER_KEY, $_SESSION['id']. 'user');
		return unserialize($this->m_m->get(MEM_USER_KEY));
	}

	public function msetUserById($value)
	{
		if (!defined(MEM_USER_KEY))
			define(MEM_USER_KEY, $_SESSION['id']. 'user');
		$this->m_m->set(array('k' =>MEM_USER_KEY, 'v' => $value));
		unset($value);
	}

	/**
	@ Get user info by sql.
	*/
	public function getUserBySql($sql)
	{
		if ($this->check(array('string' => $sql)) === true)
		{
			$rs = $this->m_db->getOneRecord($sql);
			if (!is_array($rs)) return false;
			return $rs;
		}else return false;
	}

	/**
	@ Get user pets by id
	*/
	public function getUserPetById($id)
	{
		if ($this->check(array('int' => $id)) === true)
		{
			$rs = $this->m_db->getRecords("SELECT *
											 FROM userbb
											WHERE uid={$id}
											ORDER BY level DESC
										 ");
			if ($this->check(array('array' => $rs))===true)
		    {
			   return $rs;
		    }
		}
		return false;
	}
	
	/**
	@ Get user pets by id
	*/
	public function getUserPetByIdS($uid,$id)
	{
		if(intval($uid)<1||intval($id)<1) return false;
		$rs = $this->m_db->getOneRecord("SELECT *
										 FROM userbb
										WHERE uid={$uid} and id ={$id} 
										ORDER BY level DESC
									 ");
		if (is_array($rs))
		{
		   return $rs;
		}
		return false;
	}

	/**
	@ From memory get userbb info.
	*/
	public function mgetUserPetById()
	{
		if (!defined(MEM_USERBB_KEY))
			define(MEM_USERBB_KEY, $_SESSION['id']. 'bb');
		return unserialize($this->m_m->get(MEM_USERBB_KEY));
	}

	public function msetUserPetById($value)
	{
		if (!defined(MEM_USERBB_KEY))
			define(MEM_USERBB_KEY, $_SESSION['id']. 'bb');
		$this->m_m->set(array('k' =>MEM_USERBB_KEY, 'v' => $value));
		unset($value);
	}

	/**
	@ Get user pets skill by id
	*/
	public function getUserPetSkillById($id)
	{
		if ($this->check(array('int' => $id)) === true)
		{
			$rs = $this->m_db->getRecords("SELECT s.*,
												  b.uid as uid
											 FROM userbb as b, skill as s
					 						WHERE b.uid={$id} and b.id=s.bid
										 ");
			if ($this->check(array('array' => $rs))===true)
		    {
			   return $rs;
		    }
		}
		return false;
	}
	/**
	@ Get user pets skill by id
	*/
	public function getUserPetSkillByIdS($uid,$id,$sid)
	{
		if(intval($uid)<1||intval($id)<1) return false;
		$rs = $this->m_db->getOneRecord("SELECT s.*,
											  b.uid as uid
										 FROM userbb as b, skill as s
										WHERE b.uid={$uid} and b.id=s.bid and s.bid={$id} and s.sid={$sid}
										ORDER BY s.level
									 ");
		if (is_array($rs))
		{
		   return $rs;
		}		
		return false;
	}

	/**
	@ From memory get userskill info.
	*/
	public function mgetUserPetSkillById()
	{
		if (!defined(MEM_USERSK_KEY))
			define(MEM_USERSK_KEY, $_SESSION['id']. 'sk');
		return unserialize($this->m_m->get(MEM_USERSK_KEY));
	}
    
	public function msetUserPetSkillById($value)
	{
		if (!defined(MEM_USERSK_KEY))
			define(MEM_USERSK_KEY, $_SESSION['id']. 'sk');
		$this->m_m->set(array('k' =>MEM_USERSK_KEY, 'v' => $value));
		unset($value);
	}

	/**
	@ From memory get userbag info.
	*/
	public function mgetUserBagById()
	{
		if (!defined(MEM_USERBAG_KEY))
			define(MEM_USERBAG_KEY, $_SESSION['id']. 'bag');
		return unserialize($this->m_m->get(MEM_USERBAG_KEY));
	}

	public function msetUserBagById($value)
	{
		if (!defined(MEM_USERBAG_KEY))
			define(MEM_USERBAG_KEY, $_SESSION['id']. 'bag');
		$this->m_m->set(array('k' =>MEM_USERBAG_KEY, 'v' => $value));
		unset($value);
	}

	/**
	@ Get Userbag by id.
	*/
	public function getUserBagById($id,$petId=0)
	{
		if ($this->check(array('int' => $id)) === true)
		{
			$rs = $this->m_db->getRecords("SELECT b.id as id,
												  b.uid as uid,
												  b.sums as sums,
												  b.pid as pid,
												  b.vary as vary,
												  b.psell as psell,
												  b.pstime as pstime,
												  b.petime as petime,
												  b.bsum as bsum,
												  b.psum as psum,
												  b.zbing as zbing,
												  b.zbpets as zbpets,
												  b.plus_tms_eft as plus_tmes_eft,
												  p.name as name,
												  p.varyname as varyname,
												  p.effect as effect,
												  p.requires as requires,
												  p.usages as usages,
							  					  p.sell as sell,
												  p.img as img,
												  p.expire as expire,
												  p.pluseffect as pluseffect,
												  p.postion as postion,
												  p.plusflag as plusflag,
												  p.pluspid as pluspid,
												  p.plusget as plusget,
												  p.plusnum as plusnum,
												  p.series as series,
												  p.serieseffect as serieseffect,
												  p.propslock as propslock,
												  p.prestige as prestige,
												  b.psj as psj,
												   b.pyb as pyb,
												  p.merge as merge,
												  b.cantrade as cantrade
										     FROM userbag as b,props as p
											WHERE p.id = b.pid and b.uid={$id} and (b.sums>0 or b.bsum>0 or b.psum>0 or b.pyb>0)
											".
											($petId==0?"":' and b.zbing=1 and b.zbpets='.$petId.' ')
											."
											");//ORDER BY b.pstime DESC using filesort
		   if ($this->check(array('array' => $rs))===true)
		   {
			  return $rs;
		   }
		}
		return false;
	}
	
	public function getUserItemById($uid,$id)
	{
		if ($this->check(array('int' => $id)) === true)
		{
			$rs = $this->m_db->getOneRecord("SELECT b.id as id,
												  b.uid as uid,
												  b.sums as sums,
												  b.pid as pid,
												  b.vary as vary,
												  b.psell as psell,
												  b.pstime as pstime,
												  b.petime as petime,
												  b.bsum as bsum,
												  b.psum as psum,
												  b.zbing as zbing,
												  b.zbpets as zbpets,
												  b.plus_tms_eft as plus_tmes_eft,
												  p.name as name,
												  p.varyname as varyname,
												  p.effect as effect,
												  p.requires as requires,
												  p.usages as usages,
							  					  p.sell as sell,
												  p.img as img,
												  p.pluseffect as pluseffect,
												  p.postion as postion,
												  p.plusflag as plusflag,
												  p.pluspid as pluspid,
												  p.plusget as plusget,
												  p.plusnum as plusnum,
												  p.series as series,
												  p.serieseffect as serieseffect,
												  p.propslock as propslock,
												  p.prestige as prestige
										     FROM userbag as b,props as p
											WHERE p.id = b.pid and b.uid={$uid} and b.id={$id} and (b.sums>0 or b.bsum>0 or b.psum>0)
											");//ORDER BY b.pstime DESC using filesort
		   if ($this->check(array('array' => $rs))===true)
		   {
			  return $rs;
		   }
		}
		return false;
	}
	
	
	public function getUserBagItemById($uid,$id)
	{
		if ($this->check(array('int' => $id)) === true)
		{
			$rs = $this->m_db->getOneRecord("SELECT b.id as id,
												  b.uid as uid,
												  b.sums as sums,
												  b.pid as pid,
												  b.vary as vary,
												  b.psell as psell,
												  b.pstime as pstime,
												  b.petime as petime,
												  b.bsum as bsum,
												  b.psum as psum,
												  b.zbing as zbing,
												  b.zbpets as zbpets,
												  b.plus_tms_eft as plus_tmes_eft,
												  p.name as name,
												  p.varyname as varyname,
												  p.effect as effect,
												  p.requires as requires,
												  p.usages as usages,
							  					  p.sell as sell,
												  p.img as img,
												  p.pluseffect as pluseffect,
												  p.postion as postion,
												  p.plusflag as plusflag,
												  p.pluspid as pluspid,
												  p.plusget as plusget,
												  p.plusnum as plusnum,
												  p.series as series,
												  p.serieseffect as serieseffect,
												  p.propslock as propslock,
												  p.prestige as prestige
										     FROM userbag as b,props as p
											WHERE p.id = b.pid and b.uid={$uid} and b.pid={$id} and (b.sums>0 or b.bsum>0 or b.psum>0)
											");//ORDER BY b.pstime DESC using filesort
		   if ($this->check(array('array' => $rs))===true)
		   {
			  return $rs;
		   }
		}
		return false;
	}
	
	
	// Use db data update
	public function updateMemUser($id)
	{
		if ($id == '' || $id<1) return false;
		$this->msetUserById($this->getUserById($id));
	}
	
	// update userbb
	public function updateMemUserbb($id)
	{
		if ($id == '' || $id<1) return false;
		$this->msetUserPetById($this->getUserPetById($id));
	}
	
	// update userskill
	public function updateMemUsersk($id)
	{
		if ($id == '' || $id<1) return false;
		$this->msetUserPetSkillById($this->getUserPetSkillById($id));
	}
	
	// update userskill
	public function updateMemUserbag($id)
	{
		if ($id == '' || $id<1) return false;
		$this->msetUserBagById($this->getUserBagById($id));
	}

	function __destruct(){
		//$this->m_db=null;
		//$this->m_m=null;
		//unset($this->m_db, $this->m_m);
	}
}
?>