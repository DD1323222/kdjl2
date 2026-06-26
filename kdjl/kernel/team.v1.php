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
		
	function __construct($_id=0,&$_s){
		global $_pm;
		if (!is_array($_pm) || 
			!is_object($_pm['mysql']) || 
			!is_object($_pm['mem'])
			)
		return false;
		$this-> id 	 = $_id;
		$this-> m_db = &$_pm['mysql'];
		$this-> m_m	 = &$_pm['mem'];
		$this-> team_key = $this->team_key_pre.$this->id;
		$this-> socket = $_s;
	}
	
	//从内存读我所在队伍的信息，返回给客户端
	function getMyTeamInfo()
	{		
		$teaminfo=$this->getTeamInfo();
		$tmp=$teaminfo['team']['name'].'@';
		if(!empty($teaminfo['members'])){
			foreach($teaminfo['members'] as $row)
			{
				$tmp.=$row['uid'].'|'.$row['nickname'].'|'.$row['state'].'`';
			}
		}
		return $tmp;
	}
	
	//从内存中取我所在队伍的组队信息
	function getTeamInfo($tid="")
	{
		if($tid=="") $tid=$_SESSION['team_id'];
		$ti=$this->m_m->get($this->team_key_pre.$tid);
		$this->members=$ti['members'];
		return $ti;
	}
	
	//从数据库取队伍信息放到内存
	function refreshTeamInfo()
	{
		if(!$this->id&&$_SESSION['team_id']) $this->id=$_SESSION['team_id'];
		if(!isset($this->members)||empty($this->members)) $this->getTeamInfo();
		$v['team'] = $this-> m_db->getOneRecord('select id,name,creator,inmap from team where id='.$this->id);
		$v['members'] = $this-> m_db->getRecords('select uid,state,nickname from team_members where team_id='.$this->id.' order by apply_time');
		//$teams=$this-> m_m ->get('MEM_TEAM_LIST');
		//$teams[$v['team']['id']]=array();
		if(count($v['members'])>0&&is_array($v['members'])){
			foreach($v['members'] as $k=>$v1)
			{
				//if($v1['state']>-1) $teams[$v['team']['id']][]=$v1['uid'];
				if(!empty($this->members)){
					foreach($this->members as $kk=>$vv)
					{
						if($vv['uid']==$v1['uid'])
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
		$this->updateTeamListMem();
		return $this->m_m->setns($this->team_key_pre.$this->id,$v);
	}
	
	//把队伍列表保存到内存当中
	function refreshTeamList($inmap=0)
	{
		$teams=$this->m_db->getRecords('select team.id,team.name,count(team.id) ct from team,team_members where team.id=team_members.team_id and team.inmap='.$inmap.' and team.state=0 and team_members.state>-1 group by team.id');
		$this->m_m->setns($this->team_list_key_pre.$inmap,$teams);
		$timekey=$this->team_list_key_pre.$inmap.'_time';
		$this->m_m->setns($timekey,time().'');
	}
	
	//从内存中取得当前地图的队伍列表
	function getTeamList($time)
	{
		$getorRow=$this->m_db->getOneRecord('select inmap from player where id='.$_SESSION['id']);
		$inmap=$getorRow['inmap'];
		$updateTime=$this-> m_m	->get($this->team_list_key_pre.$inmap.'_time');
	
		if($time==0)
		{
			$teams=$this->m_m->get($this->team_list_key_pre.$inmap);
			if(!empty($teams)&&count($teams)>0){
				foreach($teams as $team)
				{
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
		$tmRow=$this->m_db->getOneRecord('select uid from team_members where uid='.$_SESSION['id'].' and state>-1');	
		
		if(!empty($tmRow))
		{
			return '你已经加入队伍!';
		}

		$otherApplys=$this-> m_db->getRecords('select team.creator uid,team_members.team_id from team_members,team where team_members.team_id=team.id and team_members.uid='.$_SESSION['id']);
		$this->m_db->query('delete from team_members where uid='.$_SESSION['id']);
		if(count($otherApplys)>0)
		{
			$mems=array();
			$_ts=array();
			foreach($otherApplys as $r)
			{				
				$mems[]=$r['uid'];
				$_ts[]=$r['team_id'];	
			}

			foreach($_ts as $_tid)
			{
				$this->id=$_tid;
				$this->refreshTeamInfo();
			}
			$this->socket->sendMsg('SYSUTEAM|'.$this->id,$mems);	

			$this->socket->sendMsg('SYSN|updateYouTeam',$mems);			
		}

		
		$creatorRow=$this-> m_db->getOneRecord('select inmap from player where id='.$_SESSION['id']);
		$sql='insert into team set name="'.$_SESSION['nickname'].'",creator='.$_SESSION['id'].',inmap='.$creatorRow['inmap'].',create_time='.time();
		$this-> m_db->query($sql);
		$this->id = $this-> m_db->last_id();
		$sql='insert into team_members set nickname="'.$_SESSION['nickname'].'",team_id='.$this->id.',uid='.$_SESSION['id'].',state=1';
		$this-> m_db->query($sql);
		if(!mysql_error())
		{
			$this->refreshTeamList($creatorRow['inmap']);
			$_SESSION['team_id']=$this->id;
			$this->refreshTeamInfo();
			
			
			return true;
		}else{
			return mysql_error();
		}
	}
	
	function inviteTeam($id)
	{
		$id=intval($id);
		if($id==$_SESSION['id']) return "不能邀请自己!";
		if(!isset($_SESSION['team_id'])) return "你没有队伍!";
		$tRow=$this-> m_db->getOneRecord('select id,inmap from team where creator='.$_SESSION['id']);	
		
		if(empty($tRow)||!$tRow)
		{
			return '你不是队长!';
		}
		
		$tRow=$this-> m_db->getOneRecord('select uid from team_members where uid='.$id.' and state>-1');		
		if($tRow)
		{
			return '对方已经有了一个队伍!';
		}
		
		$tRow=$this-> m_db->getOneRecord('select inmap from player where id='.$id);		
		if($tRow&&$tRow['inmap']!=$_SESSION['team_inmap'])
		{
			return '对方不在这张地图!';
		}
		//5.3
		$this->socket->sendMsg('SYS|$'.iconv('utf-8','utf-8',$_SESSION['nickname'].'` 邀请你加入他的队伍,<span style="cursor:pointer;color:#00ff00" onclick="doapplyTeam(\''.$_SESSION['team_id'].'\',\''.$_SESSION['team_inmap'].'\');"><strong>点击这里接受邀请</strong></span>。'),$id);
		return true;
	}
	
	//申请加入队伍
	function applyTeam($id)
	{
		
		$id=intval($id);
		$sql='delete from team_members where uid='.$_SESSION['uid'].' and apply_time<'.(time()-300);
		$this-> m_db->query($sql);
		
		$applys=$this->m_db->getOneRecord('select count(uid) ct from team_members where uid='.$_SESSION['id']);
		
		if($applys['ct']>=5)
		{
			return '您在五分钟内向超过四个队伍申请加入,请稍等一会儿再重试!';
		}
		
		$tRow=$this->m_db->getOneRecord('select creator,name,state,inmap from team where id='.$id);		
		if(!$tRow||empty($tRow))
		{
			return '队伍不存在!';
		}
		
		if($tRow['state']>0)
		{
			return '该队伍已经开始战斗!';
		}
				
		$tRowp=$this-> m_db->getOneRecord('select inmap from player where id='.$_SESSION['id']);		
		if(!$tRowp||$tRowp['inmap']!=$tRow['inmap'])
		{
			return '你不在队长所在的地图!';
		}
		
		$tmRow=$this->m_db->getOneRecord('select count(uid) ct from team_members where team_id='.$id.' and state>-1');
		if(!empty($tmRow)&&$tmRow['ct']>=5)
		{
			return $tRow['name'].'的队伍已经满员了!';
		}
		
		$eRow=$this->m_db->getOneRecord('select uid,state from team_members where team_id='.$id.' and uid='.$_SESSION['id']);
		if(!empty($eRow))
		{
			if($eRow['state']==-1){
				$this->socket->sendMsg(iconv('utf-8','utf-8','SYSM|'.$_SESSION['nickname'].'申请加入你的队伍！'),$tRow['creator']);
			}
			return '您已经申请过了，请耐心等待，或者密队长!';
		}
		
		$sql='insert into team_members set nickname="'.$_SESSION['nickname'].'",team_id='.$id.',uid='.$_SESSION['id'].',state=-1,apply_time=unix_timestamp() on duplicate key update state=-1,apply_time=unix_timestamp()';
		$this-> m_db->query($sql);
		if(!mysql_error())
		{
			$this->id = $id;
			$this->team_key = $this->team_key_pre.$id;
			$this->refreshTeamInfo();			
			$rs=$this->socket->sendMsg('SYSN|updateYouTeam',$tRow['creator']);
			return true;
		}else{
			return mysql_error();
		}
	}
	
	//队长同意用户的申请
	function permitTeam($id)
	{
		$id=intval($id);
		
		$tRow=$this-> m_db->getOneRecord('select id,inmap from team where creator='.$_SESSION['id']);	
		
		if(empty($tRow)||!$tRow)
		{
			return '你没有创建队伍!';
		}
		
		$tmRows=$this-> m_db->getRecords('select uid,state,team_id from team_members where uid='.$id.' and (team_id='.$_SESSION['team_id'].' or state>-1)');
		
		if(empty($tmRows)||!$tmRows)
		{
			return '该玩家没有申请加入任何队伍!';
		}else{
			$flag=false;
			foreach($tmRows as $row)
			{
				if($row['state']>-1&&$row['team_id']!=$_SESSION['team_id'])
				{
					return '该玩家已经加入别的队伍了!';
				}
				if($row['team_id']==$_SESSION['team_id'])
				{
					$flag=true;
				}
			}
			if(!$flag)
			{
				return '该玩家没有申请加入你的队伍,或者已经加入到别人的队伍!';
			}
		}
		
		$tmsRow=$this-> m_db->getOneRecord('select count(uid) ct from team_members where team_id='.$tRow['id'].' and state>-1');
		
		if(!empty($tmsRow)&&$tmsRow['ct']>=5)
		{
			return '你的队伍已经有5名队员了!';
		}
				
		$otherApplys=$this-> m_db->getRecords('select team.creator uid,team_members.team_id from team_members,team where team_members.team_id=team.id and team_members.uid='.$id.' and team_members.team_id<>'.$tRow['id']);
		
		$sql='update team_members set state=0 where uid='.$id.' and team_id='.$tRow['id'];
		$this-> m_db->query($sql);		
		$sql='delete from team_members where uid='.$id.' and team_id<>'.$tRow['id'].' and state<>-2';
		$this-> m_db->query($sql);
		
		if(count($otherApplys)>0)
		{
			$mems=array();
			$_ts=array();
			foreach($otherApplys as $r)
			{				
				$mems[]=$r['uid'];	
				$_ts[]=$r['team_id'];	
			}
			
			foreach($_ts as $_tid)
			{
				$this->id=$_tid;
				$this->refreshTeamInfo();
			}
			$this->socket->sendMsg('SYSUTEAM|'.$this->id,$mems);
			echo $this->socket->sendMsg('SYSN|updateYouTeam',$mems);			
		}
		if(!mysql_error())
		{
			$this->id=$tRow['id'];
			$this-> team_key = $this->team_key_pre.$this->id;
			$this->refreshTeamInfo();
			$this->refreshTeamList($tRow['inmap']);
			$teaminfo=$this->getTeamInfo();
			$mems=array();
			foreach($teaminfo['members'] as $row)
			{
				if($row['state']>-1)$mems[]=$row['uid'];
			}
			$this->socket->sendMsg('SYSUTEAM|'.$this->id,$mems);
			$this-> socket->sendMsg('SYSN|updateYouTeam',$mems);
			return true;
		}else{
			return mysql_error();
		}
	}
	
	//队长同意用户的申请
	function unpermitTeam($id)
	{
		$id=intval($id);
		
		$tRow=$this-> m_db->getOneRecord('select id,inmap from team where creator='.$_SESSION['id']);	
		
		if(empty($tRow)||!$tRow)
		{
			return '你没有创建队伍!';
		}
		
		$tmRows=$this-> m_db->getRecords('select uid,state,team_id from team_members where uid='.$id.' and team_id='.$_SESSION['team_id']);
		
		if(empty($tmRows)||!$tmRows)
		{
			return '该玩家没有申请加入你的队伍,或者已经加入到别人的队伍!';
		}else{
			$sql='update team_members set state=-2,apply_time='.(time()+600).' where uid='.$id.' and team_id='.$tRow['id'];
			$this-> m_db->query($sql);
			$this->id=$tRow['id'];
			$this-> team_key = $this->team_key_pre.$this->id;
			$this->refreshTeamInfo();
			$teaminfo=$this->getTeamInfo();
			$mems=array();
			foreach($teaminfo['members'] as $row)
			{
				if($row['state']>-1)$mems[]=$row['uid'];
			}
			$this->socket->sendMsg('SYSUTEAM|'.$this->id,$mems);
			$this-> socket->sendMsg('SYSN|updateYouTeam',$mems);
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
		
		$this->id=$_SESSION['team_id'];
		$sql='delete from team_members where uid='.$_SESSION['id'].' and state <>-2';
		$this-> m_db->query($sql);

		if(!mysql_error())
		{
			$this->refreshTeamInfo();
			$this-> team_key = $this->team_key_pre.$_SESSION['team_id'];
			$tRow=$this->m_db->getOneRecord('select id,inmap from team where id='.intval($_SESSION['team_id']));	
			$this->refreshTeamList($tRow['inmap']);			
			$teaminfo=$this->getTeamInfo();
			unset($_SESSION['team_id']);
			$mems=array();
			$lems= array();
			if(!empty($teaminfo['members'])){
				foreach($teaminfo['members'] as $row)
				{
					if($row['state']>-1) {
						$mems[]=$row['uid'];
					}else{
						$lems[] = $row['uid'];
					}
				}
				$this->socket->sendMsg('SYSLTEAM|'.$this->id,$lems);
				$this->socket->sendMsg('SYSN|updateYouTeam',$mems);
			}
			return true;
		}else{
			return mysql_error();
		}
	}
	
	//检查用户是否被通过加入队伍,或者说用户是否有队伍!
	function checkMyTeam()
	{
		$tRow=$this-> m_db->getOneRecord('select uid,team_id,state from team_members where uid='.$_SESSION['id'].' and state >-1 limit 1');
		$tmRow=$this-> m_db->getOneRecord('select id from team where id='.$tRow['team_id']);
		if(empty($tRow)||!$tRow||!$tmRow||empty($tmRow))
		{
			unset($_SESSION['team_id']);
			unset($_SESSION['team_inmap']);
			unset($_SESSION['team_state']);
			return false;
		}else{
			if(!isset($_SESSION['team_id'])){
				$_SESSION['team_id']=$tRow['team_id'];
			}
			if($tRow['state']!=$_SESSION['team_state'])
			{
				$_SESSION['team_state']=$tRow['state'];
			}
			if(!isset($_SESSION['team_inmap'])){
				$tmRow=$this-> m_db->getOneRecord('select inmap,state from team where id='.$tRow['team_id']);
				$_SESSION['team_inmap']=$tmRow['inmap'];
			}
			$this-> m_db->query('update team_members set update_time='.time().' where uid='.$_SESSION['id']);
			
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
		$sql='update team_members set state=abs(state-1) where uid='.$_SESSION['id'].' and state<>-1';
		$this-> m_db->query($sql);

		if(!mysql_error())
		{
			$this-> team_key = $this->team_key_pre.$_SESSION['team_id'];
			$this->refreshTeamInfo();
			$this->id = $_SESSION['team_id'];
			$teaminfo=$this->getTeamInfo();
			$mems=array();
			foreach($teaminfo['members'] as $row)
			{
				if($row['state']>-1) $mems[]=$row['uid'];
			}
			$this->socket->sendMsg('SYSN|updateYouTeam',$mems);
			return true;
		}else{
			return mysql_error();
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

		$tRow=$this-> m_db->getOneRecord('select id,inmap from team where creator='.$_SESSION['id']);
		if($force){
			if(empty($tRow)||!$tRow)
			{
				return '你没有创建队伍!';
			}
		}

		$sql='delete from team_members where team_id='.$tRow['id'];
		$this-> m_db->query($sql);
		$sql='delete from team where id='.$tRow['id'];
		$this-> m_db->query($sql);
		if(!mysql_error())
		{
			$this-> team_key = $this->team_key_pre.$_SESSION['team_id'];
			$teaminfo=$this->getTeamInfo();
			$mems=array();
			if(count($teaminfo['members'])>0){
				foreach($teaminfo['members'] as $row)
				{
					if($row['state']>-1) $mems[]=$row['uid'];
				}
				$this->socket->sendMsg('SYSLTEAM|'.$_SESSION['team_id'],$mems);
				$this->socket->sendMsg('SYSN|disbandTeam',$mems);
			}
			$this->m_m->del($this->team_key);
			$this->m_m->del($this->team_key_fight_pre.$_SESSION['team_id']);		
			$this->refreshTeamList($tRow['inmap']);		
			$this->updateTeamListMem();
			unset($_SESSION['team_id']);
			return true;
		}else{
			return mysql_error();
		}
	}
	
	function autoDisbandTeam($inmap)
	{
		$inmap=intval($inmap);
		$sql='select team.id,team.inmap from team,team_members where team.inmap='.$inmap.' and team_members.uid=team.creator and team_members.update_time+900<'.time();
		$tRows=$this-> m_db->getRecords($sql);

		//echo mysql_error().$sql;
		if(!$tRows) return;
		$mems=array();
		
		foreach($tRows as $tRow)
		{
			$sql='delete from team_members where team_id='.$tRow['id'];
			$this-> m_db->query($sql);
			$sql='delete from team where id='.$tRow['id'];
			$this-> m_db->query($sql);
			if(!mysql_error())
			{
				$this-> team_key = $this->team_key_pre.$tRow['id'];
				$teaminfo=$this->getTeamInfo($tRow['id']);
				if(count($teaminfo['members'])>0){
					foreach($teaminfo['members'] as $row)
					{
						$mems[]=$row['uid'];
					}
				}
				$this->m_m->del($this->team_key);
				$this->m_m->del($this->team_key_fight_pre.$tRow['id']);	
			}			
		}
		if(!empty($mems)){
			$this->socket->sendMsg('SYSLTEAM|no',$mems);
			$this->socket->sendMsg('SYSN|uareKicked',$mems);
		}
		$this->refreshTeamList($tRow['inmap']);
		$this->updateTeamListMem();
	}

	//所有的组队队伍信息存在内存里面给聊天程序查询队聊资料
	function updateTeamListMem()
	{
		$mRow=$this-> m_db->getRecords('select uid,team_id from team_members where state>-1');	

		if(empty($mRow)||!$mRow)
		{
			return;
		}

		$arr=array();
		foreach($mRow as $row)
		{
			$arr[$row['team_id']][]=$row['uid'];
		}
		$this->m_m->setns('MEM_TEAM_LIST',$arr);
		memArr2Str($arr,'MEM_TEAM_LIST');
	}
	
	//踢出用户
	function kickMember($id,$sysForceKick=false)
	{
		if(!isset($_SESSION['team_id'])||!$_SESSION['team_id'])
		{
			return "你没有加入队伍！";
		}
		
		if(!$sysForceKick){
			$tRow=$this-> m_db->getOneRecord('select id,inmap from team where creator='.$_SESSION['id']);	
			if(empty($tRow)||!$tRow)
			{
				return '你没有创建队伍!';
			}
		}else{
			$tRow=$this-> m_db->getOneRecord('select id,inmap from team where id='.$_SESSION['team_id']);	
			if(empty($tRow)||!$tRow)
			{
				return '你的队伍信息丢失!';
			}
		}
		
		$mRow=$this-> m_db->getOneRecord('select uid from team_members where team_id='.$tRow['id'].' and uid='.$id);	
		
		if(empty($mRow)||!$mRow)
		{
			return '队伍中无此成员!';
		}
		
		$this->id=$_SESSION['team_id'];
		
		$sql='delete from team_members where uid='.$id;
		$this-> m_db->query($sql);
		
		if(!mysql_error())
		{
			$this-> team_key = $this->team_key_pre.$_SESSION['team_id'];
			$this->refreshTeamInfo();
			$teaminfo=$this->getTeamInfo();
			$this->refreshTeamList($_SESSION['team_inmap']);
			$mems=array();
			foreach($teaminfo['members'] as $row)
			{
				if($row['state']>-1) $mems[]=$row['uid'];
			}
			$this->socket->sendMsg('SYSN|updateYouTeam',$mems);
			$this->socket->sendMsg('SYSN|uareKicked',$id);
			$this->socket->sendMsg('SYSLTEAM|no',array($id));
			return true;
		}else{
			return mysql_error();
		}
	}
	
	function getTeamState()
	{
		$return = $this-> m_m->get($this->team_key_fight_pre.$_SESSION['team_id']);
		$this->monsters=$return['monsters'];
		return $return;
	}
	
	//战斗结束，清理组信息
	function clearTeamState($autotimes=false)
	{
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
			$dataNow['team_select_map']=$oldData['team_select_map'];
			$dataNow['team_fuben_step']=$oldData['team_fuben_step'];
			$dataNow['team_fuben_boss']=$oldData['team_fuben_boss'];
			$dataNow['team_fuben_card_step_num']=$oldData['team_fuben_card_step_num'];
			$dataNow['team_fuben_get_card_users']=$oldData['team_fuben_get_card_users'];	
			$dataNow['team_fuben_get_card_sj_users']=$oldData['team_fuben_get_card_sj_users'];
			$dataNow['fubensjoj']=$oldData['fubensjoj'];		
		}

		if($autotimes!==false)
		{
			$dataNow['autofight']=$autotimes>1&&$dataNow['autofighting']?1:0;
		}
		$this-> m_m->setns($this->team_key_fight_pre.$_SESSION['team_id'],$dataNow);
		$this->updateListStr();
	}
	
	
	//设置组队副本进度
	function setTeam_fuben_step($state)
	{
		//if(!$state['team_fuben_flag']) return false;
		if(!isset($state['team_fuben_step']))
		{
			$state['team_fuben_step']=array(0,0);
		}
		if($state['team_fuben_step'][0]+1>=3&&!$state['team_fuben_boss']){
			//$this->fbjindu=(1+$state['team_fuben_step'][0]).'关_怪物_________1111111__________';
			return 3;
		}
		if($state['team_fuben_step'][0]+1>=3&&$state['team_fuben_boss'])
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
		$this->setTeamState(
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
		$ctype='team_fuben_get_card'.$type.'_users';
		
		if(!isset($_SESSION['team_id'])||$_SESSION['team_id']<1){
			return '0a';
		}
		$teamState=$this->getTeamState();
		
		if(!$teamState['team_fuben_flag']||!isset($teamState['team_fuben_step'])||!isset($teamState['team_fuben_card_step_num'])||$teamState['team_fuben_card_step_num']<0) return '0b';
				
		if($state['team_fuben_step'][1]>=3&&!$this->isTeamLeader($_SESSION['id'],$_SESSION['team_id']))
		{
			return '0c';
		}else if($state['team_fuben_step'][1]>=3&&$this->isTeamLeader($_SESSION['id'],$_SESSION['team_id'])){
			return 3;
		}
		
		if($uid==0) $uid=$_SESSION['id'];

		if(!isset($teamState[$ctype])||!isset($teamState[$ctype][$uid]))
		{	
			return $teamState['team_fuben_card_step_num'];
		}
		
		if(
			$this->isTeamLeader($uid,$_SESSION['team_id'])&&
			$teamState['team_fuben_card_step_num']==3&&
			(!isset($teamState[$ctype][$uid])||$teamState[$ctype][$uid]<2)
		)
		{
			if($teamState[$ctype][$uid]<2)
			{			
				$teamState[$ctype][$uid]=1;
				$this->setTeamState(
								array(
										$ctype=>$teamState[$ctype]
									)
								);
			}
			return 3;
		}		
		return '0d';
	}
	
		
	//设置一个人为已经翻了牌,有误,返回false
	function set_team_funben_card_prize_got($uid=0,$type='')
	{
		if($uid==0) $uid=$_SESSION['id'];
		if(!$_SESSION['team_id']) return false;
		
		$teamState=$this->getTeamState();
		$teamState['team_fuben_get_card'.$type.'_users'][$uid]=1;
		$this->setTeamState(array('team_fuben_get_card'.$type.'_users'=>$teamState['team_fuben_get_card'.$type.'_users']));
	}
	
	
	
	//检查是不是所有人都翻了牌,有误,或者不是所有人翻了,返回false
	function check_team_funben_card_prize_all_got()
	{
		if(!$_SESSION['team_id']) return false;
		$teamState=$this->getTeamState();
		$teamInfo=$this->getTeamInfo();
		$team_member_num=0;
		$ctM=0;
		foreach($teamInfo['members'] as $mem)
		{
			if($mem['state']==1)
			{
				$ctM++;
			}
		}
		return $ctM==count($teamState['team_fuben_get_card_users']);
	}
	
	//设置队伍将要打的怪物,返回false表示失败,没有检查是不是队长
	function setTeamMonsters($str)
	{
		global $_pm;
		if(!$_SESSION['team_id']){
			//echo 'no team';
			return false;
		}
		$strs=explode(',',$str);
		if(empty($strs)) return false;
		
		$memgpc = unserialize($_pm['mem'] -> get('db_gpcid'));
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
		
		$this->setTeamState(array(
								'fight_html'=>'',
								'fightgate_html'=>'',
								'monsters'=>array(),
								'cur_monster'=>array(),
								'exp_get'=>0,
								'money_get'=>0,
								'props_get'=>'',
								'multi_monsters_next'=>array(),			
								'monsters_last'=>array(),
								'monsters_tf_3'=>$gws,
								'monsters'=>array()
								));

		return true;
	}
	
	//设置队伍的“整体”信息
	function setTeamState($data=array())
	{
		if(!empty($data))
		{
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
					case 'monsterliststr':
					case 'userliststr':
					case 'autofight'://是否有道具
					case 'autofighting'://是否开启了
						$dataNow[$k]=$v;
						break;
					case 'money_get':
					case 'exp_get':
						$dataNow[$k]+=intval($v);
						break;
					case 'props_get':
						if(substr($dataNow[$k],-1)==','){
							$dataNow[$k].=$v;
						}else{
							$dataNow[$k].=','.$v;
						}
						break;
					default:					
						break;
				}
			}
			
			$this-> m_m->setns($this->team_key_fight_pre.$_SESSION['team_id'],$dataNow);
			if(isset($data['monsters']))
			{
				$this->updateListStr();
			}
		}
	}

	//设置队员的状态为生存或者死亡,$uid=0时为所有队员设置生存状态,同时返回是不是还有玩家存活
	function setTeamMemberSate($uid,$live)
	{
		$dataNow=$this->getTeamInfo();
		if(!empty($dataNow['members'])){
			foreach($dataNow['members'] as $k=>$v)
			{
				if($uid==0||$v['uid']==$uid)
				{
					$dataNow['members'][$k]['living']=$live;//不能break,保证$uid=0时能够正常运行
				}
			}
		}

		$this->m_m->setns($this->team_key_pre.$_SESSION['team_id'],$dataNow);
		$this->updateUserListStr();
		if(!empty($dataNow['members'])){
			foreach($dataNow['members'] as $k=>$v)
			{
				if($v['living']&&$v['state']>0) return true;
			}
		}
		return false;
	}	

	//所有宠物活或者死
	function reliveAll($st=1)
	{
		$this->setTeamMemberSate(0,$st);
	}
	
	//开始战斗
	function fightStart($monsters)
	{
		$this->reliveAll();
		$data=array();
		//$data['fight_pwd']=md5(time());
		$data['fighting']=1;
		//if(count($monsters)==1) $monsters=array();//只有一个怪物就不要保存了
		$data['monsters']=$monsters;
		$auto=$this->m_db->getOneRecord('select team_auto_times from player_ext b,team t where t.creator=b.uid and t.id='.$_SESSION['team_id'].' limit 1');
		if($auto&&$auto['team_auto_times']>0)
		{
			$data['autofight']=1;
		}else{
			$data['autofight']=0;
		}
		$data['props_get']='';
		$data['exp_get']=0;
		$data['money_get']=0;
		$this->setTeamState($data);
		$this->m_db->query('update team set state=1 where id='.$_SESSION['team_id'].' and creator='.$_SESSION['id']);
		$this->refreshTeamList($_SESSION['team_inmap']);
	}
	
	//返回村庄
	function returnVi()
	{
		$this->m_db->query('update team set state=0 where id='.$_SESSION['team_id'].' and creator='.$_SESSION['id']);
		$this->refreshTeamList($_SESSION['team_inmap']);
		$this->snotice('returnVillege'.$_SESSION['team_inmap'],NULL,$_SESSION['id']);
	}
	
	//通过socket向其它成员传递消息
	function snotice($msg,$teaminfo=NULL,$exclude=array())
	{
		if(!is_array($exclude))$exclude=array($exclude);
		if($teaminfo==NULL)
		{
			$teaminfo=$this->getTeamInfo();
		}
		$mems=array();
		if(!empty($teaminfo['members'])){
			foreach($teaminfo['members'] as $row)
			{
				if(!in_array($row['uid'],$exclude)){
					$mems[]=$row['uid'];
				}
			}
		}
		return $this->socket->sendMsg('SYSN|'.$msg,$mems);
	}
	
	//更新用户列表字符串
	function updateUserListStr(){		
		$this->getTeamInfo();		
		$lists = explode("|",$list);
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
				if($v['living']&&$v['state']==1){
					$str .= "<tr><td width=180 $c>&nbsp;&nbsp;".$v['nickname']."</td></tr>";
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
		$this->setTeamState(array('userliststr'=>$str));
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
			$tmp = explode(",",$v);
			$str .= "<tr><td width=180 $c>&nbsp;&nbsp;Lvl:".$_gw['level']."&nbsp;&nbsp;&nbsp;&nbsp;".$_gw['name']."</td></tr>";
			$c = "";
			$monsterJsStr .= "
mmonsters[mmonsters.length]=['{$_gw['name']}',{$_gw['level']},'{$_gw['wx']}',{$_gw['ac']},{$_gw['mc']},{$_gw['hp']},{$_gw['mp']},'{$_gw['skill']}','{$_gw['imgstand']}','{$_gw['imgack']}','{$_gw['imgdie']}',{$_gw['id']}];
";
			$connector = "|";
		}
		$str .= "</table>".'
	<div style="height:11px;background-image:url(../new_images/ui/tl_06.png);width:180px; background-repeat:no-repeat; background-position:left top"></div>
    </td>
  </tr>
</table> </div>';
		$str='<script language="javascript">var mmonsters=[];
'.$monsterJsStr.'</script>'.$str;
		$this->setTeamState(array('monsterliststr'=>$str));
		$this->monsterListStr=$str;
		return $str;
	}
	
	function checkLost()
	{
		$tRow=$this-> m_db->getOneRecord('select id,creator from team where id='.$_SESSION['team_id']);
		if(!$tRow) return;
		$members=$this-> m_db->getRecords('select uid,update_time from team_members where team_id='.$_SESSION['team_id']);
		$remove=array();
		foreach($members as $mem)
		{
			if($mem['update_time']<time()-60)
			{
				$remove[]=$mem['uid'];
				$this-> m_db->query('delete from team_members where uid='.$mem['uid'].' and team_id='.$_SESSION['team_id']);
			}
		}
		
		if(!empty($remove))
		{
			$this->id=$_SESSION['team_id'];
			$this->refreshTeamInfo();
			$this->snotice(iconv('utf-8','utf-8',"C|<font color='#ff0000'>有队员断线了！</font>"));
		}
	}
	function getTarot(){
		return 1;
	}
}

function wr($somecontent,$line=0){
	$filename = dirname(__FILE__).'/log.txt';

    $handle = fopen($filename, 'a+');

    if (fwrite($handle, '
<-----------------------------------------------------------------
'.$_SERVER['REQUEST_URI'].'
-----------------------------------------------------------------
	'.$somecontent."
----------------------------------------------------------------->	
	") === FALSE) {
        //exit;
    }

    fclose($handle);
}
?>