<?php
/**

*/
class team
{
	private $m_db;	//	Db Handle

	private $m_m;	//	Memory Handle

	private $team_key_pre='pm_team_';
	private $team_key = '';
	private $team_list_key_pre='pm_list_team_';
	private $team_key_fight_pre='pm_team_fight_';
	private $id=0;
	private $members=array();
	private $socket=NULL;
	public $fbjindu='';

	function __construct($_id=0,$_s=NULL){
		global $_pm;
		if (!is_array($_pm) ||
			!is_object($_pm['mysql']) ||
			!is_object($_pm['mem'])
			)
		return false;
		$this-> id 	 = intval($_id);
		$this-> m_db = &$_pm['mysql'];
		$this-> m_m	 = &$_pm['mem'];
		$this-> team_key = $this->team_key_pre.$this->id;
		$this-> socket = is_object($_s) ? $_s : NULL;
	}

	function sendTeamMsg($msg,$users)
	{
		if(!is_object($this->socket) || !method_exists($this->socket, 'sendMsg')) return false;
		return $this->socket->sendMsg($msg,$users);
	}

	function syncChatTeamId($users,$teamId)
	{
		if(!is_array($users)) $users=array($users);
		$ids=array();
		foreach($users as $userId)
		{
			$userId=intval($userId);
			if($userId>0) $ids[$userId]=$userId;
		}
		if(empty($ids)) return true;
		return $this->m_db->query('update chat_login_auth set team_id='.max(0,intval($teamId)).' where uid in ('.implode(',',$ids).')');
	}

	//从内存读我所在队伍的信息，返回给客户端
	function getMyTeamInfo()
	{
		$teaminfo=$this->getTeamInfo();
		if(!is_array($teaminfo)) return '';
		$teamName = (isset($teaminfo['team']) && is_array($teaminfo['team']) && isset($teaminfo['team']['name'])) ? $teaminfo['team']['name'] : '';
		$tmp=$teamName.'@';
		if(isset($teaminfo['members']) && is_array($teaminfo['members']) && !empty($teaminfo['members'])){
			foreach($teaminfo['members'] as $row)
			{
				if(!is_array($row)) continue;
				$tmp.=intval($row['uid']).'|'.(isset($row['nickname']) ? $row['nickname'] : '').'|'.(isset($row['state']) ? $row['state'] : 0).'`';
			}
		}
		return $tmp;
	}

	//从内存中取我所在队伍的组队信息
	function getTeamInfo($tid="")
	{
		if($tid=="")
		{
			if(!isset($_SESSION['team_id']) || intval($_SESSION['team_id']) < 1)
			{
				$this->members=array();
				return array('team'=>array('name'=>''), 'members'=>array());
			}
			$tid=$_SESSION['team_id'];
		}
		$tid = intval($tid);
		if($tid < 1)
		{
			$this->members=array();
			return array('team'=>array('name'=>''), 'members'=>array());
		}
		$cacheKey = $this->team_key_pre.$tid;
		$ti=$this->m_m->get($cacheKey);
		if(!is_array($ti) || !isset($ti['team']) || !is_array($ti['team']) || !isset($ti['members']) || !is_array($ti['members']))
		{
			$teamRow = $this->m_db->getOneRecord('select id,name,creator,inmap from team where id='.$tid);
			$memberRows = $this->m_db->getRecords('select uid,state,nickname from team_members where team_id='.$tid.' order by apply_time');
			if(!is_array($teamRow)) $teamRow = array('name'=>'');
			if(!is_array($memberRows)) $memberRows = array();
			$ti = array('team'=>$teamRow, 'members'=>$memberRows);
			if(!empty($teamRow) && isset($teamRow['id'])) $this->m_m->setns($cacheKey,$ti);
		}
		if(!isset($ti['team']) || !is_array($ti['team'])) $ti['team'] = array('name'=>'');
		if(!isset($ti['members']) || !is_array($ti['members'])) $ti['members'] = array();
		$cacheChanged=false;
		foreach($ti['members'] as $memberKey=>$memberRow)
		{
			if(!is_array($memberRow)) continue;
			if(isset($memberRow['state']) && intval($memberRow['state'])>-1 && !isset($memberRow['living']))
			{
				$ti['members'][$memberKey]['living']=1;
				$cacheChanged=true;
			}
		}
		if($cacheChanged && isset($ti['team']['id'])) $this->m_m->setns($cacheKey,$ti);
		$this->members=$ti['members'];
		return $ti;
	}

	//从数据库取队伍信息放到内存
	function refreshTeamInfo()
	{
		if(!$this->id && isset($_SESSION['team_id'])) $this->id=intval($_SESSION['team_id']);
		if(intval($this->id) < 1) return false;
		if(!isset($this->members) || !is_array($this->members) || empty($this->members))
		{
			$cachedInfo=$this->getTeamInfo($this->id);
			$this->members=(is_array($cachedInfo) && isset($cachedInfo['members']) && is_array($cachedInfo['members'])) ? $cachedInfo['members'] : array();
		}
		$v['team'] = $this-> m_db->getOneRecord('select id,name,creator,inmap from team where id='.$this->id);
		$v['members'] = $this-> m_db->getRecords('select uid,state,nickname from team_members where team_id='.$this->id.' order by apply_time');
		if(!is_array($v['team'])) $v['team'] = array();
		if(!is_array($v['members'])) $v['members'] = array();
		//$teams=$this-> m_m ->get('MEM_TEAM_LIST');
		//$teams[$v['team']['id']]=array();
		if(is_array($v['members']) && count($v['members'])>0){
			foreach($v['members'] as $k=>$v1)
			{
				if(isset($v1['state']) && intval($v1['state'])>-1) $v['members'][$k]['living']=1;
				//if($v1['state']>-1) $teams[$v['team']['id']][]=$v1['uid'];
				if(!empty($this->members)){
					foreach($this->members as $kk=>$vv)
					{
						if(is_array($vv) && isset($vv['uid'],$v1['uid']) && $vv['uid']==$v1['uid'])
						{
							if(isset($vv['living']))
							{
								$v['members'][$k]['living']=$vv['living'];
							}
							break;
						}
					}
				}
			}
		}else{
			//echo 'select uid,state,nickname from team_members where team_id='.$this->id.' order by apply_time';
		}
		//$this->m_m->setns('MEM_TEAM_LIST',$teams);
		//memArr2Str('MEM_TEAM_LIST');
		$listStored=$this->updateTeamListMem();
		$stored=$this->m_m->setns($this->team_key_pre.$this->id,$v);
		if($stored) $this->members=$v['members'];
		return $stored && $listStored;
	}

	//把队伍列表保存到内存当中
	function refreshTeamList($inmap=0)
	{
		$inmap=intval($inmap);
		$teams=$this->m_db->getRecords('select team.id,team.name,count(team.id) ct from team,team_members where team.id=team_members.team_id and team.inmap='.$inmap.' and team.state=0 and team_members.state>-1 group by team.id,team.name');
		if(!is_array($teams)) $teams=array();
		$listStored=$this->m_m->setns($this->team_list_key_pre.$inmap,$teams);
		$timekey=$this->team_list_key_pre.$inmap.'_time';
		$timeStored=$this->m_m->setns($timekey,time().'');
		return $listStored && $timeStored;
	}

	//从内存中取得当前地图的队伍列表
	function getTeamList($time)
	{
		$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
		if($uid < 1) return '';
		$getorRow=$this->m_db->getOneRecord('select inmap from player where id='.$uid);
		if(!is_array($getorRow) || !isset($getorRow['inmap'])) return '';
		$inmap=intval($getorRow['inmap']);
		$updateTime=$this-> m_m	->get($this->team_list_key_pre.$inmap.'_time');
		$teams=$this->m_m->get($this->team_list_key_pre.$inmap);
		if(!is_array($teams) || $updateTime===false)
		{
			$this->refreshTeamList($inmap);
			$teams=$this->m_m->get($this->team_list_key_pre.$inmap);
			$updateTime=$this->m_m->get($this->team_list_key_pre.$inmap.'_time');
		}

		if($time==0)
		{
			$str='';
			if(is_array($teams)&&!empty($teams)){
				foreach($teams as $team)
				{
					if(!is_array($team)) continue;
					$str.=$team['id'].'|'.$team['name'].'|'.$team['ct'].'`';
				}
			}
			return $str;
		}
		else if($updateTime>$time)
		{
			return false;
		}
		else
		{
			return 'latest';
		}
	}

