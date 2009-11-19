<?php

/**
 * Generates a list of URIs of a given rdf:type.
 */
class Get_Uris_By_Type extends RPC_Service
{
	/**
	 * rdf:type URI
	 * @datatype uri
	 * @required
	 */
	static $PARAM_TYPE_URI = 'type';
	
	/**
	 * URI per line, distinct values.
	 */
	public function execute()
	{
		$typeUri = $this->getParam(self::$PARAM_TYPE_URI);
		
		$store = GAMA_Store::singleton();
		
		$rpcclient = $this->getRpcClient();
		$typeId = $rpcclient->{'query/Get_Resource_Id_By_Uri'}($typeUri);
		
		$stmt = $store->sql('select uri from RESOURCE where type=?', $typeId);
		
		@header('Content-type: text/plain');
		while($row = $stmt->fetch(PDO::FETCH_NUM))
		{
			echo $row[0]."\n";
		}
	}
}

?>