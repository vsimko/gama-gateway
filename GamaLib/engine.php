<?php

// =============================================================================
// Error reporting and assertions
// =============================================================================

// Use of trans sid may risk your users security.
ini_set('session.use_trans_sid', '0');

// therefore we decided to only allow session stored in cookies
// this is even easier for the engine because there is no url rewriting going on.
ini_set('session.use_cookies', '1');

// we want to be notified of all possible errors.
ini_set('display_errors', '1');
error_reporting(E_ALL); // this is the strict-mode
//error_reporting(E_ALL | E_STRICT); // this enables nasty hacks inside the code

// setting-up the assert() function to work properly
assert_options(ASSERT_BAIL, 1);
assert_options(ASSERT_CALLBACK, 'assert_handler');

/**
 * This function tells the PHP to print the debug backtrace and finis
 * in provided an assert() function-call would fail.
 *
 * @param string $file
 * @param integer $line
 * @param string $message
 */
function assert_handler($file, $line, $message)
{
	debug_print_backtrace();
	die();
}

// =============================================================================
// Autoloading of objects and include path
// =============================================================================

/**
 * Autoloading Objects in PHP5 taking into account
 * current INCLUDE_PATH.
 *
 * @param string $class_name
 */
function __autoload($classname)
{
//	require_once($classname.'.php'); // this didn't work well with the class_exists("classname", true)
	@include_once($classname.'.php');
}

/**
 * @param string $path
 */
function add_include_path($path)
{
	assert(!empty($path)); // path cannot be empty
	
	// we are using the strpos() function for fast searching
	$path = PATH_SEPARATOR.$path;
	$curPath = ini_get('include_path');
	
	// we must prepend the PATH_SEPARATOR to cover the case where there
	// is only one item in the include_path
	if( ! strpos($path, PATH_SEPARATOR.$curPath) )
	{
		ini_set('include_path', $curPath.$path );
	}
}

// Always consider the directory where this engine-script resides
// as the PHP's include path. This should simplify the usage of a library
// which the engine is a part of.
add_include_path(dirname(__FILE__).DIRECTORY_SEPARATOR);
add_include_path(dirname(__FILE__).DIRECTORY_SEPARATOR.'interfaces');
add_include_path(dirname(__FILE__).DIRECTORY_SEPARATOR.'classes');

// =============================================================================
// Debugging
// =============================================================================

// show debug messages or not
Config::def('debug.enabled', false);

// messages can be prefixed thus output from different applications
// writing to the same file descriptor be identified
Config::def('debug.prefix', 'DEBUG: ');

// where to write debug messages (error log of http server by default)
Config::def('debug.output', 'php://stderr');

// append messages or replace the content of the file
Config::def('debug.append_mode', true);

// use flush after each write operation (slower but interactive)
Config::def('debug.flush', true);

/**
 * Print the debug message.
 *
 * @param mixed $message
 * @param int[optional] $indent
 */
function debug($message, $indent = 0)
{
	if( Config::get('debug.enabled') )
	{
		static $fh, $outfname;
		
		if(empty($fh) || $outfname != Config::get('debug.output'))
		{
			$outfname = Config::get('debug.output');
			$fh = fopen( $outfname, Config::get('debug.append_mode') ? 'a' : 'w');
		}
		
		if(!is_scalar($message))
		{
			$message = print_r($message, true);
		}
		
		$prefix = str_repeat(' ', $indent).Config::get('debug.prefix');
		
		fwrite($fh, $prefix . str_replace("\n", "$prefix\n", $message) . "\n");
			
		if(Config::get('debug.flush'))
		{
			fflush($fh);
		}
	}
}

/**
 * Measures execution time between two points in a script.
 *
 * @param string $measureId
 */
function debug_time_measure($measureId)
{
	static $timeStart = array();
	
	$timestamp = time() + microtime();
	
	if(isset($timeStart[$measureId]))
	{
		$taken = round($timestamp - $timeStart[$measureId], 4);
		debug( round($timestamp, 4)." TIME TAKEN [$measureId] : $taken second(s)");
		unset($timeStart[$measureId]);
	} else
	{
		$timeStart[$measureId] = $timestamp;
		$taken = null;
	}
	
	return $taken;
}

// =============================================================================
// Dispatcher implementation
// =============================================================================

require_once 'Dispatcher.php';

// =============================================================================
// Engine initialisation (synchronisation)
// =============================================================================

/**
 * Acquires the global application lock.
 * Multiple lock-levels are used when called multiple times.
 * @param integer $mode
 */
function app_lock($mode = LOCK_EX)
{
	$GLOBALS['locklevel']++;
	if(! flock($GLOBALS['lockfh'], $mode))
	{
		throw new Exception('Could not lock the application');
	}
}

/**
 * Releases one level of the global application lock.
 * If the level reaches 0 a real unlock is performed.
 * The lock will also be released automatically when the script terminates.
 * 
 * NOTE: This function is registered as PHP-shutdown function.
 */
function app_unlock($final = false)
{
	global $locklevel;
	if($locklevel <= 0)
	{
		throw new Exception('Locking protocol violation');
	}

	if(--$locklevel == 0)
	{
		if(! flock($GLOBALS['lockfh'], LOCK_UN))
		{
			throw new Exception('Could not unlock the application');
		}
		//debug('real unlock performed');
	}
	
	if($final && $locklevel != 0)
	{
		debug('!!! WARNING !!! You forgot to unlock something');
	}
}

// We use this script as a default lockfile.
// You might need to change this if you store your scripts on NFS.
Config::def('repo.lockfile', __FILE__);

// This file is also used as a synchronisation point of parallel processes.
// We use a single global-lock in our implementation which is locked in a
// shared manner on each run.
$lockfh = fopen( Config::get('repo.lockfile'), 'r');
$locklevel = 0;

register_shutdown_function('app_unlock', true);

// this will measure the time spent in the whole request
register_shutdown_function('debug_time_measure', 'whole request');
debug_time_measure('whole request');

debug('Trying to acquire global read-lock');
app_lock(LOCK_SH);
debug('Global read-lock acquired');

?>