<?php

/**
 * Simple RPC client for PHP
 * @author Viliam Simko
 */
interface RPC_Client_Interface
{
	/**
	 * Syntactic sugar for the callService method.
	 * @param string $methodName
	 * @param array $methodParams
	 * @return mixed
	 */
	function __call($methodName, array $methodParams);
	
	/**
	 * The service is called and the result is returned as a string.
	 * @param string $serviceName
	 * @param array $serviceParams
	 * @return string
	 */
	function callService($serviceName, array $serviceParams = array());
	
	/**
	 * This method calls the service and returns an opened resource
	 * (socket, file descriptor etc.) which can be used by the fread function.
	 * You have to close the resource after you have read all the response
	 * content.
	 * 
	 * @param string $serviceName
	 * @param array $serviceParams
	 * @return resource
	 */
	function callStreamService($serviceName, array $serviceParams = array());
}
?>