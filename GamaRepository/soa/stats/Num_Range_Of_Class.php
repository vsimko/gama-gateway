<?php

/**
 * Provides number of properties where the given class appeared as rdfs:range.
 */
class Num_Range_Of_Class extends RPC_Service
{
	/**
	 * URI of the class.
	 * @datatype uri
	 * @required
	 */
	static $PARAM_CLASS_URI = 'uri';
	
	const RDF_RANGE_URI = 'http://www.w3.org/2000/01/rdf-schema#range';
	
	/**
	 * Number.
	 */
	public function execute()
	{
		$classUri = $this->getParam(self::$PARAM_CLASS_URI);
		
		$rpcclient = $this->getRpcClient();
		$classId = $rpcclient->{'query/Get_Resource_Id_By_Uri'}($classUri);
		$propertyId = $rpcclient->{'query/Get_Resource_Id_By_Uri'}(self::RDF_RANGE_URI);
		
		$store = GAMA_Store::singleton();
		echo $store->sqlFetchValue("
			select count(*)
			from S_$propertyId
			where object = ?
			", $classId );
	}
}

?>