<?php
/**
 * Provides a list of RDFS classes in the repository.
 */
class List_Repo_Classes extends RPC_Service
{
	/**
	 * The output format is a simple text file containing one URI per line.
	 */
	public function execute()
	{
		$store = GAMA_Store::singleton();
		
		$sqlQueryString = '
			select distinct
			  t.uri as uri
			from
			  RESOURCE r
			  join RESOURCE t on r.type = t.id
		';
		
		$stmt = $store->sql($sqlQueryString);

		@header('Content-type: text/plain');
		while( $row = $stmt->fetch(PDO::FETCH_NUM) )
		{
			echo $row[0]."\n";
		}
	}
}

?>