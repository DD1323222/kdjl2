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
		$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
		if($uid < 1) return false;
		$info=$this->m_db->getOneRecord('select g.id,g.name,g.creator_id,g.president_id,g.honor,g.level,g.victory_times,g.failed_times,gm.honor ghonor from guild g,guild_members gm where gm.guild_id=g.id and gm.member_id='.$uid);
		return $info;
	}
	//家族站时间是否到了
	function checkGuildFightTime()
	{
		$week = date("N", time());
		$hourM= date("Hi", time());

		$battletimearr = kdjlSafeMemValue($this-> m_m->get(MEM_TIME_KEY), array());
		if(!is_array($battletimearr)) $battletimearr = array();

		foreach($battletimearr as $bv){
			if(!is_array($bv) || !isset($bv['titles']) || $bv['titles'] != "guild_battle")
			{
				continue;
			}
			$days = isset($bv['days']) ? $bv['days'] : '';
			$starttime = isset($bv['starttime']) ? $bv['starttime'] : '';
			$endtime = isset($bv['endtime']) ? $bv['endtime'] : '';
			if(isWeeklyDayTimeActive($days, $starttime, $endtime, $week, $hourM, false)){//战场已经开始
				return true;
			}
		}
		return false;
	}

	function getChanllengeGuildInfo($id)
	{
		$id = intval($id);
		$info=$this->m_db->getRecords('select challenger_id,defenser_id,challenger_score,defenser_score from guild_challenges where flags=1 and (challenger_id='.$id.' or defenser_id='.$id.')');
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
		$id = intval($id);
		if($id < 1) return "敌方家族成员数据错误！";
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
		$winnerId = intval($winnerId);
		$loserId = intval($loserId);
		if($winnerId < 1 || $loserId < 1 || $winnerId == $loserId)
		{
			return '家族战数据错误！';
		}
		if(!$this->m_db->query('BEGIN'))
		{
			return '家族战结算事务启动失败！';
		}

		$firstMemberId = min($winnerId, $loserId);
		$secondMemberId = max($winnerId, $loserId);
		$memberRows = $this->m_db->getRecords('select member_id,guild_id,honor from guild_members where member_id in ('.$firstMemberId.','.$secondMemberId.') order by member_id for update');
		if(!is_array($memberRows) || count($memberRows) != 2)
		{
			$this->m_db->query('ROLLBACK');
			return '分配奖励时发生错误，战败或者战胜方数据无法读取！';
		}
		$members = array();
		foreach($memberRows as $memberRow)
		{
			$members[intval($memberRow['member_id'])] = $memberRow;
		}
		if(!isset($members[$winnerId], $members[$loserId]))
		{
			$this->m_db->query('ROLLBACK');
			return '分配奖励时发生错误，战败或者战胜方数据无法读取！';
		}
		$winner = $members[$winnerId];
		$loser = $members[$loserId];
		$winnerGuildId = intval($winner['guild_id']);
		$loserGuildId = intval($loser['guild_id']);
		if($winnerGuildId < 1 || $loserGuildId < 1 || $winnerGuildId == $loserGuildId)
		{
			$this->m_db->query('ROLLBACK');
			return '分配奖励时发生错误，战败或者战胜方数据无法读取！';
		}

		$firstGuildId = min($winnerGuildId, $loserGuildId);
		$secondGuildId = max($winnerGuildId, $loserGuildId);
		$guildRows = $this->m_db->getRecords('select id,honor,level from guild where id in ('.$firstGuildId.','.$secondGuildId.') order by id for update');
		if(!is_array($guildRows) || count($guildRows) != 2)
		{
			$this->m_db->query('ROLLBACK');
			return "分配奖励时发生错误，战败或者战胜方数据无法读取！";
		}
		$guilds = array();
		foreach($guildRows as $guildRow)
		{
			$guilds[intval($guildRow['id'])] = $guildRow;
		}
		if(!isset($guilds[$winnerGuildId], $guilds[$loserGuildId]))
		{
			$this->m_db->query('ROLLBACK');
			return "分配奖励时发生错误，战败或者战胜方数据无法读取！";
		}
		$winnerGuild = $guilds[$winnerGuildId];
		$loserGuild = $guilds[$loserGuildId];

		$challengeRows = $this->m_db->getRecords('select id,challenger_id,defenser_id,challenger_score,defenser_score from guild_challenges where flags=1 and ((challenger_id='.$winnerGuildId.' and defenser_id='.$loserGuildId.') or (challenger_id='.$loserGuildId.' and defenser_id='.$winnerGuildId.')) order by id for update');

		if(!is_array($challengeRows) || count($challengeRows) != 1)
		{
			$this->m_db->query('ROLLBACK');
			return "分配奖励时发生错误，战败或者战胜方数据无法读取！";
		}
		$challenge = $challengeRows[0];

		$honorGet = intval(round(10 * (1 + (intval($loserGuild['level']) - intval($winnerGuild['level'])) * 0.1)));
		$honorGet = max(5, min(15, $honorGet));
		$winnerGuildHonor = intval($winnerGuild['honor']);
		$winnerHonor = intval($winner['honor']);
		$guildSql='update guild set honor='.($winnerGuildHonor+$honorGet).' where honor='.$winnerGuildHonor.' and id='.$winnerGuildId;
		$guildUpdated = $this->m_db->query($guildSql);
		if(!$guildUpdated || mysql_affected_rows($this->m_db->getConn()) != 1)
		{
			$this->m_db->query('ROLLBACK');
			return '分配奖励时发生错误，更新数据失败！';
		}
		$memSql='update guild_members set honor='.($winnerHonor+$honorGet).' where honor='.$winnerHonor.' and member_id='.$winnerId.' and guild_id='.$winnerGuildId;
		//echo $memSql;
		$memberUpdated = $this->m_db->query($memSql);
		if(!$memberUpdated || mysql_affected_rows($this->m_db->getConn()) != 1)
		{
			$this->m_db->query('ROLLBACK');
			return '分配奖励时发生错误，更新数据失败！';
		}

		//$this->m_db->query('update guild_members set honor = honor+ '.$honorGet.' where member_id='.$winnerId);
		if($winnerGuildId==intval($challenge['challenger_id']))
		{
			$challengeScore = intval($challenge['challenger_score']);
			$challengeSql='update guild_challenges set challenger_score='.($challengeScore+1).' where id='.intval($challenge['id']).' and challenger_score='.$challengeScore;
		}else{
			$challengeScore = intval($challenge['defenser_score']);
			$challengeSql='update guild_challenges set defenser_score='.($challengeScore+1).' where id='.intval($challenge['id']).' and defenser_score='.$challengeScore;
		}
		$challengeUpdated = $this->m_db->query($challengeSql);
		//wr($challengeSql);
		if(!$challengeUpdated || mysql_affected_rows($this->m_db->getConn()) != 1){
			$this->m_db->query('ROLLBACK');
			return '分配奖励时发生错误，更新数据失败！';
		}
		if(!$this->m_db->query('COMMIT')){
			$this->m_db->query('ROLLBACK');
			return '分配奖励时发生错误，更新数据失败！';
		}
		return '胜利方获得荣誉：'.$honorGet.'。';
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
}
?>
