<?php

/**
 * This singleton provides API to the blacklist files
 * that contain lists of deleted URIs.
 * 
 * @author Viliam Simko
 */
class GAMA_Blacklist_Hash
{
	/**
	 * This is where the hash structure will be stored in memory.
	 * @var array
	 */
	private $hash;
	
	/**
	 * Singleton instance.
	 * @var GAMA_Store
	 */
	static private $instance = null;
	
	/**
	 * Implementation of singleton design pattern in PHP5.
	 *
	 * @return GAMA_Blacklist_Hash
	 */
	static public function singleton()
	{
		if(!self::$instance)
		{
			$classname = __CLASS__;
			self::$instance = new $classname();
		}

		return self::$instance;
	}
	
	/**
	 * Forbidden in singleton design pattern.
	 */
	private function __clone(){}
	
	/**
	 * Constructor loads hashes to memory.
	 */
	protected function __construct()
	{
		if($this->isHashOk())
		{
			$this->loadHashesToMemory();
		} else
		{
			$this->buildNewHashStructure();
			$this->saveHashesFromMemory();
		}
	}
	
	/**
	 * Hash is OK if all the blacklist files are older than the hash file.
	 * Here, the files' modification time is used.
	 * @return boolean
	 */
	public function isHashOk()
	{
		if( ! file_exists($this->getBlacklistHashLocation()))
		{
			return false;
		}
		
		$maxmtime = 0;
		foreach(glob($this->getBlacklistFilePattern()) as $filename)
		{
			$maxmtime = max($maxmtime, filemtime($filename));
		}
		
		return filemtime($this->getBlacklistHashLocation()) >= $maxmtime;
	}
	
	/**
	 * @return string
	 */
	private function getBlacklistDir()
	{
		return Config::get('gama.blacklist.dir');
	}
	
	/**
	 * @return string
	 */
	private function getBlacklistFilePattern()
	{
		return $this->getBlacklistDir().DIRECTORY_SEPARATOR.'*.txt';
	}

	/**
	 * @return string
	 */
	private function getBlacklistHashLocation()
	{
		return $this->getBlacklistDir().DIRECTORY_SEPARATOR.'blacklist.hash';
	}
	
	/**
	 * Serialize the hash structure and save it to a file.
	 */
	private function saveHashesFromMemory()
	{
		$filename = $this->getBlacklistHashLocation();
		file_put_contents($filename, serialize($this->hash));
	}
	
	/**
	 * Load serialized hash directly to memory.
	 */
	private function loadHashesToMemory()
	{
		$filename = $this->getBlacklistHashLocation();
		$this->hash = (array) @unserialize(file_get_contents($filename));
	}
	
	/**
	 * Loads URIs from all blacklists and create a new hash structure.
	 */
	private function buildNewHashStructure()
	{
		$this->hash = array();
		
		// loading uris from all blacklist files
		foreach(glob($this->getBlacklistFilePattern()) as $filename)
		{
			echo "Loading URIs from file: $filename\n";
			
			// appending uris as array values
			$this->hash = array_merge(
				$this->hash,
				file($filename,  FILE_IGNORE_NEW_LINES |  FILE_SKIP_EMPTY_LINES)
			);
		}
		
		// values become keys
		$this->hash = array_flip($this->hash);
	}
	
	/**
	 * Throws an exception if the URI is blacklisted.
	 * @param string $uri
	 */
	public function scanBlacklists($uri)
	{
		if(array_key_exists($uri, $this->hash))
		{
			throw new Exception("Blacklisted URI '$uri'");
		}
	}
}