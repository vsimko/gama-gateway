<?php

/**
 * The GAMA_Metadata_Ingest class adds to the functionality of
 * the GAMA_Metadata_Writer the communication with the
 * GAMA RDF Repository endpoint.
 * 
 * The method "newIngest" acts as a delimiter in the communication protocol.
 * Data between the GAMA_Metadata_Ingest class and the RDF repository use
 * the HTTP protocol as if they were sent from the web browser.
 */
class GAMA_Metadata_Ingest
{
	/**
	 * The URL of the GAMA RDF repository.
	 * @var string
	 */
	private $repoLocation;
	
	/**
	 * @var string
	 */
	private $baseUri;
	
	/**
	 * @var GAMA_Metadata_Writer
	 */
	private $gamaMetadataWriter;
	
	/**
	 * @var string
	 */
	private $tmpOutputLoc;
	
	/**
	 * If TRUE then the endIngest sends the RDF data to the repository
	 * @var boolean
	 */
	private $isIngestReady = false;
	
	/**
	 * @param string $repoLocation
	 */
	public function __construct($repoLocation)
	{
		// default settings
		Config::def('ingest.timeout', 10);
		Config::def('ingest.keeptmpfile', false);
		
		$this->repoLocation = $repoLocation;
		
		// create a temporary file for writing the output
		$this->tmpOutputLoc = tempnam( GAMA_Utils::getSystemTempDir(), 'gama-mt-ingest-');
		
		$this->gamaMetadataWriter = new GAMA_Metadata_Writer();
		
		$this->gamaMetadataWriter->loadSchema($repoLocation.'/schema/');
	}
	
	/**
	 * Destructor removes all temporary files.
	 */
	public function __destruct()
	{
		$this->endIngest();
		if(!empty($this->tmpOutputLoc) && !Config::get('ingest.keeptmpfile') )
		{
			debug("Deleting the temporary file: $this->tmpOutputLoc");
			unlink($this->tmpOutputLoc);
		}
	}
	
	/**
	 * BaseUri represents the URI of the graph inserted into the repository.
	 * @param string $baseUri
	 */
	public function setBaseUri($baseUri)
	{
		$this->baseUri = $baseUri;
	}
	
	/**
	 * Delimiter in the RDF/XML stream.
	 */
	public function newIngest()
	{
		assert('/* valid base URI required */ !empty($this->baseUri)');
		
		// close the previous RDF document and send it to the repository
		$this->endIngest();
				
		// start new RDF document
		$this->gamaMetadataWriter->beginDocument($this->tmpOutputLoc, $this->baseUri);
		$this->isIngestReady = true;
	}
	
	/**
	 * Uses the base URI from the XML file.
	 *
	 * @param string $filename
	 */
	public function newIngestUsingFile($filename)
	{
		// close the previous RDF document and send it to the repository
		$this->endIngest();
		
		self::callRepoInsertDataFunction(
			$this->repoLocation,
			$filename,
			$this->httpCookie
			);
	}
	
	/**
	 * Send RDF/XML buffer to the repository. 
	 */
	public function endIngest()
	{
		if($this->isIngestReady)
		{	
			$this->gamaMetadataWriter->endDocument();
			self::callRepoInsertDataFunction(
				$this->repoLocation,
				$this->tmpOutputLoc,
				$this->httpCookie
				);
				
			$this->isIngestReady = false;
		}
	}
	
	/**
	 * HTTP cookies will be temporarily stored here.
	 * @var string
	 */
	private $httpCookie = '';
	
