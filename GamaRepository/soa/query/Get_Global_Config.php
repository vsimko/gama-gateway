<?php
/**
 * Provides a set of global configuration items.
 */
class Get_Global_Config extends RPC_Service
{
	/**
	 * JSON
	 */
	public function execute()
	{
		$config = array(
			'repo.dbname'		=> Config::get('repo.dbname'),
			'repo.mediabaseurl'	=> Config::get('repo.mediabaseurl'),
			'repo.idxversion'	=> Config::get('repo.idxversion'),
		);
		
		@header('Content-type: text/plain');
		echo GAMA_Utils::jsonPrettyEncode($config);
	}
}

?>