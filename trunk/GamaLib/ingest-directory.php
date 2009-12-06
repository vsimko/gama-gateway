#!/usr/bin/php
<?php

// ====================================================================
// Example: http://url/of/your/gama/GamaRepository/';
$DEF_REPOSITORY_LOCATION	= '';
$DEF_DIR					= '.';

$REPOSITORY_LOCATION = $DEF_REPOSITORY_LOCATION;
$DIR = $DEF_DIR;

$COOKIES='';
$APPNAME=array_shift($argv);
// ====================================================================

function usage()
{
    echo "\nUSAGE:\n";
    echo $GLOBALS['APPNAME'].' [-r <repository URL>] [<dir>]';
    echo "\n";
    echo "\n   repository URL  = default value is $GLOBALS[DEF_REPOSITORY_LOCATION]";
    echo "\n   dir             = default value is $GLOBALS[DEF_DIR]";
    echo "\n\n";
    exit;
}

while(count($argv))
{
	$arg = array_shift($argv);
	switch($arg)
	{
		case '--help':
		case '-help':
		case '-h':
		
		case '--repository':
		case '--repo':
		case '-r':
			$REPOSITORY_LOCATION = array_shift($argv);
			break;
			
		default:
			$DIR = $arg;
			if(count($argv)) // no further arguments are allowed
			{
				usage();
			}
			break;
	}
}


if(empty($DIR) || empty($REPOSITORY_LOCATION))
{
    usage();
}


function debug($message)
{
 	echo "$message\n";
}

function send_http_post_file($repoLocation, $fileToInsert, &$httpCookie)
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
	
	$sock = fsockopen($urlServer, $urlPort, $errno, $errstr, 10);

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
		echo fgets($sock, 4096);
	}
	
	// remember the cookie for the next request
	if(preg_match('/^([^;]+)/', @$responseHeaders['set-cookie'], $match))
	{
		$httpCookie = $match[1];
	}
	
	fclose($sock);
	debug("HTTP request finished\n");
}


// accepts also gzipped files
foreach(glob($DIR.'/*.{xml,xml.gz,rdf,rdf.gz,owl,owl.gz}', GLOB_BRACE) as $filename)
{
    send_http_post_file($REPOSITORY_LOCATION, $filename, $COOKIES);
}

?>