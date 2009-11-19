<?php

/**
 * Provides a list of named graphs in the repository.
 */
class List_Repo_Graphs extends RPC_Service
{
	/**
	 * The output format is a simple text file containing one URI per line.
	 */
	public function execute()
	{
		$store = GAMA_Store::singleton();
		
		$sqlQueryString = 'select uri from GRAPH';

		$stmt = $store->sql($sqlQueryString);

		@header('Content-type: text/plain');
		while( $row = $stmt->fetch(PDO::FETCH_NUM) )
		{
			echo $row[0]."\n";
		}
	}
}

?>