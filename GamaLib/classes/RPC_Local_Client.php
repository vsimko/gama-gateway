<?php

/**
 * Call RPC functions locally represented as methods of the local rpc client class.
 */
class RPC_Local_Client implements RPC_Client_Interface
{
	/**
	 * @var RPC_Server
	 */
	private $serverContext;
	
	/**
	 * @var array
	 */
	private $cachedInstances = array();
	
	/**
	 * Stores the server context which, besides other information, contains
	 * the name of a directory with all local services.
	 * @param RPC_Server $serverContext
	 */
	public function __construct(RPC_Server $serverContext)
	{
		$this->serverContext = $serverContext;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see classes/RPC_Client_Interface#__call($methodName, $methodParams)
	 */
	function __call($methodName, array $methodParams)
	{
		if(count($methodParams) > 1)
		{
			return $this->callService($methodName, $methodParams);
		} else
		{
			return $this->callService($methodName, (array) @$methodParams[0]);
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see classes/RPC_Client_Interface#callService($serviceName, $serviceParams)
	 */
	public function callService($serviceName, array $serviceParams = array())
	{
		if(empty($this->cachedInstances[$serviceName]))
		{
			$this->cachedInstances[$serviceName] =
				$this->serverContext->createService($serviceName);
		}
		
		// prepare parameters for the call
		$this->cachedInstances[$serviceName]->setServiceParams($serviceParams);
		
		ob_start();
		$this->cachedInstances[$serviceName]->execute();
		return ob_get_clean();
	}
	
	public function callPassthruService($serviceName, array $serviceParams = array())
	{
		if(empty($this->cachedInstances[$serviceName]))
		{
			$this->cachedInstances[$serviceName] =
				$this->serverContext->createService($serviceName);
		}
		
		// prepare parameters for the call
		$this->cachedInstances[$serviceName]->setServiceParams($serviceParams);
		
		$this->cachedInstances[$serviceName]->execute();
	}
	
	/**
	 * Note: At the moment, callStream function is more efficient in the local RPC client.
	 * @see classes/RPC_Client_Interface#callStreamService($serviceName, $serviceParams)
	 */
	function callStreamService($serviceName, array $serviceParams = array())
	{
		$fh = tmpfile();
		fwrite($fh, $this->callService($serviceName, $serviceParams) );
		return $fh;
	}
}
?>