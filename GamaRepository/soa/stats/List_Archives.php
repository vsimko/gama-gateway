<?php
/**
 * Just the list of archives.
 */
class List_Archives extends RPC_Service
{
	/**
	 * JSON format which contains:
	 * - archive URI
	 * - archive name
	 * - archive homepage
	 */
	public function execute()
	{
		@header('Content-type: text/plain');
		echo $this->getRpcClient()->{'query/Aggregated_Sparql'}('
			PREFIX gama: <http://gama-gateway.eu/schema/>
		
			AGGREGATE ?archive => "archive_name" => ?name + ,
			SELECT * { ?archive gama:archive_name ?name }
			
			AGGREGATE ?archive => "archive_homepage" => ?homepage + ,
			SELECT * { ?archive gama:archive_homepage ?homepage }
		');
	}
}

?>