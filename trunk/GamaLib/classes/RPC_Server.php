<?php

/**
 * Server context.
 * @author Viliam Simko
 */
class RPC_Server
{
	public $rpcDir;
	
	private $localRpcClient;
	public function getLocalRpcClient()
	{
		if(empty($this->localRpcClient))
		{
			$this->localRpcClient = new RPC_Local_Client($this);
		}
		return $this->localRpcClient;
	}
	
	/**
	 * @param string $rpcDir directory containing the services
	 * @param array $requestParams parameters of the service
	 */
	public function __construct($rpcDir)
	{
		//debug("RPC server from directory: $rpcDir");
		$this->rpcDir = $rpcDir;
	}
	
	/**
	 * Executes a service based on the given request parameters.
	 */
	public function handleRequest(array &$requestParams)
	{
		try
		{
			// translate value issued by the user to the service name
			$serviceName = $this->stringToServiceName(
				@$requestParams[RPC_Service::SERVICE_PARAM_NAME]);
				
			//debug("Service name: $serviceName");
			
			$serviceInstance = $this->createService($serviceName);
			// we've got the service instance now
			
			if(isset($requestParams[RPC_Service::HELP_PARAM_NAME]))
			{
				// TODO: if the Help_Renderer service is not available, use some internal renderer
				$helpService = $this->createService('Help_Renderer');
				$helpServiceParams = array(
					'signature' => json_encode($serviceInstance->getSignature()) );
				$helpService->setServiceParams($helpServiceParams);
				$helpService->execute();
			} else
			{
				// strip slashes recursively if necessary
				if(get_magic_quotes_gpc())
				{
					array_walk_recursive( $requestParams,
						create_function('&$item', '$item = stripslashes($item);') );
				}
				$serviceInstance->setServiceParams($requestParams);
				$serviceInstance->execute();
			}
		
		} catch(Exception $e)
		{
			@header("Error:".$e->getCode());
			
			// Browser vs. RPC client
			if(strpos($_SERVER['HTTP_USER_AGENT'], 'RPC'))
			{
				@header('Content-type: text/plain');
				echo $e->getMessage();
			} else
			{
				//TODO: show the link to List_Services only if the service is available
				@header('Content-type: text/html');
				echo "Something nasty has happened:\n";
				echo "<div style='padding-left:10pt;font-size:small;color:red;white-space:pre;'>".$e->getMessage()."</div>\n";
				echo "<div style='padding-left:10pt;font-size:small;color:red;white-space:pre;'>".$e->getTraceAsString()."</div>\n";
				echo "<div style='padding:10pt;font-size:xx-small'><a href='?".RPC_Service::SERVICE_PARAM_NAME."=ListServices'>See the list of available services for more information</a></div>\n";
			}
			exit;
		}
	}
	
	/**
	 * Converts a given string into the normalized service name.
	 * The filter works as follows:
	 * 
	 * @return string
	 */
	static public function stringToServiceName($string)
	{
		if(empty($string))
		{
			throw new Exception('Empty service name provided');
		}
		
		// remove surrounding spaces
		//$string = trim($string);
		
		$packageName = strtolower(dirname($string));
		$className = basename($string);
		
		// substitute whitespaces with underscores
		//$string = str_replace(' ', '_', $string);
		
//		return $string;
		// remove weird characters
		$string = strtolower(preg_replace(
			array('/[^a-zA-Z_]/', '/([a-z])([A-Z])/'),
			array('', '$1_$2'), $className ));
		
		// normalise the name (uppercase first letter)
		return $packageName.'/'.preg_replace_callback(
			'/^(.)|[_\/]([^_])/',
			create_function('$match', 'return strtoupper($match[0]);'),
			$string );
	}

	/**
	 * Factory method.
	 * @param string $serviceName
	 * @return RPC_Service
	 */
	public function createService($serviceName)
	{
		$serviceClassName = basename($serviceName);
		add_include_path(dirname($this->rpcDir.'/'.$serviceName));
		
		// this is faster that file_exists + autoload
		$phpFileToInclude = $this->rpcDir.'/'.$serviceName.'.php';
		if(! @include_once ($phpFileToInclude) )
		{
			throw new Exception('No such service: '. $serviceName);
		}
		
		// here comes the instantiation
		return new $serviceClassName($serviceName, $this);
	}
}
?>