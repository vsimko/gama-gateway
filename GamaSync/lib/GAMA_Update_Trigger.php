<?php

/**
 * Encapsulates function that allow to work with the GAMA Update Lifecycle.
 * 
 * - phase transitions
 * - e-mail notification
 * - http-call notification
 * 
 * @author Viliam Simko
 */
class GAMA_Update_Trigger
{
	const LOCK_FILENAME = 'Update/.lock';
	const REGEX_EMPTY_CHARS = '/[∅\s]/';
	
	private $replyToEmail = 'noreply@noreply.org';
	
	function __construct()
	{
		header('Content-type: text/plain');
	}
	
	/**
	 * Generate a link to a specific script (or other file)
	 * in the root directory of the GamaSync tool.
	 * @param $scriptName
	 * @return string
	 */
	public function getScriptUrl($scriptName)
	{
		return 'http://'.$_SERVER['SERVER_NAME'].dirname($_SERVER['REQUEST_URI']).'/'.$scriptName;
	}
	
	/**
	 * Report internal server error.
	 * @param $errorMessage
	 */
	function reportError($errorMessage)
	{
		header($_SERVER["SERVER_PROTOCOL"]." 500 Internal Server Error");
		header("Error: 1");
		die($errorMessage);
	}
	
	/**
	 * Reports a conflict in the GAMA Update protocol.
	 * @param $errorMessage
	 */
	function reportConflict($errorMessage)
	{
		header($_SERVER["SERVER_PROTOCOL"]." 409 Conflict");
		header('Error: 2');
		die($errorMessage);
	}
	
	private $appliedTransition = 'none';
	
	/**
	 * - without acquiring the lock.
	 * @return string
	 */
	public function getCurrentPhase()
	{
		return file_get_contents(self::LOCK_FILENAME);
	}
	
	/**
	 * Try to apply transtions using the locking mechanism.
	 * @param $_ (variable arguments) transtions in format "X -> Y"
	 * @return string The applied transtion
	 */
	function changePhase($_)
	{
		// acquire the lock
		$lockfile = @fopen(self::LOCK_FILENAME, 'a+');
		if(!$lockfile || !flock($lockfile, LOCK_EX))
		{
			$this->reportError('Could not acquire file lock');
		}
		
		// lock acquired, now read lines
		fseek($lockfile, 0);
		$content = fread($lockfile, 4096);
		$info = explode("\n", $content);
	
		// current phase is located on the first line
		$currentPhase = @$info[0];
		
		$allTransitions = func_get_args();
		
		// try to find a matching transition 
		foreach($allTransitions as $transition)
		{
			// syntax of the transition must be: X -> Y
			$match = array();
			if(!preg_match('/^\s*([^\s]*)\s*->\s*([^\s]*)/', $transition, $match))
			{
				$this->reportError('Wrong transition. This is a programming error!');
			}
	
			// character "∅" represents an empty string (syntactic sugar) 
			$fromPhase = preg_replace( self::REGEX_EMPTY_CHARS, '', $match[1]);
			$toPhase = preg_replace( self::REGEX_EMPTY_CHARS, '', $match[2]);
			
			// can we apply the transition ?
			if($currentPhase == $fromPhase)
			{
				// yes, we can
				ftruncate($lockfile, 0);
				fwrite($lockfile, $toPhase."\n".date('Y-m-d H:i:s').' IP Address: '.$_SERVER['REMOTE_ADDR']);
				fclose($lockfile);
				
				echo "Successfully applied the transition: $transition\n";
				$this->appliedTransition = $transition;
				return $transition;
			}
		}
		
		if(empty($currentPhase))
		{
			$currentPhase = '∅';
		}
		
		// no transition could have been applied	
		$this->reportConflict(
			"Neither of the following transitions could have been applied:\n".
			implode("\n", $allTransitions).
			"\n\n".
			"The current phase of the update lifecycle is: $currentPhase\n".
			@$info[1]."\n"
		);
	}
	
	private $emailSubject;	
	public function setSubject($text)
	{
		$this->emailSubject = $text;
	}
	
	private $emailMessage;
	public function setMessage($text)
	{
		$this->emailMessage = $text;
	}
	
	/**
	 * Send the notification by email.
	 * @param string $toEmail
	 */
	function notifyByEmail($toEmail)
	{
		mail(
			$toEmail,
			"[GAMA UPDATE] - $this->emailSubject",
			$this->emailMessage.
			"\n\nNote: You have received this email because your address ".
			"is included in one of the trigger scripts, see also:\n".
			$this->getScriptUrl('README.TXT').
			"\n\n---\nAPPLIED TRANSITION: $this->appliedTransition",
			'From: '.$this->replyToEmail
		);
		
		echo "Notification e-mail sent to: $toEmail\n";
	}
	
	/**
	 * 
	 * @param $url
	 * @return unknown_type
	 */
	function notifyByHttpCall($url)
	{
		echo "Notification by HTTP call: $url\n";
		echo file_get_contents($url)."\n";
	}
}

?>