	//创建队伍
	function createTeam()
	{
		$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
		if($uid < 1) return false;
		$creatorRow=$this-> m_db->getOneRecord('select inmap,nickname from player where id='.$uid);
		if(!is_array($creatorRow) || !isset($creatorRow['inmap'],$creatorRow['nickname'])) return false;
		$creatorInmap=intval($creatorRow['inmap']);
		$nickname = $creatorRow['nickname'];
		$nicknameSql = $this->m_db->escape($nickname);
		if(!$this->m_db->query('BEGIN'))
		{
			return '队伍数据异常，请重试！';
		}
		$tmRow=$this->m_db->getOneRecord('select uid from team_members where uid='.$uid.' and state>-1 for update');
		if(!empty($tmRow))
		{
			$this->m_db->query('ROLLBACK');
			return '你已经加入队伍!';
		}
		$otherApplys=$this->m_db->getRecords(
			'select team.creator uid,team_members.team_id from team_members,team '.
			'where team_members.team_id=team.id and team_members.uid='.$uid.' and team_members.state=-1 for update'
		);
		if(!is_array($otherApplys)) $otherApplys = array();
		if(!$this->m_db->query('delete from team_members where uid='.$uid))
		{
			$this->m_db->query('ROLLBACK');
			return '队伍数据异常，请重试！';
		}
		$sql='insert into team set name="'.$nicknameSql.'",creator='.$uid.',inmap='.$creatorInmap.',create_time='.time();
		if(!$this->m_db->query($sql))
		{
			$this->m_db->query('ROLLBACK');
			return '队伍数据异常，请重试！';
		}
		$this->id = $this-> m_db->last_id();
		if(intval($this->id) <= 0)
		{
			$this->m_db->query('ROLLBACK');
			return '队伍数据异常，请重试！';
		}
		$newTeamId=intval($this->id);
		$now=time();
		$sql='insert into team_members set nickname="'.$nicknameSql.'",team_id='.$newTeamId.',uid='.$uid.',state=1,apply_time='.$now.',update_time='.$now;
		if(!$this->m_db->query($sql) || mysql_affected_rows($this->m_db->getConn()) != 1)
		{
			$this->m_db->query('ROLLBACK');
			return '队伍数据异常，请重试！';
		}
		if(!$this->m_db->query('COMMIT'))
		{
			$this->m_db->query('ROLLBACK');
			return '队伍数据异常，请重试！';
		}
		foreach($otherApplys as $r)
		{
			if(!is_array($r) || !isset($r['uid'],$r['team_id'])) continue;
			$oldCreator=intval($r['uid']);
			$oldTeamId=intval($r['team_id']);
			if($oldCreator < 1 || $oldTeamId < 1) continue;
			$this->id=$oldTeamId;
			$this->team_key=$this->team_key_pre.$oldTeamId;
			$this->refreshTeamInfo();
			$this->sendTeamMsg('SYSUTEAM|'.$oldTeamId,$oldCreator);
			$this->sendTeamMsg('SYSN|updateYouTeam',$oldCreator);
		}
		$this->id=$newTeamId;
		$this->team_key=$this->team_key_pre.$newTeamId;
		$this->refreshTeamList($creatorInmap);
		$_SESSION['team_id']=$newTeamId;
		$_SESSION['team_inmap']=$creatorInmap;
		$_SESSION['team_state']=1;
		$this->refreshTeamInfo();
		$this->syncChatTeamId($uid,$newTeamId);


		return true;
	}

	function inviteTeam($id)
	{
		$id=intval($id);
		$uid=isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
		if($id < 1 || $uid < 1) return false;
		if($id==$uid) return "不能邀请自己!";
		if(!isset($_SESSION['team_id'])) return "你没有队伍!";
		$tRow=$this-> m_db->getOneRecord('select id,inmap,name from team where creator='.$uid);
		if(!is_array($tRow) || !isset($tRow['id'], $tRow['inmap'], $tRow['name'])) $tRow=false;

		if(empty($tRow)||!$tRow)
		{
			return '你不是队长!';
		}

		$_SESSION['team_id'] = intval($tRow['id']);
		$_SESSION['team_inmap'] = intval($tRow['inmap']);
		$inviterName = $tRow['name'];
		$tRow=$this-> m_db->getOneRecord('select uid from team_members where uid='.$id.' and state>-1');
		if($tRow)
		{
			return '对方已经有了一个队伍!';
		}

		$tRow=$this-> m_db->getOneRecord('select inmap from player where id='.$id);
		if(!$tRow||!is_array($tRow)||!isset($tRow['inmap'])) return false;
		if($tRow&&$tRow['inmap']!=$_SESSION['team_inmap'])
		{
			return '对方不在这张地图!';
		}
		//5.3
		$nicknameHtml = htmlspecialchars($inviterName, ENT_QUOTES, 'UTF-8');
		$this->sendTeamMsg('SYS|$'.kdjlSafeIconv('utf-8','utf-8',$nicknameHtml.'` 邀请你加入他的队伍,<span style="cursor:pointer;color:#00ff00" onclick="doapplyTeam(\''.$_SESSION['team_id'].'\',\''.$_SESSION['team_inmap'].'\');"><strong>点击这里接受邀请</strong></span>。'),$id);
		return true;
	}

	//申请加入队伍
	function applyTeam($id)
	{
		$id=intval($id);
		if($id < 1) return false;
		$uid=isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
		if($uid < 1) return false;
		$now=time();
		$sql='delete from team_members where uid='.$uid.
			' and ((state=-1 and apply_time<'.($now-300).') or (state=-2 and apply_time<='.$now.'))';
		if(!$this->m_db->query($sql)) return '队伍数据异常，请重试！';

		$joined=$this->m_db->getOneRecord('select team_id from team_members where uid='.$uid.' and state>-1 limit 1');
		if(is_array($joined) && isset($joined['team_id'])) return '你已经加入队伍!';

		$applys=$this->m_db->getOneRecord('select count(uid) ct from team_members where uid='.$uid.' and state=-1 and apply_time>='.($now-300));

		if(is_array($applys) && isset($applys['ct']) && $applys['ct']>=5)
		{
			return '您在五分钟内向超过四个队伍申请加入,请稍等一会儿再重试!';
		}

		$tRow=$this->m_db->getOneRecord('select creator,name,state,inmap from team where id='.$id);
		if(!is_array($tRow) || !isset($tRow['creator'], $tRow['name'], $tRow['state'], $tRow['inmap'])) $tRow=false;
		if(!$tRow||empty($tRow))
		{
			return '队伍不存在!';
		}

		if($tRow['state']>0)
		{
			return '该队伍已经开始战斗!';
		}

		$tRowp=$this-> m_db->getOneRecord('select inmap,nickname from player where id='.$uid);
		if(!$tRowp||!is_array($tRowp)||!isset($tRowp['inmap'],$tRowp['nickname'])||$tRowp['inmap']!=$tRow['inmap'])
		{
			return '你不在队长所在的地图!';
		}

		$tmRow=$this->m_db->getOneRecord('select count(uid) ct from team_members where team_id='.$id.' and state>-1');
		if(!empty($tmRow)&&$tmRow['ct']>=5)
		{
			return $tRow['name'].'的队伍已经满员了!';
		}

		$eRow=$this->m_db->getOneRecord('select uid,state,apply_time from team_members where team_id='.$id.' and uid='.$uid);
		if(!empty($eRow))
		{
			$existingState=isset($eRow['state']) ? intval($eRow['state']) : -99;
			if($existingState==-2)
			{
				return '该队伍拒绝了你的申请，请十分钟后再试！';
			}
			if($existingState==-1){
				$nickname = $tRowp['nickname'];
				$nicknameHtml = htmlspecialchars($nickname, ENT_QUOTES, 'UTF-8');
				$this->sendTeamMsg(kdjlSafeIconv('utf-8','utf-8','SYSM|'.$nicknameHtml.'申请加入你的队伍！'),$tRow['creator']);
			}
			else if($existingState>-1)
			{
				return '你已经加入队伍!';
			}
			return '您已经申请过了，请耐心等待，或者密队长!';
		}

		$nickname = $tRowp['nickname'];
		$nicknameSql = $this->m_db->escape($nickname);
		$sql='insert into team_members set nickname="'.$nicknameSql.'",team_id='.$id.',uid='.$uid.',state=-1,apply_time=unix_timestamp() on duplicate key update nickname="'.$nicknameSql.'",state=-1,apply_time=unix_timestamp()';
		if($this->m_db->query($sql))
		{
			$this->id = $id;
			$this->team_key = $this->team_key_pre.$id;
			$this->refreshTeamInfo();
			$rs=$this->sendTeamMsg('SYSN|updateYouTeam',$tRow['creator']);
			return true;
		}else{
			return '队伍数据异常，请重试！';
		}
	}

