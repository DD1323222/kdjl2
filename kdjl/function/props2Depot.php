<?php
/**
 * put the props in bag to depot
 *
 * @date:2009-03-24
 * @author:Zheng.Ping
 **/


require_once('../config/config.game.php');

secStart($_pm['mem']);
del_bag_expire();
define('MOVE_TIME_SPACE', 1); // the operation time space

/**
 * class to descripe the user's props
 */
class UserProps {
    /**
     * the props in the user's bag
     *
     * @array $bagProps;
     */
    private $bagProps = array();

    /**
     * the props in the user's depot
     *
     * @array $depotProps
     */
    private $depotProps = array();

    /**
     * constructor
     *
     * @param array $userPropsList
     */
    public function __construct($userPropsList = array()) {
        if (is_array($userPropsList) && !empty($userPropsList)) {
            foreach($userPropsList as $props) {
                if (isset($props['bsum']) && $props['bsum'] > 0) {
                    $this->depotProps[$props['id']] = $props;
                } 
                // the same record may belong to depot, 
                // and belong to bag at the same time
                if (isset($props['sums']) && ($props['sums'] > 0) 
                    && isset($props['zbing']) && ($props['zbing'] != 1)) {
                    $this->bagProps[$props['id']] = $props;
                }
            }
        }
    }

    /**
     * get the size of props in depot
     *
     * @return integer
     */
    public function getDepotPropsLength()
    {
        return count($this->depotProps);
    }

    /**
     * get the size of props in bag
     *
     * @return integer
     */
    public function getBagPropsLength()
    {
        return count($this->bagProps);
    }

    /**
     * check if the giving props is in the user's bag
     *
     * @param integer $idProps
     * @return boolean
     */
    public function isPropsInBag($idProps)
    {
        //var_dump(array_keys($this->bagProps));
        return in_array(intval($idProps), array_keys($this->bagProps));
    }

    /**
     * get the assigned props in bag
     *
     * @param integer $idProps
     * @return array or false(boolean)
     */
    public function getPropsInBag($idProps)
    {
        if (isset($this->bagProps[$idProps])) {
            return $this->bagProps[$idProps];
        }

        return false;
    }
}

/**
 * update the table `userbag`,
 * it means to move the props in bag to depot.
 *
 * @param array $props
 * @return boolean
 */
function movePropsInBag2Depot($props)
{
    $dbHandler = $GLOBALS['_pm']['mysql'];
    $numPropsBag = $props['sums'];
    $numPropsDepot = $props['bsum'];

    $sql = sprintf("UPDATE userbag SET sums=%d, bsum=%d WHERE id=%d",
        0, $numPropsBag + $numPropsDepot, $props['id']);

    return $dbHandler->query($sql);
}

/**
 * drop the record the table `userbag`.
 *
 * @param array $props
 * @return boolean
 */
function dropPropsInBag($props)
{
    $dbHandler = $GLOBALS['_pm']['mysql'];
    $numPropsBag = $props['sums'];
    $numPropsDepot = $props['bsum'];
	//$pcheck = $dbHandler -> getOneRecord("SELECT cantrade FROM userbag WHERE uid = {$_SESSION['id']} AND id = {$props['id']}");
	/*if($pcheck['cantrade'] == 3){
		return 'exit';
	}else{*/
		$sql = sprintf("UPDATE userbag SET sums=%d WHERE id=%d", 0, $props['id']);

    	return $dbHandler->query($sql);
	//}

    // use update to drop the propos in bag,
    // if need to delete the record, there need to do two operations,
    // 1. check if the record also be in depot, 
    //    if it is a part of props in depot, can not delete the record,
    //    only do update operation.
    // 2. if the record only is a props in bag, it can be deleted.
    
}

/**
 * get the time spacing between the nearly operation
 * if the operation is the first operation, it will return -1
 * 
 * @return integer (in seconds)
 */
function getOperationTimeSpace()
{
    $timeSpace = -1;

    $now = time();
    if (isset($_SESSION['propsMoveTime'])) {
        $timeSpace = $now - $_SESSION['propsMoveTime'];
    }

    return $timeSpace;
}


// =============== the executable code =================
$id  = intval($_REQUEST['id']); // userbag id
$act = $_REQUEST['act'];
//$id = 2663;
require_once('../sec/dblock_fun.php');
$a = getLock($_SESSION['id']);

if ($id < 1) {
	realseLock();
    die('1');
}

if (!in_array($act, array('move', 'drop'))) {
	realseLock();
    die('1');
}

$user     = $_pm['user']->getUserById($_SESSION['id']);
$userBags = $_pm['user']->getUserBagById($_SESSION['id']);

/*$user     = $_pm['user']->getUserById(26);
$userBags = $_pm['user']->getUserBagById(26);*/
//var_dump($user, $userBags);
if (is_array($user) && is_array($userBags)) {
    $userProps = new UserProps($userBags);
    //var_dump($userProps);
    if ($act == 'move') {
        // check whether the depot is full, if there is no space in depot,
        // break at here, and return a message
        if ($userProps->getDepotPropsLength() >= $user['maxbase']) {
			realseLock();
            die('2');
        }
    }
    // check whether the props is in the playr's bag, if it is not in,
    // break at here, and return a message
    if (!$userProps->isPropsInBag($id)) {
		realseLock();
        die('3');
    }
    // get the props in bag
    $propsInBag = $userProps->getPropsInBag($id);
    // if the two operation's time spacing is less than 5 seconds, do nothing.
    if (isset($_SESSION['propsMoveTime']) 
        && getOperationTimeSpace() < MOVE_TIME_SPACE) {
		realseLock();
        die('4');
    }

    switch($act) {
    case 'move':
        // move props from bag to depot
        if (movePropsInBag2Depot($propsInBag)) {
            $_SESSION['propsMoveTime'] = time(); // record the moving time
			realseLock();
            exit('0'); // operation success
        } else {
			realseLock();
            die('10'); // db operation failed.
        }
        break;

    case 'drop':
        // drop props from bag
		/*$re = dropPropsInBag($propsInBag);
		if($re == 'exit'){
			exit('100');
			return;
		}*/
        if (dropPropsInBag($propsInBag)) {
            $_SESSION['propsMoveTime'] = time(); // record the moving time
			realseLock();
            exit('0'); // operation success
        } else {
			realseLock();
            die('10'); // db operation failed.
        }
        break;

    default:
        break;
    }
} else {
	realseLock();
    die('11'); // can not get integreated information
}

?>
