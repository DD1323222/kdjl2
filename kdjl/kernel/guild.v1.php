<?php
/**

*/
class guild
{
	private $m_db;	//	Db Handle

	private $m_m;	//	Memory Handle

	private $socket=NULL;
	
	function __construct(&$_s){
		global $_pm;
		if (!is_array($_pm) || 
			!is_object($_pm['mysql']) || 
			!is_object($_pm['mem'])
			)
		return false;
		$this-> m_db = &$_pm['mysql'];
		$this-> m_m	 = &$_pm['mem'];
		$this-> socket = $_s;
	}
	//取得我的家族信息
	function getMyGuildInfo()
	{
		$info=$this->m_db->getOneRecord('select g.id,g.name,g.creator_id,g.president_id,g.honor,g.level,g.victory_times,g.failed_times,gm.honor ghonor from guild g,guild_members gm where gm.guild_id=g.id and gm.member_id='.$_SESSION['id']);
		return $info;
	}
	//家族站时间是否到了
	function checkGuildFightTime()
	{
		$week = date("N", time());
		$hourM= date("Hi", time());
		
		$battletimearr = unserialize($this-> m_m->get(MEM_TIME_KEY));
		
		foreach($battletimearr as $bv){
			if($bv['titles'] != "guild_battle")
			{
				continue;
			}
			if($week == $bv['days'] && ($hourM >= $bv['starttime'] && $hourM < $bv['endtime'])){//战场已经开始
				return true;
			}
		}
		return false;
	}
	
	function getChanllengeGuildInfo($id)
	{
		$info=$this->m_db->getRecords('select challenger_id,defenser_id,challenger_score,defenser_score from guild_challenges where flags=1 and challenger_id='.$id.' or defenser_id='.$id);
		if(!$info)
		{
			return "您的家族既没有接受挑战，也没有家族接受您的挑战！";
		}
		
		if(count($info)>1)
		{
			return "您的家族挑战数据多余一条！";
		}
		
		return $info[0];
	}

	//取得挑战我的或者被挑战的家族的成员
	function getChanllengeGuildMembers($id)
	{
		$info=$this->m_db->getRecords('select member_id,priv from guild_members where guild_id='.$id);
		if(!$info)
		{
			return "敌方家族成员数据错误！";
		}
		return $info;
	}
	
	//清除家族战相关session信息
	function clearGuildFightSession()
	{
		unset($_SESSION['guild_fight_id']);
		unset($_SESSION['guild_fight_time']);
		unset($_SESSION['guild_fight_bid']);
	}	

	//家族战斗结束保存积分等
	function writeGuildFightScore($winnerId,$loserId)
	{
		$this->m_db->query('BEGIN');
		$winner		=$this->m_db->getOneRecord('select guild_id,honor from guild_members where member_id='.$winnerId.' for update');
		$loser 		=$this->m_db->getOneRecord('select guild_id,honor from guild_members where member_id='.$loserId);

		$winnerGuild=$this->m_db->getOneRecord('select id,honor,level from guild where id='.$winner['guild_id'].' for update');
		$loserGuild =$this->m_db->getOneRecord('select id,honor,level from guild where id='.$loser['guild_id']);

		$challenge	=$this->m_db->getOneRecord('select id,challenger_id from guild_challenges where (challenger_id='.$winner['guild_id'].' and defenser_id='.$loser['guild_id'].') or (challenger_id='.$loser['guild_id'].' and defenser_id='.$winner['guild_id'].') limit 1');
		
		$challenge	=$this->m_db->getOneRecord('select id,challenger_id,defenser_id,challenger_score,defenser_score from guild_challenges where id='.$challenge['id'].' for update');

		if(!$winner||!$loser||!$winnerGuild||!$loserGuild||!$challenge)
		{
			$this->m_db->query('ROLLBACK');
			return "分配奖励时发生错误，战败或者战胜方数据无法读取(-".mysql_error().")！";
		}
		
		$honorGet=(10*(1+($loserGuild['level']-$winnerGuild['level'])*0.1));
		$guildSql='update guild set honor='.($winnerGuild['honor']+$honorGet).' where honor='.intval($winnerGuild['honor']).' and id='.$winner['guild_id'];
		$this->m_db->query($guildSql);
		$memSql='update guild_members set honor='.($winner['honor']+$honorGet).' where honor='.intval($winner['honor']).' and member_id='.$winnerId;
		//echo $memSql;
		$this->m_db->query($memSql);
		
		//$this->m_db->query('update guild_members set honor = honor+ '.$honorGet.' where member_id='.$winnerId);
		if($winner['guild_id']==$challenge['challenger_id'])
		{
			$challengeSql='update guild_challenges set challenger_score='.($challenge['challenger_score']+1).' where id='.$challenge['id'].' and challenger_score='.$challenge['challenger_score'];
		}else{
			$challengeSql='update guild_challenges set defenser_score='.($challenge['defenser_score']+1).' where id='.$challenge['id'].' and defenser_score='.$challenge['defenser_score'];
		}
		$this->m_db->query($challengeSql);
		//wr($challengeSql);
		//echo mysql_error();
		if(!$err=mysql_error()){	
			$this->m_db->query('COMMIT');
			return '胜利方获得荣誉：'.$honorGet.'。';
		}else{
			$this->m_db->query('ROLLBACK');
			return '分配奖励时发生错误，更新数据失败(-'.mysql_error().')！';
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
}
?>