	//队长同意用户的申请
	function permitTeam($id)
	{
		$id=intval($id);
		$uid=isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
		if($id < 1 || $uid < 1) return false;

		$tRow=$this-> m_db->getOneRecord('select id,inmap from team where creator='.$uid);
		if(!is_array($tRow) || !isset($tRow['id'], $tRow['inmap'])) $tRow=false;

		if(empty($tRow)||!$tRow)
		{
			return '你没有创建队伍!';
		}

		$teamId = intval($tRow['id']);
		$_SESSION['team_id'] = $teamId;
		$_SESSION['team_inmap'] = intval($tRow['inmap']);
		$tmRows=$this-> m_db->getRecords('select uid,state,team_id from team_members where uid='.$id.' and (team_id='.$teamId.' or state>-1)');

		if(empty($tmRows)||!$tmRows)
		{
			return '该玩家没有申请加入任何队伍!';
		}else{
			$flag=false;
			foreach($tmRows as $row)
			{
				if(!is_array($row) || !isset($row['state'], $row['team_id'])) continue;
				if($row['state']>-1&&$row['team_id']!=$teamId)
				{
					return '该玩家已经加入别的队伍了!';
				}
				if($row['team_id']==$teamId)
				{
					$flag=true;
				}
			}
			if(!$flag)
			{
				return '该玩家没有申请加入你的队伍,或者已经加入到别人的队伍!';
			}
		}

		if(!$this->m_db->query('BEGIN'))
		{
			return '队伍数据异常，请重试！';
		}
		$lockedTeam = $this->m_db->getOneRecord(
			'select id,inmap from team where id='.$teamId.' and creator='.$uid.' for update'
		);
		if(!is_array($lockedTeam))
		{
			$this->m_db->query('ROLLBACK');
			return '你没有创建队伍!';
		}
		$memberRows = $this->m_db->getRecords(
			'select team_id,state from team_members where uid='.$id.' order by team_id for update'
		);
		if(!is_array($memberRows))
		{
			$this->m_db->query('ROLLBACK');
			return '该玩家没有申请加入任何队伍!';
		}
		$hasPendingApply = false;
		foreach($memberRows as $memberRow)
		{
			if(!is_array($memberRow) || !isset($memberRow['team_id'],$memberRow['state'])) continue;
			$memberTeamId = intval($memberRow['team_id']);
			$memberState = intval($memberRow['state']);
			if($memberTeamId == $teamId && $memberState == -1) $hasPendingApply = true;
			if($memberTeamId != $teamId && $memberState > -1)
			{
				$this->m_db->query('ROLLBACK');
				return '该玩家已经加入别的队伍了!';
			}
		}
		if(!$hasPendingApply)
		{
			$this->m_db->query('ROLLBACK');
			return '该玩家没有申请加入你的队伍,或者已经加入到别人的队伍!';
		}

		$activeMembers = $this->m_db->getRecords(
			'select uid from team_members where team_id='.$teamId.' and state>-1 for update'
		);
		if(!is_array($activeMembers))
		{
			$this->m_db->query('ROLLBACK');
			return '队伍数据异常，请重试！';
		}
		if(count($activeMembers) >= 5)
		{
			$this->m_db->query('ROLLBACK');
			return '你的队伍已经有5名队员了!';
		}

		$otherApplys=$this->m_db->getRecords(
			'select team.creator uid,team_members.team_id from team_members,team '.
			'where team_members.team_id=team.id and team_members.uid='.$id.
			' and team_members.team_id<>'.$teamId.' and team_members.state<>-2'
		);
		if(!is_array($otherApplys)) $otherApplys = array();

		$sql='update team_members set state=0,update_time='.time().' where uid='.$id.' and team_id='.$teamId.' and state=-1';
		if(!$this->m_db->query($sql) || mysql_affected_rows($this->m_db->getConn()) != 1)
		{
			$this->m_db->query('ROLLBACK');
			return '队伍数据异常，请重试！';
		}
		$sql='delete from team_members where uid='.$id.' and team_id<>'.$teamId.' and state<>-2';
		if(!$this->m_db->query($sql))
		{
			$this->m_db->query('ROLLBACK');
			return '队伍数据异常，请重试！';
		}
		if(!$this->m_db->query('COMMIT'))
		{
			$this->m_db->query('ROLLBACK');
			return '队伍数据异常，请重试！';
		}
		$this->syncChatTeamId($id,$teamId);

		if(count($otherApplys)>0)
		{
			foreach($otherApplys as $r)
			{
				if(!is_array($r) || !isset($r['uid'],$r['team_id'])) continue;
				$creatorUid = intval($r['uid']);
				$_tid = intval($r['team_id']);
				if($creatorUid < 1 || $_tid < 1) continue;
				$this->id=$_tid;
				$this->refreshTeamInfo();
				$this->sendTeamMsg('SYSUTEAM|'.$_tid,$creatorUid);
				$this->sendTeamMsg('SYSN|updateYouTeam',$creatorUid);
			}
		}
		$this->id=$teamId;
		$this-> team_key = $this->team_key_pre.$this->id;
		$this->refreshTeamInfo();
		$this->refreshTeamList($tRow['inmap']);
		$teaminfo=$this->getTeamInfo();
		$mems=array();
		if(is_array($teaminfo) && isset($teaminfo['members']) && is_array($teaminfo['members'])) foreach($teaminfo['members'] as $row)
		{
			if($row['state']>-1)$mems[]=$row['uid'];
		}
		$this->sendTeamMsg('SYSUTEAM|'.$this->id,$mems);
		$this->sendTeamMsg('SYSN|updateYouTeam',$mems);
		return true;
	}

	//队长同意用户的申请
	function unpermitTeam($id)
	{
		$id=intval($id);
		$uid=isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
		if($id < 1 || $uid < 1) return false;

		$tRow=$this-> m_db->getOneRecord('select id,inmap from team where creator='.$uid);
		if(!is_array($tRow) || !isset($tRow['id'], $tRow['inmap'])) $tRow=false;

		if(empty($tRow)||!$tRow)
		{
			return '你没有创建队伍!';
		}

		$teamId = intval($tRow['id']);
		$_SESSION['team_id'] = $teamId;
		$_SESSION['team_inmap'] = intval($tRow['inmap']);
		$tmRows=$this-> m_db->getRecords('select uid,state,team_id from team_members where uid='.$id.' and team_id='.$teamId.' and state=-1');

		if(empty($tmRows)||!$tmRows)
		{
			return '该玩家没有申请加入你的队伍,或者已经加入到别人的队伍!';
		}else{
			$sql='update team_members set state=-2,apply_time='.(time()+600).' where uid='.$id.' and team_id='.$teamId.' and state=-1';
			if(!$this->m_db->query($sql) || mysql_affected_rows($this->m_db->getConn()) != 1)
			{
				return '队伍数据异常，请重试！';
			}
			$this->id=$teamId;
			$this-> team_key = $this->team_key_pre.$this->id;
			$this->refreshTeamInfo();
			$teaminfo=$this->getTeamInfo();
			$mems=array();
			if(is_array($teaminfo) && isset($teaminfo['members']) && is_array($teaminfo['members'])) foreach($teaminfo['members'] as $row)
			{
				if($row['state']>-1)$mems[]=$row['uid'];
			}
			$this->sendTeamMsg('SYSUTEAM|'.$this->id,$mems);
			$this->sendTeamMsg('SYSN|updateYouTeam',$mems);
			return '十分钟内，该玩家不能再次申请加入这个队伍！';
		}
	}

