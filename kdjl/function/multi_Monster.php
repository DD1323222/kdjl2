<?php
class multiMonster{
	private $id="";
	private $table_name='multi_monster';
	private $conn;
	private $mem;
	private $expire_time = 900;
	private $uid;
	private $mapid;
	public $listStr = "";
	public $isFirstMultiMonster = false;
	public $monsterTotalNum = 0;

	//,$_mapid		$this->mapid = $_mapid;
	public function multiMonster($_uid,&$_conn,&$_mem){
		$this->mem = $_mem;
		$this->uid = intval($_uid);
		$this->conn = $_conn;
		if(!$this->conn||!is_resource($this->conn)){
			throw new Exception(__FILE__."->".__LINE__.": Need a mysql connection!");
		}
		$this->cleanup();
	}
	private function cleanup(){
		$sql = 'delete from '.$this->table_name.' where befall_time > 0 and (befall_time + '.$this->expire_time.') < UNIX_TIMESTAMP()';
		if(!$this->execute($sql)){
			$this->initTable();
		}
	}
	public function freshup(){
		$memKey= "last_update_user_fight_time_".$this->uid;
		$this->mem->del($memKey);
		$sql = 'delete from '.$this->table_name.' where uid='.$this->uid;
		$this->execute($sql);
	}
	public function finish(){
		$sql = 'select props_got,befall_time from '.$this->table_name.' where uid='.$this->uid;
		$propsRs= $this->getRow($sql);
		$befallTime = (is_array($propsRs) && isset($propsRs['befall_time'])) ? intval($propsRs['befall_time']) : 0;
		if(!is_array($propsRs) || $befallTime + $this->expire_time < time()) return array("","");//某种错误，因为玩家是15分钟前遇到的怪，这几乎是不可能的

		$propsGot = isset($propsRs['props_got']) ? $propsRs['props_got'] : '';
		$propsStr = explode('#|#',$propsGot);
		$rtn = array("","",0,0);
		$con = "";
		$con1 = "";
		foreach($propsStr as $v){
			$tmp = explode("#*#",$v);
			if(count($tmp)==4){
				if($tmp[0]!='-'){
					$rtn[0] .= $con.$tmp[0];
					$rtn[1] .= $con1.$tmp[1];
					$con = "，";
					$con1 = ",";
				}
				$rtn[2] += $tmp[2];
				$rtn[3] += $tmp[3];
			}
		}
		if(isset($_SESSION['fight'.$this->uid]) && is_array($_SESSION['fight'.$this->uid]))
		{
			unset($_SESSION['fight'.$this->uid]['lastMMid']);
		}
/*
if($rtn[0]=="")
			$rtn[0] = "&nbsp;";
		else
			$rtn[0] .="&nbsp;";
*/
		$this->freshup();
		return $rtn;
	}
	public function saveProps($props){
		$props = mysql_real_escape_string($props,$this->conn);
		$this->query("update ".$this->table_name." set props_got=concat(props_got,'#|#','".$props."') where uid=".$this->uid,true);
	}

	private function updateListStr($list){
		$str = "-";
		if(!empty($list)){
			$lists = explode("|",$list);
			$str = "<table wdith='180' border=0 cellspadding=0 cellspacing=0 id=showmmonsterlistdetails>";
			$c = " style='color:#ff0000' ";
			foreach($lists as $v){
				$tmp = explode(",",$v);
				if(count($tmp) < 3) continue;
				$name = htmlspecialchars($tmp[2], ENT_QUOTES, 'UTF-8');
				$level = intval($tmp[1]);
				$str .= "<tr><td width=180 $c>".$name."&nbsp;&nbsp;&nbsp;&nbsp;Lvl:".$level."</td></tr>";
				$c = "";
			}
			$str .= "</table>";
		}
		$this->listStr = $str;
	}
	/**/
	public function checkMyMonster(){
		$sql = 'select monsters from '.$this->table_name.' where uid='.$this->uid;
		$monsterStr = $this->getOne($sql);

		if($monsterStr===false) return false;

		if(empty($monsterStr)||$monsterStr=='|'){
			return "OK";
		}
		$this->monsterTotalNum = count(explode('|',$monsterStr));
		return true;
	}

	/*public function getMyMonsterList(){
		$sql = 'select monsters from '.$this->table_name.' where uid='.$this->uid;
		$monsterStr = $this->getOne($sql);
		if($monsterStr===false||$monsters=="|") return "";
		return $monsterStr;
	}
	*/


