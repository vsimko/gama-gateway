<?php
/**
 * Provides a list of available services.
 */
class List_Services extends RPC_Service
{
	/**
	 * Show only services from a given package
	 * @datatype string
	 * @optional
	 */
	static $PARAM_PACKAGE = 'package';
	
	/**
	 * The recursion starts in the dir where this file is located.
	 * @var string
	 */
	private $startDir;
	
	/**
	 * Exclusion regex patterns.
	 * Relative to the startDir.
	 * @var string
	 */
	private $excludePattern = '/^config|^index/';
	
	/**
	 * HTML page containing a list of all services.
	 */
	public function execute()
	{
		echo "<h2>List of available services</h2>\n";
		$this->startDir = realpath(dirname(__FILE__));
		
		$startInDir = $this->startDir.'/'.$this->getParam(self::$PARAM_PACKAGE, self::OPTIONAL);
		
		// link to parent service
		$parentServiceName = dirname(substr($startInDir, strlen( $this->startDir )));
		echo "(<a href='?".self::SERVICE_PARAM_NAME."=".
			htmlspecialchars($this->serviceName).'&package='.
			htmlspecialchars($parentServiceName)."'>..</a>)\n";
			
		$this->listDirRecursively($startInDir);
	}
	
	/**
	 * Recursive function that renders list of services in a given package.
	 * @param $dirname
	 */
	private function listDirRecursively($dirname)
	{
		// remove ".." characters
		$dirname = str_replace(array('..','./'), '', $dirname);
		
		// relative to the services dir
		$packageName = substr($dirname, strlen( $this->startDir ));
				
		//remove the leading slashes
		$packageName = preg_replace('/^\/+/','', $packageName);
		
		// link to the service
		echo "Package: <a href='?".self::SERVICE_PARAM_NAME."=".
			htmlspecialchars($this->serviceName).'&package='.
			htmlspecialchars($packageName)."' >$packageName</a>\n";
		
		$packageName = empty($packageName) ? '' : $packageName.'/';
		
		echo "<ul>\n";
		foreach( glob($dirname.'/*') as $entry)
		{
			$entryBaseName = basename($entry);
			
			// use the exclusion pattern relative to the startDir
			if(! preg_match($this->excludePattern, $packageName.$entryBaseName ))
			{
				echo "<li>";
				if(is_dir($entry))
				
				{
					$this->listDirRecursively($entry);
				} else
				{
					$serviceName = substr($entryBaseName, 0, -4);
					$encodedServiceName = htmlspecialchars($serviceName);
					echo "<a href='?".self::SERVICE_PARAM_NAME."=".
						htmlspecialchars($packageName.$serviceName)."&help'>".
						htmlspecialchars($serviceName)."</a>";
				}
				echo "</li>\n";
			}
		}
		echo "</ul>\n";
	}
		
}

?>