	//用户离开队伍
	function leaveTeam()
	{
		if(!isset($_SESSION['team_id'])||!$_SESSION['team_id'])
		{
			return "你没有加入队伍！";
		}

		$teamId = intval($_SESSION['team_id']);
		if($teamId < 1) return false;
		$this->id=$teamId;
		$tRow=$this->m_db->getOneRecord('select id,inmap,creator from team where id='.$teamId);
		if(!is_array($tRow) || !isset($tRow['creator'])) return '队伍数据异常，请重试！';
		if(intval($tRow['creator'])==intval($_SESSION['id'])) return '队长不能直接离队，请解散队伍！';
		$sql='delete from team_members where uid='.intval($_SESSION['id']).' and team_id='.$teamId.' and state in (0,1)';
		$deleteOk = $this->m_db->query($sql);

		if($deleteOk && mysql_affected_rows($this->m_db->getConn()) == 1)
		{
			$this->syncChatTeamId(intval($_SESSION['id']),0);
			$this->refreshTeamInfo();
			$this-> team_key = $this->team_key_pre.$teamId;
			if(is_array($tRow) && isset($tRow['inmap'])) $this->refreshTeamList($tRow['inmap']);
			$teaminfo=$this->getTeamInfo();
			unset($_SESSION['team_id']);
			unset($_SESSION['team_inmap']);
			unset($_SESSION['team_state']);
			$mems=array();
			$lems= array();
			if(is_array($teaminfo) && isset($teaminfo['members']) && is_array($teaminfo['members']) && !empty($teaminfo['members'])){
				foreach($teaminfo['members'] as $row)
				{
					if($row['state']>-1) {
						$mems[]=$row['uid'];
					}else{
						$lems[] = $row['uid'];
					}
				}
				$this->sendTeamMsg('SYSLTEAM|'.$this->id,$lems);
				$this->sendTeamMsg('SYSN|updateYouTeam',$mems);
			}
			return true;
		}else{
			return '队伍数据异常，请重试！';
		}
	}

	//检查用户是否被通过加入队伍,或者说用户是否有队伍!
	function checkMyTeam()
	{
		$uid=isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
		if($uid < 1) return false;
		$tRow=$this-> m_db->getOneRecord('select uid,team_id,state from team_members where uid='.$uid.' and state>-1 order by state desc,team_id limit 1');
		if(empty($tRow)||!$tRow||!is_array($tRow)||!isset($tRow['team_id']))
		{
			$this->syncChatTeamId($uid,0);
			unset($_SESSION['team_id']);
			unset($_SESSION['team_inmap']);
			unset($_SESSION['team_state']);
			return false;
		}
		$teamId = intval($tRow['team_id']);
		if($teamId < 1)
		{
			$this->syncChatTeamId($uid,0);
			unset($_SESSION['team_id']);
			unset($_SESSION['team_inmap']);
			unset($_SESSION['team_state']);
			return false;
		}
		$tmRow=$this-> m_db->getOneRecord('select id,inmap from team where id='.$teamId);
		if(!$tmRow||empty($tmRow)||!is_array($tmRow)||!isset($tmRow['inmap']))
		{
			$this->m_db->query('delete from team_members where uid='.$uid.' and team_id='.$teamId);
			$this->updateTeamListMem();
			$this->syncChatTeamId($uid,0);
			unset($_SESSION['team_id']);
			unset($_SESSION['team_inmap']);
			unset($_SESSION['team_state']);
			return false;
		}else{
			$teamChanged=!isset($_SESSION['team_id']) || intval($_SESSION['team_id'])!=$teamId;
			if($teamChanged)
			{
				$_SESSION['team_id']=$teamId;
				unset($_SESSION['team_state']);
			}
			if(!isset($_SESSION['team_state']) || $tRow['state']!=$_SESSION['team_state'])
			{
				$_SESSION['team_state']=$tRow['state'];
			}
			$_SESSION['team_inmap']=intval($tmRow['inmap']);
			$this->id=$teamId;
			$this->team_key=$this->team_key_pre.$teamId;
			$this->m_db->query('update team_members set update_time='.time().' where uid='.$uid.' and team_id='.$teamId.' and state>-1');
			$this->syncChatTeamId($uid,$teamId);

			return $tRow['state'];
		}
	}

	function isTeamLeader($id,$team_id)
	{
		$tRow=$this-> m_db->getOneRecord('select creator from team where id='.intval($team_id));
		if(empty($tRow)||!$tRow||$tRow['creator']!=intval($id))
		{
			return false;
		}else{
			return true;
		}
	}

	//队员改变自己在队伍中的状态
	function swapTeamState()
	{
		if(!isset($_SESSION['team_id']))
		{
			if(!$this->checkMyTeam()){
				return "你没有加入队伍!";
			}
		}
		$teamId = intval($_SESSION['team_id']);
		if($teamId < 1) return false;
		if($this->isTeamLeader(intval($_SESSION['id']),$teamId)) return '队长不能暂离队伍！';
		$sql='update team_members set state=1-state where uid='.intval($_SESSION['id']).' and team_id='.$teamId.' and state in (0,1)';
		$swapOk = $this->m_db->query($sql);

		if($swapOk && mysql_affected_rows($this->m_db->getConn()) == 1)
		{
			$this-> team_key = $this->team_key_pre.$teamId;
			$this->refreshTeamInfo();
			$this->id = $teamId;
			$teaminfo=$this->getTeamInfo();
			$mems=array();
			if(is_array($teaminfo) && isset($teaminfo['members']) && is_array($teaminfo['members'])) foreach($teaminfo['members'] as $row)
			{
				if($row['state']>-1) $mems[]=$row['uid'];
			}
			$this->sendTeamMsg('SYSN|updateYouTeam',$mems);
			return true;
		}else{
			return '队伍数据异常，请重试！';
		}
	}

	//解散队伍
	function disbandTeam($force=true)
	{
		if(!isset($_SESSION['team_id']))
		{
			if(!$this->checkMyTeam()){
				return "你没有队伍!";
			}
		}

		$sessionTeamId=isset($_SESSION['team_id']) ? intval($_SESSION['team_id']) : 0;
		if($force)
		{
			$tRow=$this->m_db->getOneRecord('select id from team where creator='.intval($_SESSION['id']).' limit 1');
			if(!is_array($tRow) || !isset($tRow['id'])) return '你没有创建队伍!';
			$teamId=intval($tRow['id']);
		}
		else
		{
			$teamId=$sessionTeamId;
		}
		if($teamId < 1) return false;
		if(!$this->m_db->query('BEGIN')) return '队伍数据异常，请重试！';
		$lockSql='select id,inmap,creator from team where id='.$teamId;
		if($force) $lockSql.=' and creator='.intval($_SESSION['id']);
		$tRow=$this->m_db->getOneRecord($lockSql.' for update');
		if(!is_array($tRow) || !isset($tRow['id'],$tRow['inmap']))
		{
			$this->m_db->query('ROLLBACK');
			return $force ? '你没有创建队伍!' : '队伍数据异常，请重试！';
		}
		$memberRows=$this->m_db->getRecords('select uid from team_members where team_id='.$teamId.' and state>-1 for update');
		if(!is_array($memberRows)) $memberRows=array();
		if(!$this->m_db->query('delete from team_members where team_id='.$teamId))
		{
			$this->m_db->query('ROLLBACK');
			return '队伍数据异常，请重试！';
		}
		if(!$this->m_db->query('delete from team where id='.$teamId) || mysql_affected_rows($this->m_db->getConn()) != 1)
		{
			$this->m_db->query('ROLLBACK');
			return '队伍数据异常，请重试！';
		}
		if(!$this->m_db->query('COMMIT'))
		{
			$this->m_db->query('ROLLBACK');
			return '队伍数据异常，请重试！';
		}

		$mems=array();
		foreach($memberRows as $row)
		{
			if(is_array($row) && isset($row['uid']) && intval($row['uid'])>0) $mems[]=intval($row['uid']);
		}
		if(!empty($mems))
		{
			$this->syncChatTeamId($mems,0);
			$this->sendTeamMsg('SYSLTEAM|'.$teamId,$mems);
			$this->sendTeamMsg('SYSN|disbandTeam',$mems);
		}
		$this->team_key=$this->team_key_pre.$teamId;
		$this->m_m->del($this->team_key);
		$this->m_m->del($this->team_key_fight_pre.$teamId);
		$this->refreshTeamList(intval($tRow['inmap']));
		$this->updateTeamListMem();
		if($sessionTeamId==$teamId)
		{
			unset($_SESSION['team_id']);
			unset($_SESSION['team_inmap']);
			unset($_SESSION['team_state']);
		}
		return true;
	}

