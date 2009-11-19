<?php

require_once 'config.php';

// use uploaded files as parameters
if(!empty($_FILES))
{
	foreach($_FILES as $paramName => $uploadedFile)
	{
		$_REQUEST[$paramName] = $uploadedFile;
	}
}


require_once 'RPC_Server.php';
$server = new RPC_Server('soa');
$server->handleRequest($_REQUEST);

?>