	/**
	 * HTTP client communicates with the repository.
	 * @param string $repoLocation
	 * @param string $fileToInsert
	 * @param string $httpCookie
	 */
	static public function callRepoInsertDataFunction($repoLocation, $fileToInsert, &$httpCookie)
	{		
		// this function works only with http and https URLs
		$match = null;
		if (preg_match('/(http|https):\/\/(([a-zA-Z0-9_\-\.]*)(:([a-zA-Z0-9_\-\.]*)@))?([a-zA-Z0-9_\-\.]*)(:([1-9][0-9]{1,4}))?(\/.*)?/', $repoLocation, $match))
		{
			@list(	/* ignore */,
					$urlProtocol,
					/* ignore */,
					$urlUser,
					/* ignore */,
					$urlPasswd,
					$urlServer,
					/* ignore */,
					$urlPort,
					$urlPath) = $match;
		} else
		{
			throw new Exception('Syntax error in the repository URL');
		}

		if(empty($urlPort))
			$urlPort = $urlProtocol=='https' ? 443 : 80;
	
		if($urlProtocol=='https')
			$urlServer = 'ssl://'.$urlServer;

		debug("================================================================");
		debug("New HTTP request to the repository: $repoLocation");
		debug_time_measure(__FUNCTION__);
		
		$sock = fsockopen($urlServer, $urlPort, $errno, $errstr, Config::get('ingest.timeout'));
	
		if(!$sock)
		{
			throw new Exception('Could not open socket');
		}
		
		$boundary = uniqid();
		
		$postFileHeaders = "--$boundary\r\n".
			"Content-Disposition: form-data; name=\"file\"; filename=\"data.rdf\"\r\n".
			"Content-Type: text/rdf\r\n".
			"\r\n";
		
		$postFinalBoundary = "\r\n--$boundary--\r\n";
		
		$postRequestHeaders =
			"POST {$urlPath}/endpoint/insert.php HTTP/1.0\r\n".
			"Host: {$urlServer}\r\n".
			"User-Agent: GAMA_Metadata_Ingest HTTP client\r\n".
			"Accept: */*\r\n".
			"Cookie: $httpCookie\r\n".
			"Content-type: multipart/form-data; boundary=$boundary\r\n".
			"Content-Length: ".(strlen($postFileHeaders) + filesize($fileToInsert) + strlen($postFinalBoundary))."\r\n".
			'Authorization: Basic ' . base64_encode($urlUser . ':' . $urlPasswd) . "\r\n".
			"\r\n";
		
		//writing to the socket starts here
		fwrite($sock, $postRequestHeaders);
				
		// send the RDF file
		fwrite($sock, $postFileHeaders);

		debug("Sending the file as multipart/form-data: $fileToInsert (".filesize($fileToInsert)." bytes)");
		$fh = fopen($fileToInsert, 'r');
		while(!feof($fh))
		{
			fwrite($sock, fread($fh, 65536));
		}
				
		// write final boundary
		fwrite($sock, $postFinalBoundary);

		// receive the response headers
		$responseHeaders = array();
		while ($str = trim(fgets($sock, 4096)))
		{
			if(preg_match('/^([^:]+):\s*(.*)/', $str, $match))
			{
				$responseHeaders[strtolower($match[1])] = $match[2];
			}
		}
			
		// receive the response data
		debug('Receiving the HTTP response from the repository...');
		$responseData = '';
		while (!feof($sock))
		{
			$responseData .= fgets($sock, 4096);
		}
		
		// remember the cookie for the next request
		if(preg_match('/^([^;]+)/', @$responseHeaders['set-cookie'], $match))
		{
			$httpCookie = $match[1];
		}
		
		//debug('RESPONSE HEADERS =====================');
		//debug($responseHeaders);
		//debug('RESPONSE DATA: =======================');
		debug($responseData);
		
		fclose($sock);
		debug_time_measure(__FUNCTION__);
		debug("HTTP request finished\n");
	}
	
	/**
	 * Method calls mapped to the GAMA_Metadata_Writer instance.
	 * @param string $name
	 * @param array $args
	 * @return mixed
	 */
	public function __call($name, $args)
	{
		return call_user_func_array(array($this->gamaMetadataWriter, $name), $args);
	}
}
?>