	function autoDisbandTeam($inmap)
	{
		$inmap=intval($inmap);
		$cutoff=time()-900;
		$sql='select team.id,team.inmap from team inner join team_members '.
			'on team_members.team_id=team.id and team_members.uid=team.creator and team_members.state>-1 '.
			'where team.inmap='.$inmap.' and team_members.update_time<'.$cutoff;
		$tRows=$this->m_db->getRecords($sql);
		if(!is_array($tRows) || empty($tRows)) return 0;
		$mems=array();
		$inmaps=array();
		$removed=0;

		foreach($tRows as $tRow)
		{
			if(!is_array($tRow) || !isset($tRow['id'])) continue;
			$teamId = intval($tRow['id']);
			if($teamId < 1) continue;
			if(!$this->m_db->query('BEGIN')) continue;
			$locked=$this->m_db->getOneRecord(
				'select team.id,team.inmap from team inner join team_members '.
				'on team_members.team_id=team.id and team_members.uid=team.creator and team_members.state>-1 '.
				'where team.id='.$teamId.' and team_members.update_time<'.$cutoff.' for update'
			);
			if(!is_array($locked) || !isset($locked['inmap']))
			{
				$this->m_db->query('ROLLBACK');
				continue;
			}
			$memberRows=$this->m_db->getRecords('select uid from team_members where team_id='.$teamId.' and state>-1 for update');
			if(!is_array($memberRows)) $memberRows=array();
			if(!$this->m_db->query('delete from team_members where team_id='.$teamId) ||
			   !$this->m_db->query('delete from team where id='.$teamId) ||
			   mysql_affected_rows($this->m_db->getConn()) != 1 ||
			   !$this->m_db->query('COMMIT'))
			{
				$this->m_db->query('ROLLBACK');
				continue;
			}
			foreach($memberRows as $row)
			{
				if(is_array($row) && isset($row['uid']) && intval($row['uid'])>0) $mems[intval($row['uid'])]=intval($row['uid']);
			}
			$mapId=intval($locked['inmap']);
			$inmaps[$mapId]=$mapId;
			$this->team_key=$this->team_key_pre.$teamId;
			$this->m_m->del($this->team_key);
			$this->m_m->del($this->team_key_fight_pre.$teamId);
			$removed++;
		}
		if(!empty($mems)){
			$this->syncChatTeamId($mems,0);
			$this->sendTeamMsg('SYSLTEAM|no',$mems);
			$this->sendTeamMsg('SYSN|uareKicked',$mems);
		}
		foreach($inmaps as $inmap)
		{
			$this->refreshTeamList($inmap);
		}
		if($removed>0) $this->updateTeamListMem();
		return $removed;
	}

	//所有的组队队伍信息存在内存里面给聊天程序查询队聊资料
	function updateTeamListMem()
	{
		$mRow=$this-> m_db->getRecords('select uid,team_id from team_members where state>-1');

		if(empty($mRow)||!$mRow)
		{
			$arr=array();
			$stored=$this->m_m->setns('MEM_TEAM_LIST',$arr);
			memArr2Str($arr,'MEM_TEAM_LIST');
			return $stored;
		}

		$arr=array();
		foreach($mRow as $row)
		{
			if(!is_array($row) || !isset($row['team_id'],$row['uid'])) continue;
			$teamId=intval($row['team_id']);
			$memberUid=intval($row['uid']);
			if($teamId<1 || $memberUid<1) continue;
			$arr[$teamId][]=$memberUid;
		}
		$stored=$this->m_m->setns('MEM_TEAM_LIST',$arr);
		memArr2Str($arr,'MEM_TEAM_LIST');
		return $stored;
	}

	//踢出用户
	function kickMember($id,$sysForceKick=false)
	{
		$id=intval($id);
		if($id < 1) return false;
		if(!isset($_SESSION['team_id'])||!$_SESSION['team_id'])
		{
			return "你没有加入队伍！";
		}

		if(!$sysForceKick){
			$tRow=$this-> m_db->getOneRecord('select id,inmap,creator from team where creator='.intval($_SESSION['id']));
			if(empty($tRow)||!$tRow)
			{
				return '你没有创建队伍!';
			}
		}else{
			$tRow=$this-> m_db->getOneRecord('select id,inmap,creator from team where id='.intval($_SESSION['team_id']));
			if(empty($tRow)||!$tRow)
			{
				return '你的队伍信息丢失!';
			}
		}

		if(!is_array($tRow) || !isset($tRow['id'],$tRow['creator'])) return '队伍数据异常，请重试！';
		$teamId = intval($tRow['id']);
		if($teamId < 1) return '队伍数据异常，请重试！';
		$teamInmap = isset($tRow['inmap']) ? intval($tRow['inmap']) : 0;
		if($id==intval($tRow['creator']))
		{
			if($sysForceKick)
			{
				$disbanded=$this->disbandTeam(false);
				return $disbanded===true ? -1 : $disbanded;
			}
			return '队长不能被踢出，请解散队伍！';
		}

		$mRow=$this-> m_db->getOneRecord('select uid from team_members where team_id='.$teamId.' and uid='.$id.' and state>-1');

		if(empty($mRow)||!$mRow)
		{
			return '队伍中无此成员!';
		}

		$this->id=$teamId;

		$sql='delete from team_members where uid='.$id.' and team_id='.$teamId.' and state>-1';
		$deleteOk = $this->m_db->query($sql);

		if($deleteOk && mysql_affected_rows($this->m_db->getConn()) == 1)
		{
			$this->syncChatTeamId($id,0);
			$this-> team_key = $this->team_key_pre.$teamId;
			$this->refreshTeamInfo();
			$teaminfo=$this->getTeamInfo();
			$this->refreshTeamList($teamInmap);
			$mems=array();
			if(is_array($teaminfo) && isset($teaminfo['members']) && is_array($teaminfo['members'])) foreach($teaminfo['members'] as $row)
			{
				if($row['state']>-1) $mems[]=$row['uid'];
			}
			$this->sendTeamMsg('SYSN|updateYouTeam',$mems);
			$this->sendTeamMsg('SYSN|uareKicked',$id);
			$this->sendTeamMsg('SYSLTEAM|no',array($id));
			return true;
		}else{
			return '队伍数据异常，请重试！';
		}
	}

	function getTeamState()
	{
		if(!isset($_SESSION['team_id']) || intval($_SESSION['team_id']) < 1)
		{
			$this->monsters=array();
			return array('monsters'=>array());
		}
		$return = $this-> m_m->get($this->team_key_fight_pre.$_SESSION['team_id']);
		if(!is_array($return)) $return = array();
		if(!isset($return['monsters']) || !is_array($return['monsters'])) $return['monsters'] = array();
		$this->monsters=$return['monsters'];
		return $return;
	}

	//战斗结束，清理组信息
	function clearTeamState($autotimes=false)
	{
		if(!isset($_SESSION['team_id']) || intval($_SESSION['team_id']) < 1) return false;
		$dataNow=array(
				'fight_html'=>'',
				'fightgate_html'=>'',
				'monsters'=>array(),
				'cur_monster'=>array(),
				'exp_get'=>0,
				'money_get'=>0,
				'props_get'=>'',
				'monsters_last'=>array()
			);
		$oldData=$this->getTeamState();
		if(isset($oldData['team_fuben_flag']))
		{
			$dataNow['team_fuben_flag']=$oldData['team_fuben_flag'];
		}

		if(isset($oldData['team_fuben_step']))
		{
			$dataNow['team_select_map']=isset($oldData['team_select_map']) ? $oldData['team_select_map'] : 0;
			$dataNow['team_fuben_step']=$oldData['team_fuben_step'];
			$dataNow['team_fuben_boss']=isset($oldData['team_fuben_boss']) ? $oldData['team_fuben_boss'] : 0;
			$dataNow['team_fuben_card_step_num']=isset($oldData['team_fuben_card_step_num']) ? $oldData['team_fuben_card_step_num'] : 0;
			$dataNow['team_fuben_get_card_users']=isset($oldData['team_fuben_get_card_users']) ? $oldData['team_fuben_get_card_users'] : array();
			$dataNow['team_fuben_get_card_sj_users']=isset($oldData['team_fuben_get_card_sj_users']) ? $oldData['team_fuben_get_card_sj_users'] : array();
			$dataNow['fubensjoj']=isset($oldData['fubensjoj']) ? $oldData['fubensjoj'] : 0;
		}

		if($autotimes!==false)
		{
			$continueAuto = intval($autotimes) > 0 && !empty($oldData['autofighting']);
			$dataNow['autofighting'] = $continueAuto ? 1 : 0;
			$dataNow['autofight'] = $continueAuto ? 1 : 0;
		}
		$stored=$this->m_m->setns($this->team_key_fight_pre.intval($_SESSION['team_id']),$dataNow);
		if($stored) $this->updateListStr();
		return $stored;
	}


