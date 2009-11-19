<?php

/**
 * This variable contains all configuration parameters
 * used by the Config::def, Config::set and Config::get functions.
 * It is also useful to have this variable defined here explicitly
 * because the debugger such as XDebug will show it's content.
 */
$CONFIG = array();

/**
 * Encapsulates the work with configuration items inside the application.
 * @author Viliam Simko
 */
abstract class Config
{
	/**
	 * Sets the default value of a configuration item.
	 * Does not replace already existing value.
	 * Use this function if you want to predefine values which may be redefined later.
	 *
	 * @param string $name
	 * @param mixed $value
	 */
	static public function def($name, $value)
	{
		global $CONFIG;
		if(! isset($CONFIG[$name]))
		{
			$CONFIG[$name] = $value;
		}
	}
	
	/**
	 * Replaces configuration item with a new value.
	 * This is the way how to override default values.
	 *
	 * @param string $name
	 * @param mixed $value
	 */
	static public function set($name, $value)
	{
		global $CONFIG;
		$CONFIG[$name] = $value;
	}
		
	/**
	 * Returns the latest value of a configuration item set
	 * by the Config::def() or Config::set() functions.
	 * 
	 * @param string $name
	 * @return mixed
	 */
	static public function get($name)
	{
		global $CONFIG;
		if(!isset($CONFIG[$name]))
		{
			throw new Exception('Configuration item not declared. Try to call Config::def() or Config::set() first.');
		}
		
		return $CONFIG[$name];
	}
	
	/**
	 * Automagically sets the configuration item according to the value stored in
	 * the session while the HTTP request overwrites the session.
	 * @param string $name
	 */
	static public function setReqestOverridesSession($name)
	{
		$rname = str_replace('.','_',$name);
		if(isset($_REQUEST[$rname]))
		{
			$_SESSION[$rname] = $_REQUEST[$rname];
		}
			
		if(isset($_SESSION[$rname]))
		{
			Config::set($name, $_SESSION[$rname]);
		}
	}
}
?>