<?php
function get_para_verify_map($para)
{
	if( !isset($para) )
	{
		return false;
	}
	else
	{
		if( preg_match("/[^".chr(0xa1)."-".chr(0xff)."]/",$para) )
		{
			return false;
		}
		else
		{
			return true;
		} 
	}
}
function get_para_verify_prize($para)
{
	if( !isset($para) )
	{
		return false;
	}
	else
	{
		if( preg_match("/[^0-9]|(unuse_title)/",$para) )
		{
			return false;
		}
		else
		{
			return true;
		} 
	}
}
function get_para_verify_title($para)
{
	if( !isset($para) )
	{
		return false;
	}
	else
	{
		if( preg_match("/[^a-zA-Z_]/",$para) )
		{
			return false;
		}
		else
		{
			return true;
		} 
	}
}
?>