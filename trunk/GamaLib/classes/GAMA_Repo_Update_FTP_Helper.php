<?php
/**
 * Traverses the GAMA Update FTP server and extract list of RDF/XML files.
 * 
 * Example:
 * <code>
 *   $helper = new GAMA_Repo_Update_FTP_Helper;
 *   $helper->connect($host, $user, $pass);
 *   $files = $helper->getRdfXmlFiles('Update');
 *   foreach($files as $fname)
 *   {
 *     $url = 'ftp://$user:$pass@$host/$fname';
 *     echo "$url\n";
 *   }
 * </code>
 * 
 * @author Viliam Simko
 */
class GAMA_Repo_Update_FTP_Helper
{
	public function __destruct()
	{
		ftp_close($this->ftpConnection);
	}
	
	private $ftpConnection;
	
	/**
	 * Establish the connection with FTP server.
	 * @param $hostname
	 * @param $username
	 * @param $password
	 * @return unknown_type
	 */
	public function connect($hostname, $username, $password)
	{
		$this->ftpConnection = ftp_connect($hostname);
		
		if(!$this->ftpConnection)
		{
			throw new Exception('Could not connect to FTP');
		}
		
		// login with username and password
		$login = ftp_login($this->ftpConnection, $username, $password); 

		// always use passive mode
		ftp_pasv($this->ftpConnection, true);
		
		// check connection
		if (!$login)
		{
			throw new Exception('Connected, but cound not login to FTP server');
		}
	}
	
	/**
	 * Traverse the FTP directory and return an array of urls that represent
	 * RDF/XML files stored on the FTP server.
	 * @param $startDir
	 * @return array
	 */
	public function getRdfXmlFiles($startDir)
	{
		ftp_chdir($this->ftpConnection, $startDir);
		return $this->_recursive_get();
	}

	const PREG_INCLUDE_PATTERN = '/\.(xml|xml\.gz|rdf|rdf\.gz|owl|owl\.gz)$/';
	const PREG_EXCLUDE_PATTERN = '/^$/';

	function _recursive_get()
	{
		$olddir = ftp_pwd($this->ftpConnection);
	
		$result = array();
		foreach(ftp_nlist($this->ftpConnection, '.') as $fname)
		{
			if(@ftp_chdir($this->ftpConnection, $fname))
			{
				$result2 = call_user_func(array($this, __METHOD__));
				$result = array_merge($result, $result2);
			} elseif(
				preg_match(self::PREG_INCLUDE_PATTERN, $fname)
				&& ! preg_match(self::PREG_EXCLUDE_PATTERN, $fname) )
			{
				$result[] = "$olddir/$fname";
			}
			ftp_chdir($this->ftpConnection, $olddir);
		}
		return $result;
	}
}
?>