	//设置组队副本进度
	function setTeam_fuben_step($state)
	{
		if(!is_array($state)) return false;
		if(!isset($state['team_fuben_step']) || !is_array($state['team_fuben_step'])) $state['team_fuben_step']=array(0,0);
		if(!isset($state['team_fuben_step'][0])) $state['team_fuben_step'][0]=0;
		if(!isset($state['team_fuben_step'][1])) $state['team_fuben_step'][1]=0;
		$state['team_fuben_step'][0]=max(0,intval($state['team_fuben_step'][0]));
		$state['team_fuben_step'][1]=max(0,intval($state['team_fuben_step'][1]));
		$hasBoss=!empty($state['team_fuben_boss']);
		if($state['team_fuben_step'][0]+1>=3&&!$hasBoss){
			//$this->fbjindu=(1+$state['team_fuben_step'][0]).'关_怪物_________1111111__________';
			return 3;
		}
		if($state['team_fuben_step'][0]+1>=3&&$hasBoss)
		{
			$this->setTeamState(array('fubensjoj'=>0));
			$this->clearTeamFubenData();
			return 3;
		}
		$state['team_fuben_step'][1]++;
		if($state['team_fuben_step'][1]>5) return false;
		if($state['team_fuben_step'][1]==5)
		{
			$state['team_fuben_step'][0]++;
			$this->fbjindu=(1+$state['team_fuben_step'][0]).'关'.$state['team_fuben_step'][1];

			$state['team_fuben_step'][1]=0;

			//设置所有人为没有翻牌
			$this->setTeamState(array(
								'team_fuben_card_step_num'=>($state['team_fuben_step'][0]>=3?3:$state['team_fuben_step'][0]),
								'team_fuben_step'=>$state['team_fuben_step'],
								'team_fuben_flag'=>1,
								'team_fuben_get_card_users'=>array(),
								'team_fuben_get_card_sj_users'=>array()
								));
			if($state['team_fuben_step'][0]>=3)
			{
				return 2;
			}else{
				return $state['team_fuben_step'][0];
			}
		}
		$this->fbjindu=(1+$state['team_fuben_step'][0]).'关'.$state['team_fuben_step'][1];
		$this->setTeamState(
							array(
								'team_fuben_step'=>$state['team_fuben_step'],
								'team_fuben_flag'=>1
								)
							);
		return false;
	}

	//设置组队副本从头开始！
	function clearTeamFubenData()
	{
		return $this->setTeamState(
							array(
									'fubensjoj'=>0,
									'team_fuben_step'=>array(0,0),
									'team_fuben_card_step_num'=>0,
									'team_fuben_get_card_users'=>array(),
									'team_fuben_get_card_sj_users'=>array(),
									'monsters'=>array(),
									'team_fuben_boss'=>'',
									'cur_monster'=>array()
								)
							);
	}


	//取得当前应该翻哪关得牌
	function get_team_funben_card_step($uid=0,$type='')
	{
		$type=($type==='_sj') ? '_sj' : '';
		$ctype='team_fuben_get_card'.$type.'_users';

		if(!isset($_SESSION['team_id'])||$_SESSION['team_id']<1){
			return '0a';
		}
		$teamState=$this->getTeamState();

		if(!isset($teamState['team_fuben_flag'])||!$teamState['team_fuben_flag']||!isset($teamState['team_fuben_step'])||!is_array($teamState['team_fuben_step'])||!isset($teamState['team_fuben_step'][1])||!isset($teamState['team_fuben_card_step_num'])||$teamState['team_fuben_card_step_num']<0) return '0b';

		if($teamState['team_fuben_step'][1]>=3&&!$this->isTeamLeader($_SESSION['id'],$_SESSION['team_id']))
		{
			return '0c';
		}else if($teamState['team_fuben_step'][1]>=3&&$this->isTeamLeader($_SESSION['id'],$_SESSION['team_id'])){
			return 3;
		}

		if($uid==0) $uid=$_SESSION['id'];
		$uid=intval($uid);
		if($uid<1) return '0d';

		if(!isset($teamState[$ctype]) || !is_array($teamState[$ctype]) || !isset($teamState[$ctype][$uid]))
		{
			return $teamState['team_fuben_card_step_num'];
		}
		return '0d';
	}


	//设置一个人为已经翻了牌,有误,返回false
	function set_team_funben_card_prize_got($uid=0,$type='')
	{
		if(!isset($_SESSION['team_id']) || intval($_SESSION['team_id']) < 1) return false;
		if($uid==0) $uid=isset($_SESSION['id']) ? $_SESSION['id'] : 0;
		$uid=intval($uid);
		if($uid<1) return false;
		$type=($type==='_sj') ? '_sj' : '';

		$teamState=$this->getTeamState();
		$key = 'team_fuben_get_card'.$type.'_users';
		if(!isset($teamState[$key]) || !is_array($teamState[$key])) $teamState[$key] = array();
		$teamState[$key][$uid]=1;
		return $this->setTeamState(array($key=>$teamState[$key]));
	}



	//检查是不是所有人都翻了牌,有误,或者不是所有人翻了,返回false
	function check_team_funben_card_prize_all_got()
	{
		if(!isset($_SESSION['team_id']) || intval($_SESSION['team_id']) < 1) return false;
		$teamState=$this->getTeamState();
		$teamInfo=$this->getTeamInfo();
		$activeMembers=array();
		if(!is_array($teamInfo) || !isset($teamInfo['members']) || !is_array($teamInfo['members'])) return false;
		foreach($teamInfo['members'] as $mem)
		{
			if(is_array($mem) && isset($mem['state'],$mem['uid']) && intval($mem['state'])==1)
			{
				$activeMembers[intval($mem['uid'])]=true;
			}
		}
		$gotUsers = (isset($teamState['team_fuben_get_card_users']) && is_array($teamState['team_fuben_get_card_users'])) ? $teamState['team_fuben_get_card_users'] : array();
		if(empty($activeMembers)) return false;
		foreach($activeMembers as $memberUid=>$unused)
		{
			if(!isset($gotUsers[$memberUid]) || intval($gotUsers[$memberUid]) != 1) return false;
		}
		return true;
	}

	//设置队伍将要打的怪物,返回false表示失败,没有检查是不是队长
	function setTeamMonsters($str)
	{
		global $_pm;
		if(!isset($_SESSION['team_id']) || intval($_SESSION['team_id']) < 1){
			//echo 'no team';
			return false;
		}
		$strs=explode(',',$str);
		if(empty($strs)) return false;

		$memgpc = kdjlSafeMemValue($_pm['mem'] -> get('db_gpcid'), array());
		$gws=array();
		foreach($strs as $id)
		{
			if(intval($id)==0) continue;
			if(isset($memgpc[intval($id)])){
				$gws[]=$memgpc[intval($id)];
			}
		}
		if(empty($gws)) return false;
		//$this->fightStart($gws);

		return $this->setTeamState(array(
								'fight_html'=>'',
								'fightgate_html'=>'',
								'cur_monster'=>array(),
								'exp_get'=>0,
								'money_get'=>0,
								'props_get'=>'',
								'multi_monsters_next'=>array(),
								'monsters_last'=>array(),
								'monsters_tf_3'=>$gws,
								'monsters'=>array()
								), true);
	}

