<?php

/**
 * Controlls the intantiation and caching of the Resource class.
 * Uses the factory design pattern.
 */
class Resource_Manager
{
	// =========================================================================
	// Event handlers
	// =========================================================================
	
	// =========================================================================
	// Other stuff
	// =========================================================================
	
	private $cachedResourcesByUri = array();
	
	/**
	 * Constructor establishes connection to database.
	 */
	protected function __construct()
	{
		// singleton pattern requires protected constructor

		Config::def('resmgr.maxcacheitems', 1000);
		Config::def('resmgr.savecache', false);
		Config::def('resmgr.cachedir', '/dev/shm/cache');
	}

	/**
	 * Forbidden in singleton design pattern.
	 */
	private function __clone(){}

	/**
	 * Singleton instance.
	 * @var Resource_Manager
	 */
	static private $instance = null;
	
	/**
	 * Implementation of singleton design pattern in PHP5.
	 *
	 * @return Resource_Manager
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
	 * @param string $uri
	 * @return Resource|RDF_Property|OWL_Class
	 */
	public function getResourceByUri($uri)
	{
		// First we try to load the instance from the memory.
		// Using a reference allows us to place the instance at the specific
		// array position later without searching the array again.
		$resource = & $this->cachedResourcesByUri[$uri];
		
		if(!empty($resource))
			return $resource;
		
// disk cache not supported yet
//		// if the resource is not in the memory, we try to load it from disk cache
//		app_lock(LOCK_SH);
//		$resource = @unserialize(@file_get_contents( Config::get('resmgr.cachedir').'/'.md5($uri)));
//		app_unlock();
//		
//		// resource found in the cache
//		if(!empty($resource))
//		{
//			dispatcher($resource)->attach($this);
//			return $resource;
//		}

		// remove surplus of the cached items
		if(count($this->cachedResourcesByUri) > Config::get('resmgr.maxcacheitems'))
		{
			$first = reset($this->cachedResourcesByUri);
			$this->removeResourceFromCache($first->getUri());
		}
		
			
		// if the resource is not in the cache we have to instantiate it
		// because we used a reference to the exact array position earlier
		// this should place the instance directly into the array
		$resource = new Resource($uri);
		
		// each resource has it's own dispatcher through which it
		// notifies other components such as the Resource_Manager
		dispatcher($resource)->attach($this);

		// The new resource should load important data from the database
		// in order to infer it's type.
		$resource->reloadFromStore();
		
		// the correct class instance is ready now
		return $resource;
	}
	
	/**
	 * @param string $uri
	 * @param string $desiredType
	 * @return Resource|RDF_Property|OWL_Class
	 */
	public function getPreparedResourceByUri($uri, $desiredState)
	{
		$resource = $this->getResourceByUri($uri);
		dispatcher($resource)->onStateChanged($desiredState);
		$resource->createInStore();
		return $resource;
	}
		
	/**
	 * Removes a resource from the cache.
	 * @param string $uri
	 */
	public function removeResourceFromCache($uri)
	{
		if(empty($this->cachedResourcesByUri[$uri]))
			return;

		//debug("removing from cache $uri");
		
//		app_lock();
//		@unlink(Config::get('resmgr.cachedir').'/'.md5($uri));
//		app_unlock();
		
		unset($this->cachedResourcesByUri[$uri]);
	}
	
	/**
	 * Mainly for debugging purposes.
	 */
	public function cleanupCache()
	{
		//=================
		app_lock(LOCK_EX);
		//=================
		try
		{
			$this->cachedResourcesByUri = array();
	
			foreach(glob(Config::get('resmgr.cachedir').'/*') as $fname)
			{
				debug('Deleting resource from cache: '.$fname);
				unlink($fname);
			}
		} catch(Exception $e)
		{
			app_unlock();
			throw $e;
		}
		//=================
		app_unlock();
		//=================
	}
	
	/**
	 * Translate the internal resource ID to a list of URIs.
	 * A single ID may represent multiple URIs provided they have been made
	 * equivalent using the owl:sameAs property.
	 *  
	 * @param integer $id
	 * @return array
	 */
	public function getResourceUrisById($id)
	{
		$store = GAMA_Store::singleton();
		$uriList = $store->sqlFetchColumn('uri', 'select uri from RESOURCE where id=?', $id);
		assert('is_array($uriList)');
		return $uriList;
	}
}
?>