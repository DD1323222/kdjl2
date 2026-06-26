<?php
require_once('../config/config.game.php');
secStart($_pm['mem']);
$user = $_pm['mysql'] -> getOneRecord("SELECT tgt,tgttime,sj,tglasttime FROM player_ext WHERE uid = {$_SESSION['id']}");
if(!is_array($user)){
	die('a');//操作有误！
}
$op = $_GET['op'];
$day = time();

if($op == 'cfight'){
	if($user['tglasttime'] > 0){
		$tgyes = date('Ymd',$user['tglasttime']);
		$tgyes1 = date('Ymd',time()-24*3600);
	}
	if($tgyes1 >= $tgyes){
		$_pm['mysql'] -> query("DELETE FROM tgt WHERE uid = {$_SESSION['id']}");
		
		$_pm['mysql'] -> query("UPDATE player_ext SET tgt = 0,tgttime = 0,tglasttime=0 WHERE uid = {$_SESSION['id']}");
		$tch = tgtgw();
		if($tch == 'a'){
			die('q');
		}
		$_pm['mysql'] -> query("UPDATE player SET inmap = 126 WHERE id = {$_SESSION['id']}");
		die('b');
	}else if($user['tgttime'] == 0){
		$tch = tgtgw();
		if($tch == 'a'){
			$_pm['mysql'] -> query("UPDATE player_ext SET tgt=0,tgttime=".time()." WHERE id = {$_SESSION['id']}");
			$a = file_get_contents('http://'.$_SERVER['HTTP_HOST'].'function/ttGate.php?op=cfight');
			echo $a;
			exit;
		}
		$_pm['mysql'] -> query("UPDATE player SET inmap = 126 WHERE id = {$_SESSION['id']}");
		die('b');
	}else if($user['tgttime'] > 0){
		$yes = date('Ymd',$user['tgttime']);
		$yes1 = date('Ymd',time()-24*3600);
		if($yes1 >= $yes){//刷新
			$_pm['mysql'] -> query("DELETE FROM tgt WHERE uid = {$_SESSION['id']}");
			$tch = tgtgw();
			if($tch == 'a'){
				die('q');
			}
			$_pm['mysql'] -> query("UPDATE player_ext SET tgt = 0,tgttime = 0 WHERE uid = {$_SESSION['id']}");
			$_pm['mysql'] -> query("UPDATE player SET inmap = 126 WHERE id = {$_SESSION['id']}");
			die('b');
			//直接进
		}else{
			$sj = ($user['tgt'] + 1) * 20;
			$flagcheck = unserialize($_pm['mem']->get('tgtimeflag'.$_SESSION['id']));//echo $flagcheck;exit;
			if($flagcheck > 0){
				$tgcheckyes = date('Ymd',$flagcheck);
				$tgcheckyes1 = date('Ymd',time());//echo $tgcheckyes.'aaaaaaa'.$tgcheckyes1.'========';
				if($tgcheckyes1 == $tgcheckyes){//echo __LINE__."<br>";
					//$sj = 200;
					$sj = 1000;
				}
			}
			//exit;
			
			if($_GET['action'] != 'do'){
				echo $sj;
				die('');
				//提示要水晶
			}else{
				
				$_pm['mysql'] -> query("UPDATE player_ext SET sj = sj - $sj WHERE uid = {$_SESSION['id']} AND sj >= $sj");
				$result = mysql_affected_rows($_pm['mysql'] -> getConn());
				
				if($result != 1){
					die("c");//没有足够的水晶
				}else if($result == 1){
					$_pm['mysql'] -> query("DELETE FROM tgt WHERE uid = {$_SESSION['id']}");
					$tch = tgtgw();
					if($tch == 'a'){
						die('q');
					}
					$_pm['mysql'] -> query("UPDATE player SET inmap = 126 WHERE id = {$_SESSION['id']}");
					//回血
					$_pm['mysql'] -> query("UPDATE userbb SET hp = srchp,mp=srcmp WHERE uid = {$_SESSION['id']}");
					$_pm['mysql'] -> query("UPDATE player_ext SET tgttime = 0 WHERE uid = {$_SESSION['id']}");
					$flagcheck = unserialize($_pm['mem']->get('tgtimeflag'.$_SESSION['id']));//echo $flagcheck;exit;
					if($flagcheck > 0){
						$_pm['mem']->del('tgtimeflag'.$_SESSION['id']);
					}
					die('d');//水晶扣除
				}
			}
		}
	}
}else if($op == 'tgfight'){
	if($user['tglasttime'] > 0){
		$tgyes = date('Ymd',$user['tglasttime']);
		$tgyes1 = date('Ymd',time()-24*3600);
	}

	if($tgyes1 >= $tgyes){
		$_pm['mysql'] -> query("DELETE FROM tgt WHERE uid = {$_SESSION['id']}");
		
		$_pm['mysql'] -> query("UPDATE player_ext SET tgt = 0,tgttime = 0,tglasttime=0 WHERE uid = {$_SESSION['id']}");
		$tch = tgtgw();
		if($tch == 'a'){
			die('q');
		}
		$_pm['mysql'] -> query("UPDATE player SET inmap = 126 WHERE id = {$_SESSION['id']}");
		die('b');
	}else if($user['tgttime'] == 0){
		
		$tch = tgtgw();
		if($tch == 'a'){
			if($tch == 'a'){
				$_pm['mysql'] -> query("UPDATE player_ext SET tgt=0,tgttime=".time()." WHERE id = {$_SESSION['id']}");
				$a = file_get_contents('http://'.$_SERVER['HTTP_HOST'].'function/ttGate.php?op=tgfight');
				echo $a;
				exit;
			}
		}
		$_pm['mysql'] -> query("UPDATE player SET inmap = 126 WHERE id = {$_SESSION['id']}");
		die('b');
	}else if($user['tgttime'] > 0){
		$yes = date('Ymd',$user['tgttime']);
		$yes1 = date('Ymd',time()-24*3600);
		if($yes1 >= $yes){//刷新
			$_pm['mysql'] -> query("DELETE FROM tgt WHERE uid = {$_SESSION['id']}");
			
			$_pm['mysql'] -> query("UPDATE player_ext SET tgt = 0,tgttime = 0 WHERE uid = {$_SESSION['id']}");
			$tch = tgtgw();
			if($tch == 'a'){
				die('q');
			}
			$_pm['mysql'] -> query("UPDATE player SET inmap = 126 WHERE id = {$_SESSION['id']}");
			die('b');
			//直接进
		}else{
			//$sj = 200;
			$sj = 1000;
			if($_GET['action'] != 'do'){
				//die('200');
				die('1000');
				//提示要水晶
			}else{
				$_pm['mysql'] -> query("UPDATE player_ext SET sj = sj - $sj WHERE uid = {$_SESSION['id']} AND sj >= $sj");
				$result = mysql_affected_rows($_pm['mysql'] -> getConn());
				if($result != 1){
					die("c");//没有足够的水晶
				}else if($result == 1){
					$_pm['mysql'] -> query("DELETE FROM tgt WHERE uid = {$_SESSION['id']}");
					$_pm['mysql'] -> query("UPDATE player_ext SET tgt = 0,tgttime = 0 WHERE uid = {$_SESSION['id']}");
					$tch = tgtgw();
					if($tch == 'a'){
						die('q');
					}
					$_pm['mysql'] -> query("UPDATE player SET inmap = 126 WHERE id = {$_SESSION['id']}");
					//回血
					$_pm['mysql'] -> query("UPDATE userbb SET hp = srchp,mp=srcmp WHERE uid = {$_SESSION['id']}");
					die('d');//水晶扣除
				}
			}
		}
	}
}
?>