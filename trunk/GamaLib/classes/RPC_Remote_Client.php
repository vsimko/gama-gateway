<?php

/**
 * Connects to the experimental RPC service through the HTTP protocol.
 * We provide two ways of getting the result from the remote service:
 * - whole result as a string
 * - socket from which the response can be read
 * 
 * The former is useful for short and replies such as getting an ID.
 * The latter is suitable for larger outputs such as XML files which can be
 * directly read by the PHP SAX parser in a serialised way - chunk by chunk
 * 
 * @author Viliam Simko
 */
class RPC_Remote_Client implements RPC_Client_Interface
{
	const CONNECTION_TIMEOUT = 10;
	const SOCKET_RECV_BUFFER_LEN = 4096;
	const SOCKET_SEND_BUFFER_LEN = 4096;
	const MAX_EXCEPTION_MESSAGE_LENGTH = 4096;
	const HTTP_USER_AGENT_IDENT = 'GAMA Remote RPC client';
	
	private $serverHost;
	private $serverPort;
	private $serverPath;
	private $serverSSL;
	
	/**
	 * Base64-encoded authentication token composed of username and password
	 * @var string
	 */
	private $serverAuth;
	
	/**
	 * HTTP cookies will be stored here.
	 * @var string
	 */
	private $serverCookie;
	
	/**
	 * Set up the RPC client.
	 * @param string $remoteBaseUrl
	 */
	public function __construct($remoteBaseUrl)
	{
		// this function works only with http and https URLs
		$match = null;
		if (preg_match('/(http|https):\/\/(([a-zA-Z0-9_\-\.]*)(:([a-zA-Z0-9_\-\.]*)@))?([a-zA-Z0-9_\-\.]*)(:([1-9][0-9]{1,4}))?(\/.*)?/',
			$remoteBaseUrl, $match))
		{
			$urlProtocol	= @$match[1];
			$urlUser		= @$match[3];
			$urlPasswd		= @$match[5];
			$urlHost		= @$match[6];
			$urlPort		= @$match[8];
			$urlPath		= @$match[9];
		} else
		{
			throw new Exception('Syntax error in the repository URL');
		}

		// choose the port if not explicitly specified
		if(empty($urlPort))
		{
			$urlPort = $urlProtocol=='https' ? 443 : 80;
		}
			
		$this->serverHost = $urlHost;
		$this->serverSSL = $urlProtocol == 'https' ? 'ssl://' : '';
		$this->serverPort = $urlPort;
		$this->serverPath = $urlPath;
		$this->serverAuth = base64_encode($urlUser.':'.$urlPasswd);
	}

	/**
	 * (non-PHPdoc)
	 * @see classes/RPC_Client_Interface#__call($methodName, $methodParams)
	 */
	public function __call($methodName, array $methodParams)
	{
		if(count($methodParams) > 1)
		{
			return $this->callService($methodName, $methodParams);
		} else
		{
			return $this->callService($methodName, (array) $methodParams[0]);
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see classes/RPC_Client_Interface#callService($serviceName, $serviceParams)
	 */
	public function callService($serviceName, array $serviceParams = array())
	{
		$socket = $this->callStreamService($serviceName, $serviceParams);
		
		// receive the response content
		$httpResponseContent = '';
		while (!feof($socket))
		{
			$httpResponseContent .= fgets($socket, self::SOCKET_RECV_BUFFER_LEN);
		}
		fclose($socket);
		
		return $httpResponseContent;
	}

	/**
	 * (non-PHPdoc)
	 * @see classes/RPC_Client_Interface#callStreamService($serviceName, $serviceParams)
	 */
	public function callStreamService($serviceName, array $serviceParams = array())
	{
		assert(is_array($serviceParams));
		
		$socket = fsockopen(	$this->serverSSL.$this->serverHost,
								$this->serverPort,
								$errno,
								$errstr,
								self::CONNECTION_TIMEOUT );

		if(!$socket)
		{
			throw new Exception('Could not open socket');
		}
	
		$serviceParamsEncoded = array();
		
		// first parameter is the service name
		$serviceParamsEncoded[] = 'service='.urlencode($serviceName);
		
		// we will encode the parameters here
		foreach($serviceParams as $serviceParamName => $serviceParamValue)
		{
			$serviceParamsEncoded[] = urlencode($serviceParamName).'='.urlencode($serviceParamValue);
		}
		$httpRequestContent = implode('&', $serviceParamsEncoded);//."\r\n";

		$httpRequestHeaders =
			"POST {$this->serverPath} HTTP/1.0\r\n".
			"Host: {$this->serverHost}\r\n".
			"User-Agent: ".self::HTTP_USER_AGENT_IDENT."\r\n".
			"Accept: */*\r\n".
			"Cookie: {$this->serverCookie}\r\n".
			"Content-Type: application/x-www-form-urlencoded\r\n".
			"Content-Length: ".strlen($httpRequestContent)."\r\n".
			"Authorization: Basic {$this->serverAuth}\r\n".
			"\r\n";
		
		//===========================
		// HTTP : SEND REQUEST
		//===========================
	
		// writing to the socket starts here
		fwrite($socket, $httpRequestHeaders);
		
		// send encoded parameters of the called service
		fwrite($socket, $httpRequestContent);
	
		//===========================
		// HTTP : RECEIVE RESPONSE
		//===========================
	
		// receive the response headers
		$httpResponseHeaders = array();
		while ($str = trim(fgets($socket, self::SOCKET_SEND_BUFFER_LEN)))
		{
			if(preg_match('/^([^:]+):\s*(.*)/', $str, $match))
			{
				$httpResponseHeaders[strtolower($match[1])] = $match[2];
			}
		}
			
		// remember the cookie for the next request
		if(preg_match('/^([^;]+)/', @$httpResponseHeaders['set-cookie'], $match))
		{
			$this->serverCookie = strtolower($match[1]);
		}
		
		if(isset($httpResponseHeaders['error']))
		{
			$msg = fread($socket, self::MAX_EXCEPTION_MESSAGE_LENGTH);
			fclose($socket);
			throw new Exception($msg, $httpResponseHeaders['error']);
		}
		
		return $socket;
	}
}
?>