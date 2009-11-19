<?php
/**
 * Just the list of Work Types.
 */
class List_Work_Types extends RPC_Service
{
	/**
	 * JSON format which contains:
	 * - Work Type URI
	 * - Work Type label
	 * - Work Type comment
	 */
	public function execute()
	{
		@header('Content-type: text/plain');
		echo $this->getRpcClient()->{'query/Aggregated_Sparql'}('
			PREFIX gama: <http://gama-gateway.eu/schema/>
			PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
		
			AGGREGATE ?wt => "label" => ?label_lang => ?label + \n
			SELECT * { ?wt a gama:WorkType. ?wt rdfs:label ?label }
			
			AGGREGATE ?wt => "comment" => ?comment_lang => ?comment + \n
			SELECT * { ?wt a gama:WorkType. ?wt rdfs:comment ?comment }
		');
	}
}

?>