	public function removeKilledMonster($id){
		$sql = 'select monsters from '.$this->table_name.' where uid='.$this->uid;
		$monsterStr = $this->getOne($sql);
		if($monsterStr===false||$monsterStr===''||$monsterStr=='|') return;
		$monsters = explode("|",$monsterStr);
		if(count($monsters)<=0||$monsters[0]==='') return;
		$tmp  = explode(",",$monsters[0]);
		if(count($monsters)>0&&count($tmp)>0){
			if($id == $tmp[0]){
				array_shift($monsters);
				$this->query("update ".$this->table_name." set monsters='".implode("|",$monsters)."' where uid=".$this->uid,true);
			}
		}
	}
	private $allMonsterStr = NULL;
	private static $gpcs = NULL;
	public $monsterCount=0;
	private function getGPCs(){
		if(self::$gpcs===NULL||empty(self::$gpcs)){
			self::$gpcs = kdjlSafeMemValue($this->mem->get(MEM_GPC_KEY), array());
			if(!is_array(self::$gpcs)) self::$gpcs = array();
		}
		return self::$gpcs;
	}

	public function getNextMonster(){
		if($this->allMonsterStr===NULL){
			$sql = 'select monsters from '.$this->table_name.' where uid='.$this->uid;
			$this->allMonsterStr = $this->getOne($sql);
		}
		if($this->allMonsterStr === false ) return false;
		if($this->allMonsterStr==""||$this->allMonsterStr=="|"){
			return false;
		}
		$this->monsterCount++;
		$monsters = explode("|",$this->allMonsterStr);
		if(count($monsters)<=0||$monsters[0]==='') return false;
		$tmp = explode(",",$monsters[0]);
		if(count($tmp)<=0||$tmp[0]==='') return false;

		$gpcs = $this->getGPCs();
		$gw =array();

		foreach($gpcs as $gpc){
			if($gpc['id']==$tmp[0]){
				$gw = $gpc;
				break;
			}
		}

		array_shift($monsters);
		$this->allMonsterStr = implode("|",$monsters);
		//exit($this->allMonsterStr);
		return $gw;
	}
	private function getMonsterAndMap(){
		$monsterAndMap = $this->mem->getHandle()->get('GPCs_in_MAP');
		if(!$monsterAndMap){
			$gpcs = $this->getGPCs();
			$map = kdjlSafeMemValue($this->mem->get(MEM_MAP_KEY), array());
			if(empty($gpcs)||empty($map)){
				return false;
			}
			$monsters = false;
			$chance = '';
			$mapgpcs=array();
			$mapLvls=array();
			foreach($map as $v){
				if(!is_array($v) || !isset($v['id'], $v['level'])) continue;
				$mapId = intval($v['id']);
				if($mapId < 1) continue;
				$mapLvls[$mapId] = explode(",",$v['level']);
			}
			foreach($gpcs as $gpc){
				if(!is_array($gpc) || !isset($gpc['name'], $gpc['level'])) continue;
				$gpcLevel = intval($gpc['level']);
				foreach($mapLvls as $k=>$v){
					if(count($v) >= 2 && intval($v[0])<=$gpcLevel&&intval($v[1])>=$gpcLevel&&(!isset($mapgpcs[$k])||!is_array($mapgpcs[$k])||!in_array($gpc['name'],$mapgpcs[$k]))){
						$mapgpcs[$k][]= $gpc['name'];
					}
				}
			}
			foreach($mapgpcs as $k=>$v){
				$mapgpcs[$k] = implode(',',$v);
			}
			$this->mem->getHandle()->set('GPCs_in_MAP',$mapgpcs,0,3600);
			$gpcs = NULL;
			return $mapgpcs;
		}
		if(!is_array($monsterAndMap)) $monsterAndMap = kdjlSafeMemValue($monsterAndMap, array());
		if(!is_array($monsterAndMap)) $monsterAndMap = array();
		return $monsterAndMap;
	}
	public function getMultiMonster($mapId){
		$mapId = intval($mapId);
		if($mapId < 1) return false;
		//return false;
		/*
		$curMonster = $this->getCurMonster();
		if($curMonster=='OK'){
			return 'OK';
		}else if($curMonster!==false&&is_array($curMonster)&&!empty($curMonster)){
			return $curMonster;
		}
		*/
		$map = kdjlSafeMemValue($this->mem->get(MEM_MAP_KEY), array());
		if(!is_array($map)) $map = array();
		$monsters = false;
		$chance = '';
		foreach($map as $v){
			if(!is_array($v) || !isset($v['id'])) continue;
			if(intval($v['id'])==$mapId){
				$chance = isset($v['multi_monsters']) ? $v['multi_monsters'] : '';
				break;
			}
		}
		$monsterAndMap=$this->getMonsterAndMap();
		if(!is_array($monsterAndMap) || !isset($monsterAndMap[$mapId]) || $monsterAndMap[$mapId] === '') return false;
		//$monsters = explode(",",$monsterAndMap[$mapId]);
		$chances = explode("|",$chance);

		$maxMonsterNum = 1+count($chances);
		$rand = rand(1,100);
		$monstersNum = 0;
		for($i=count($chances)-1;$i>-1;$i--)
		{
			if($rand<=$chances[$i]){
				$monstersNum = $maxMonsterNum;
				break;
			}
			$maxMonsterNum--;
		}
		//$monstersNum=3;

		//echo '$monsters='.count($monsters).'<hr>';
		//echo '$monstersNum='.$monstersNum.'<hr>';

		if($monstersNum<=0) return false;

		/*
		$monsterStr = "";
		$connector = "";
		for($i=0;$i<$monstersNum;$i++){
			$monsterStr .= $connector.$monsters[rand(0,count($monsters)-1)];
			$connector = ",";
		}
		*/

		$map = kdjlSafeMemValue($this->mem->get(MEM_MAP_KEY), array());
		if(!is_array($map)) $map = array();

		foreach($map as $v){
			if(!is_array($v) || !isset($v['id'])) continue;
			if(intval($v['id'])==$mapId){
				$mapLvls = isset($v['level']) ? explode(",",$v['level']) : array();
				break;
			}
		}
		if(!isset($mapLvls)||count($mapLvls)<2)
		{
			return false;
			//die('Map not found('.__LINE__.')!');
		}

		$monsterStrs = array_filter(explode(",",$monsterAndMap[$mapId]),'strlen');
		if(empty($monsterStrs)) return false;
		//echo '$monsterStrs='.$monsterStrs.'<hr>';
		$gpcs = $this->getGPCs();
		if(empty($gpcs)) return false;
		$gw =array();
		$connector = "";
		$monsterStr = "";
		$selectedNames = array();
		$ct=0;
		shuffle($gpcs);
		$minLevel = intval($mapLvls[0]);
		$maxLevel = intval($mapLvls[1]);

/*

		echo '<b>'.__FILE__.'-->'.__LINE__.'</b><br/><pre>=';
		var_dump($monsterStrs,count($gpcs)	);
		echo '</pre>';

*/
		foreach($gpcs as $gpc){
			if(!isset($gpc['id'])||!isset($gpc['name'])||!isset($gpc['level'])||!isset($gpc['boss'])) continue;
			$gpcLevel = intval($gpc['level']);
			$gpcBoss = intval($gpc['boss']);
			if(!in_array($gpc['name'],$selectedNames)&&in_array($gpc['name'],$monsterStrs)&&$gpcBoss!=3&&$gpcLevel>$minLevel&&$gpcLevel<$maxLevel&&$gpcBoss!=4){
				$monsterStr .= $connector.intval($gpc['id']).",".$gpcLevel.",".$gpc['name'];//.",".$gpc['mp'].",".$gpc['boss'];
				$selectedNames[] = $gpc['name'];
				if(empty($gw)){
					$gw = $gpc;
				}
				$connector = "|";
				$ct++;
				if($ct==$monstersNum) break;
			}
		}
		$this->monsterTotalNum = $monstersNum;
		//if($ct<$monstersNum) echo '<h1>Monster is not enough!</h1>';
		//echo '$monsterStr='.$monsterStr.'<hr>';
		if(empty($gw)){
			return false;
		}else{
			$this->freshup();
			$this->updateListStr($monsterStr);
			$this->query("insert into ".$this->table_name."(uid,befall_time,monsters) values('".$this->uid."','".time()."','".$monsterStr."')",true);
			$this->isFirstMultiMonster = true;
			return $gw;
		}

	}
	//ALTER TABLE `map` ADD `multi_monsters` VARCHAR( 100 ) NOT NULL ;
	private function initTable(){
		$sql = "
			CREATE TABLE if not exists `".$this->table_name."` (
			  `uid` int(11) unsigned NOT NULL default '0',
			  `befall_time` int(10) NOT NULL default '0',
			  `monsters` varchar(255) NOT NULL default '',
			  `props_got` varchar(255) NOT NULL default '',
			  PRIMARY KEY  (`uid`),
			  KEY `time` (`befall_time`)
			) ENGINE=MEMORY;
			";
		return $this->execute($sql,true);
	}
	private function execute($sql,$die=false)
	{
		if($die)
		{
			if(!mysql_query($sql,$this->conn)) die("100");
		}
		else
		{
			mysql_query($sql,$this->conn);
			if(mysql_error($this->conn)){
				return false;
			}else{
				return true;
			}
		}
	}
	private function getOne($sql,$die=false){
		$rs = $this->query($sql,$die);
		if($rs && ($one = mysql_fetch_row($rs)) ){
			return $one[0];
		}else{
			return false;
		}
	}
	private function query($sql,$die=false){
		if($die)
		{
			$rs = mysql_query($sql,$this->conn);
			if(!$rs) die("100");
		}
		else
		{
			$rs = mysql_query($sql,$this->conn);
		}
		return $rs;
	}
	function getRow($sql,$die=false){
		$rs = $this->query($sql,$die);
		if($rs && ($one = mysql_fetch_assoc($rs)) ){
			return $one;
		}else{
			return false;
		}
	}
}
//$_pm['mysql']->safeConn();
//var_dump($_pm['mysql']->safeConn());
$multiMonsterUid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($multiMonsterUid < 1) die('');
$multiMonster = new multiMonster($multiMonsterUid,$_pm['mysql']->getConn(),$_pm['mem']);


?>