	//设置队伍的“整体”信息
	function setTeamState($data=array(),$replaceTotals=false)
	{
		if(empty($data) || !isset($_SESSION['team_id']) || intval($_SESSION['team_id']) < 1) return false;
		$dataNow=$this->getTeamState();

		foreach($data as $k=>$v)
		{
			switch($k)
			{
					case 'fubensjoj':
					case 'team_select_map':
					case 'monsters_tf_3':
					case 'team_fuben_boss':
					case 'team_fuben_card_step_num':
					case 'team_fuben_step':
					case 'team_fuben_get_card_users':
					case 'team_fuben_get_card_sj_users':
					case 'team_fuben_flag':
					case 'fight_html':
					case 'fightgate_html':
					case 'fighting':
					case 'monsters':
					case 'cur_monster':
					case 'multi_monsters_next':
					case 'monsters_last':
					case 'monsterliststr':
					case 'userliststr':
					case 'autofight'://是否有道具
					case 'autofighting'://是否开启了
						$dataNow[$k]=$v;
						break;
					case 'money_get':
					case 'exp_get':
						if($replaceTotals || !isset($dataNow[$k])) $dataNow[$k]=intval($v);
						else $dataNow[$k]+=intval($v);
						break;
					case 'props_get':
						$v = strval($v);
						if($replaceTotals || !isset($dataNow[$k]) || $dataNow[$k] === '') $dataNow[$k]=$v;
						else if($v !== '') $dataNow[$k].=(substr($dataNow[$k],-1)==',' ? '' : ',').$v;
						break;
				default:
					break;
			}
		}

		$stored = $this->m_m->setns($this->team_key_fight_pre.$_SESSION['team_id'],$dataNow);
		if($stored && isset($data['monsters']))
		{
			if($this->updateListStr()===false) return false;
		}
		return $stored;
	}

	//设置队员的状态为生存或者死亡,$uid=0时为所有队员设置生存状态,同时返回是不是还有玩家存活
	function setTeamMemberSate($uid,$live)
	{
		if(!isset($_SESSION['team_id']) || intval($_SESSION['team_id']) < 1) return false;
		$uid=intval($uid);
		$live=$live ? 1 : 0;
		$dataNow=$this->getTeamInfo();
		if(!empty($dataNow['members'])){
			foreach($dataNow['members'] as $k=>$v)
			{
				if(!is_array($v) || !isset($v['uid'])) continue;
				if(!isset($dataNow['members'][$k]['living'])) $dataNow['members'][$k]['living']=1;
				if($uid==0||intval($v['uid'])==$uid)
				{
					$dataNow['members'][$k]['living']=$live;//不能break,保证$uid=0时能够正常运行
				}
			}
		}

		if(!$this->m_m->setns($this->team_key_pre.$_SESSION['team_id'],$dataNow)) return false;
		$this->updateUserListStr();
		if(!empty($dataNow['members'])){
			foreach($dataNow['members'] as $k=>$v)
			{
				if(is_array($v) && !empty($v['living']) && isset($v['state']) && intval($v['state'])>0) return true;
			}
		}
		return false;
	}

	//所有宠物活或者死
	function reliveAll($st=1)
	{
		return $this->setTeamMemberSate(0,$st);
	}

	//开始战斗
	function fightStart($monsters)
	{
		if(!isset($_SESSION['team_id'],$_SESSION['id']) || intval($_SESSION['team_id'])<1 || intval($_SESSION['id'])<1) return false;
		$teamId=intval($_SESSION['team_id']);
		$uid=intval($_SESSION['id']);
		$oldFightState=$this->getTeamState();
		$oldTeamInfo=$this->getTeamInfo();
		if(!$this->m_db->query('BEGIN')) return false;
		$teamRow=$this->m_db->getOneRecord('select id,state from team where id='.$teamId.' and creator='.$uid.' for update');
		if(!is_array($teamRow) || !isset($teamRow['state']) || intval($teamRow['state'])!=0)
		{
			$this->m_db->query('ROLLBACK');
			return false;
		}
		if(!$this->reliveAll())
		{
			$this->m_db->query('ROLLBACK');
			return false;
		}
		$data=array();
		//$data['fight_pwd']=md5(time());
		$data['fighting']=1;
		//if(count($monsters)==1) $monsters=array();//只有一个怪物就不要保存了
		$data['monsters']=$monsters;
		$auto=$this->m_db->getOneRecord('select team_auto_times from player_ext b,team t where t.creator=b.uid and t.id='.$teamId.' limit 1');
		if($auto&&$auto['team_auto_times']>0)
		{
			$data['autofight']=1;
		}else{
			$data['autofight']=0;
		}
		$data['props_get']='';
		$data['exp_get']=0;
		$data['money_get']=0;
		if(!$this->setTeamState($data, true) ||
		   !$this->m_db->query('update team set state=1 where id='.$teamId.' and creator='.$uid.' and state=0') ||
		   mysql_affected_rows($this->m_db->getConn()) != 1 ||
		   !$this->m_db->query('COMMIT'))
		{
			$this->m_db->query('ROLLBACK');
			$this->m_m->setns($this->team_key_fight_pre.$teamId,$oldFightState);
			$this->m_m->setns($this->team_key_pre.$teamId,$oldTeamInfo);
			return false;
		}
		$this->refreshTeamList(isset($_SESSION['team_inmap']) ? intval($_SESSION['team_inmap']) : 0);
		return true;
	}

	//返回村庄
	function returnVi()
	{
		if(!isset($_SESSION['team_id'],$_SESSION['id']) || intval($_SESSION['team_id'])<1 || intval($_SESSION['id'])<1) return false;
		$teamId=intval($_SESSION['team_id']);
		$uid=intval($_SESSION['id']);
		$teamRow=$this->m_db->getOneRecord('select id,inmap from team where id='.$teamId.' and creator='.$uid);
		if(!is_array($teamRow) || !isset($teamRow['inmap'])) return false;
		if(!$this->m_db->query('update team set state=0 where id='.$teamId.' and creator='.$uid)) return false;
		$inmap=intval($teamRow['inmap']);
		$this->refreshTeamList($inmap);
		$this->snotice('returnVillege'.$inmap,NULL,$uid);
		return true;
	}

	//通过socket向其它成员传递消息
	function snotice($msg,$teaminfo=NULL,$exclude=array())
	{
		if(!is_array($exclude))$exclude=array($exclude);
		$excludeMap=array();
		foreach($exclude as $excludeUid)
		{
			$excludeUid=intval($excludeUid);
			if($excludeUid>0) $excludeMap[$excludeUid]=true;
		}
		if($teaminfo==NULL)
		{
			$teaminfo=$this->getTeamInfo();
		}
		$mems=array();
		$memberRows=array();
		if(is_array($teaminfo) && isset($teaminfo['members']) && is_array($teaminfo['members'])) $memberRows=$teaminfo['members'];
		else if(is_array($teaminfo)) $memberRows=$teaminfo;
		if(!empty($memberRows)){
			foreach($memberRows as $row)
			{
				if(!is_array($row) || !isset($row['uid'])) continue;
				$memberUid=intval($row['uid']);
				if($memberUid<1 || (isset($row['state']) && intval($row['state'])<0)) continue;
				if(!isset($excludeMap[$memberUid])){
					$mems[$memberUid]=$memberUid;
				}
			}
		}
		return $this->sendTeamMsg('SYSN|'.$msg,$mems);
	}

