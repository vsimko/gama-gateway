<?php

/**
 * This class requires the 'grep' UNIX command.
 * @author Viliam Simko
 */
class GAMA_Blacklist
{
	/**
	 * The ID represents the filename containing the list of deleted URIs.
	 * @var string
	 */
	private $id;
	
	/**
	 * The constructor requires the content partner's identifier,
	 * such as "monte" or "argos".
	 * @param string $id
	 */
	public function __construct($id)
	{
		$this->id = $id;
	}
	
	/**
	 * Prepares the filesystem path to the list of deleted URIs.
	 * @return string
	 */
	private function getBlacklistFilename()
	{
		return 'Update/blacklist/'.$this->id.'.txt';
	}
	
	/**
	 * Appends a new line containing the URI at the end of the blacklist file.
	 * This function does not tests whether the URI already existed in the file.
	 * @param string $uri
	 */
	public function addUriToFilter($uri)
	{
		$blacklistFilename = $this->getBlacklistFilename();
		$fh = @fopen($blacklistFilename, 'a+');
		if(empty($fh))
		{
			throw new Exception("Could not append to the blacklist file: $blacklistFilename (in ".getcwd().")");
		}
		fwrite($fh, "$uri\n"); //appending new line to the file
		fclose($fh);
		chmod($blacklistFilename, 0664);
	}
	
	/**
	 * Removes URI from the list (also works with multiple occurrences.
	 * Nothing happens if the URI does not exist in the list.
	 * 
	 * Note: We use the UNIX grep command.
	 * 
	 * @param string $uri
	 */
	public function removeUriFromFilter($uri)
	{
		$blacklistFilename = $this->getBlacklistFilename();
		$tmpfilename = tempnam('', 'gama-blacklist-');
		$returnValue = 1;
		system("grep -v -F '".escapeshellarg($uri)."' '".escapeshellarg($blacklistFilename)."' > '".escapeshellarg($tmpfilename)."'", $returnValue);
		
		if($returnValue == 0)
		{
			rename($tmpfilename, $blacklistFilename);
			chmod($blacklistFilename, 0664);
		}
	}
	
	/**
	 * Searches for the URIs inside the blacklist list.
	 * Note: We use the UNIX grep command.
	 * @param string $uri
	 * @return boolean
	 */
	public function isBlacklistedUri($uri)
	{
		$returnValue = 1;
		system("grep -q -F '".escapeshellarg($uri)."' '".escapeshellarg($this->getBlacklistFilename())."' ", $returnValue);
		
		return $returnValue == 0;
	}
		
	const REQ_PARAM_ACTION = 'action';
	const ACTION_BLACKLIST = 'blacklist';
	const ACTION_ALLOW = 'allow';
	
	/**
	 * Prepares the callback from a given action.
	 * 
	 * @param string $action
	 * @return array (callback)
	 */
	private function actionToMethod($action)
	{
		$methodName = "action_$action";
		if( empty($action) || !method_exists($this, $methodName) )
		{
			return 'action_default';
		}
		
		return $methodName;
	}
	
	/**
	 * Handles the HTTP request by dispatching the actions to other methods
	 * within this class. Some HTML code will be rendered as a result.
	 * @param array $requestParams
	 */
	public function handleHttpRequest($requestParams)
	{
		$action = strtolower(@$requestParams[self::REQ_PARAM_ACTION]);		
		$methodName = $this->actionToMethod($action);
		
		try
		{
			$this->$methodName($requestParams);
		} catch(Exception $e)
		{
			echo "Error: ".$e->getMessage();
		}
	}
	
	/**
	 * @param array $requestParams
	 */
	public function action_default($requestParams)
	{
		$FILTER = $this->getBlacklistFilename();
		$SCRIPT = 'blacklist_'.$this->id.'.php';
		require 'tpl_default.php';
	}
	
	/**
	 * @param array $requestParams
	 */
	public function action_blacklist($requestParams)
	{
		$uri = trim(@$requestParams['uri']);
		if(empty($uri))
		{
			$requestParams['msg'][] = 'URI Required';
			return $this->action_default($requestParams);
		}
		
		$this->addUriToFilter($uri);
		$requestParams['msg'][] = "Added '$uri' to the blacklist";
		return $this->action_default($requestParams);
	}

	/**
	 * @param array $requestParams
	 */
	public function action_allow($requestParams)
	{
		$uri = trim(@$requestParams['uri']);
		if(empty($uri))
		{
			$requestParams['msg'][] = 'URI Required';
			return $this->action_default($requestParams);
		}
		
		$this->removeUriFromFilter($uri);
		$requestParams['msg'][] = "Removed '$uri' from the blacklist";
		return $this->action_default($requestParams);
	}
	
	/**
	 * @param array $requestParams
	 */
	public function action_check($requestParams)
	{
		$uri = trim(@$requestParams['uri']);
		if(empty($uri))
		{
			$requestParams['msg'][] = 'URI Required';
		}else if($this->isBlacklistedUri($uri))
		{
			$requestParams['msg'][] = "The URI '$uri' is already blacklisted";
		} else
		{
			$requestParams['msg'][] = "The URI '$uri' is NOT blacklisted";
		}
		
		return $this->action_default($requestParams);
	}
	
	/**
	 * @param array $requestParams
	 */
	public function action_show($requestParams)
	{
		@header('Content-type: text/plain');
		echo "Blacklisted URIs:\n";
		echo "-----------------\n";
		echo @file_get_contents($this->getBlacklistFilename());
	}
}
?>