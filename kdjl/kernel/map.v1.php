<?php
/**
@Usage: Map class
@Copyright:www.webgame.com.cn
@Version:1.0
*/
class map{
	// db object handle.
	public $db;

	function __construct(){}
	
	// get map info by id.
	// Return array.
	public function loadMapById($id){}
	
	// Update Map info by id.
	public function updateMapById($id){}
	
	// add Map info.
	public function addMapinfo($info){}

	// del map info.
	public function delMapById($id){}


	function __destruct(){}
	
?>