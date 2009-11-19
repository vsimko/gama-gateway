<?php

/**
 * Provides a list of properties in the repository.
 */
class List_Repo_Properties extends RPC_Service
{
	/**
	 * The output format is a simple text file containing one URI per line.
	 */
	public function execute()
	{
		$store = GAMA_Store::singleton();
		
//		$sqlQueryString = '
//			SELECT table_comment
//			FROM information_schema.tables
//			where
//				table_schema=?
//				and table_name like "S\_%"
//			LIMIT 0,1000
//		';

		$sqlQueryString = 'SELECT uri FROM PROPERTY';

		$stmt = $store->sql($sqlQueryString, GAMA_Store::getDatabaseName());
		
		header('Content-type: text/plain');
		while( $row = $stmt->fetch(PDO::FETCH_NUM) )
		{
			echo $row[0]."\n";
		}
	}
}

?>