	//更新用户列表字符串
	function updateUserListStr(){
		$this->getTeamInfo();
		$str = '<div id="teamplayer" style="position:absolute; left:125px; top:85px; width: 185px; padding:0px;over-flow:hidden; z-index:10"> <table width="185" border="0">
  <tr>
    <td width="185" align="center" style="color:#006600;cursor:pointer;font-size:12px; background-repeat:no-repeat" onclick="if(document.getElementById(\'teamplayerlist\').style.display==\'none\'){document.getElementById(\'teamplayerlist\').style.display=\'block\'}else{document.getElementById(\'teamplayerlist\').style.display=\'none\'}" background="../new_images/ui/tl_03.png" height="23">队员列表</td>
  </tr>
  <tr id="teamplayerlist" style="display:none; font-size:12px">
    <td width="180" align="center">
	<div style="height:11px;background-image:url(../new_images/ui/tl_04.png);width:180px; background-repeat:no-repeat; background-position:left top"></div>
    '."<table wdith='180' border=0 cellspadding=0 cellspacing=0 id='teamplayerlistdetails' style='background-image:url(../new_images/ui/tl_05.png); background-repeat:repeat-y'>";
		$c = " style='color:#ff0000' ";
		if(!empty($this->members)){
			foreach($this->members as $v){
				if(!is_array($v)) continue;
				$living = isset($v['living']) ? intval($v['living']) : 1;
				$state = isset($v['state']) ? intval($v['state']) : 0;
				if($living&&$state==1){
					$memberName = htmlspecialchars(isset($v['nickname']) ? strval($v['nickname']) : '', ENT_QUOTES, 'UTF-8');
					$str .= "<tr><td width=180 $c>&nbsp;&nbsp;".$memberName."</td></tr>";
					$c = "";
				}
			}
		}
		$str .= "</table>".'
	<div style="height:11px;background-image:url(../new_images/ui/tl_06.png);width:180px; background-repeat:no-repeat; background-position:left top"></div>
    </td>
  </tr>
</table> </div>';
		$this->userListStr=$str;
		if(!$this->setTeamState(array('userliststr'=>$str))) return false;
		return $str;
	}

	//更新怪物列表字符串
	function updateListStr(){
		$this->getTeamState();
		$str = '<div id="mmonster" style="position:absolute; left:435px; top:35px; width: 185px; padding:0px;over-flow:hidden; z-index:10"> <table width="185" border="0">
  <tr>
    <td width="185" align="center" style="color:#006600;cursor:pointer;font-size:12px; background-repeat:no-repeat" onclick="if(document.getElementById(\'showmmonsterlist\').style.display==\'none\'){document.getElementById(\'showmmonsterlist\').style.display=\'block\'}else{document.getElementById(\'showmmonsterlist\').style.display=\'none\'}" background="../new_images/ui/tl_03.png" height="23">怪物列表</td>
  </tr>
  <tr id="showmmonsterlist" style="display:none; font-size:12px">
    <td width="180" align="center">
	<div style="height:11px;background-image:url(../new_images/ui/tl_04.png);width:180px; background-repeat:no-repeat; background-position:left top"></div>
    '."<table wdith='180' border=0 cellspadding=0 cellspacing=0 id=showmmonsterlistdetails style='background-image:url(../new_images/ui/tl_05.png); background-repeat:repeat-y'>";
		$c = " style='color:#ff0000' ";
		$monsterJsStr='';
		foreach($this->monsters as $_gw){
			if(!is_array($_gw)) continue;
			$gwName = isset($_gw['name']) ? $_gw['name'] : '';
			$gwLevel = isset($_gw['level']) ? intval($_gw['level']) : 0;
			$gwWx = isset($_gw['wx']) ? $_gw['wx'] : '';
			$gwAc = isset($_gw['ac']) ? intval($_gw['ac']) : 0;
			$gwMc = isset($_gw['mc']) ? intval($_gw['mc']) : 0;
			$gwHp = isset($_gw['hp']) ? intval($_gw['hp']) : 0;
			$gwMp = isset($_gw['mp']) ? intval($_gw['mp']) : 0;
			$gwSkill = isset($_gw['skill']) ? $_gw['skill'] : '';
			$gwImgStand = isset($_gw['imgstand']) ? $_gw['imgstand'] : '';
			$gwImgAck = isset($_gw['imgack']) ? $_gw['imgack'] : '';
			$gwImgDie = isset($_gw['imgdie']) ? $_gw['imgdie'] : '';
			$gwId = isset($_gw['id']) ? intval($_gw['id']) : 0;
			$gwNameHtml = htmlspecialchars(strval($gwName), ENT_QUOTES, 'UTF-8');
			$gwNameJs = json_encode(strval($gwName));
			$gwWxJs = json_encode(strval($gwWx));
			$gwSkillJs = json_encode(strval($gwSkill));
			$gwImgStandJs = json_encode(strval($gwImgStand));
			$gwImgAckJs = json_encode(strval($gwImgAck));
			$gwImgDieJs = json_encode(strval($gwImgDie));
			if($gwNameJs === false) $gwNameJs='""';
			if($gwWxJs === false) $gwWxJs='""';
			if($gwSkillJs === false) $gwSkillJs='""';
			if($gwImgStandJs === false) $gwImgStandJs='""';
			if($gwImgAckJs === false) $gwImgAckJs='""';
			if($gwImgDieJs === false) $gwImgDieJs='""';
			$str .= "<tr><td width=180 $c>&nbsp;&nbsp;Lvl:".$gwLevel."&nbsp;&nbsp;&nbsp;&nbsp;".$gwNameHtml."</td></tr>";
			$c = "";
			$monsterJsStr .= "
mmonsters[mmonsters.length]=[".$gwNameJs.",".$gwLevel.",".$gwWxJs.",".$gwAc.",".$gwMc.",".$gwHp.",".$gwMp.",".$gwSkillJs.",".$gwImgStandJs.",".$gwImgAckJs.",".$gwImgDieJs.",".$gwId."];
";
		}
		$str .= "</table>".'
	<div style="height:11px;background-image:url(../new_images/ui/tl_06.png);width:180px; background-repeat:no-repeat; background-position:left top"></div>
    </td>
  </tr>
</table> </div>';
		$str='<script language="javascript">var mmonsters=[];
'.$monsterJsStr.'</script>'.$str;
		if(!$this->setTeamState(array('monsterliststr'=>$str))) return false;
		$this->monsterListStr=$str;
		return $str;
	}

	function checkLost()
	{
		if(!isset($_SESSION['team_id']) || intval($_SESSION['team_id']) < 1) return false;
		$teamId = intval($_SESSION['team_id']);
		$tRow=$this->m_db->getOneRecord('select id,creator,inmap from team where id='.$teamId);
		if(!$tRow || !is_array($tRow) || !isset($tRow['creator'])) return false;
		$members=$this->m_db->getRecords('select uid,update_time from team_members where team_id='.$teamId.' and state>-1');
		if(!is_array($members)) $members = array();
		$remove=array();
		$removeCandidates=array();
		$leaderLost=false;
		foreach($members as $mem)
		{
			if(!is_array($mem) || !isset($mem['uid'], $mem['update_time'])) continue;
			if($mem['update_time']<time()-60)
			{
				$lostUid=intval($mem['uid']);
				if($lostUid==intval($tRow['creator']))
				{
					$leaderLost=true;
					break;
				}
				if($lostUid>0) $removeCandidates[]=$lostUid;
			}
		}
		if($leaderLost)
		{
			$disbanded=$this->disbandTeam(false);
			return $disbanded===true ? -1 : false;
		}
		foreach($removeCandidates as $lostUid)
		{
			if($this->m_db->query('delete from team_members where uid='.$lostUid.' and team_id='.$teamId.' and state>-1') && mysql_affected_rows($this->m_db->getConn())==1)
			{
				$remove[]=$lostUid;
			}
		}

		if(!empty($remove))
		{
			$this->syncChatTeamId($remove,0);
			$this->id=$teamId;
			$this->team_key=$this->team_key_pre.$teamId;
			$this->refreshTeamInfo();
			if(isset($tRow['inmap'])) $this->refreshTeamList(intval($tRow['inmap']));
			$this->snotice(kdjlSafeIconv('utf-8','utf-8',"C|<font color='#ff0000'>有队员断线了！</font>"));
			$this->sendTeamMsg('SYSLTEAM|no',$remove);
			$this->sendTeamMsg('SYSN|uareKicked',$remove);
		}
		return count($remove);
	}
	function getTarot(){
		return 1;
	}
}

function wr($somecontent,$line=0){
	$filename = dirname(__FILE__).'/log.txt';

    $handle = fopen($filename, 'a+');
	if(!$handle) return;
	$requestUri = (isset($_SERVER['REQUEST_URI']) && !is_array($_SERVER['REQUEST_URI'])) ? str_replace(array("\r","\n"), ' ', $_SERVER['REQUEST_URI']) : '';

	flock($handle, LOCK_EX);
    if (fwrite($handle, '
<-----------------------------------------------------------------
'.$requestUri.'
-----------------------------------------------------------------
	'.$somecontent."
----------------------------------------------------------------->
	") === FALSE) {
        //exit;
    }

	flock($handle, LOCK_UN);
    fclose($handle);
}
?>
