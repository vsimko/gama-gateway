<?php

/**
 * Provides number of resources of a given class determined by rdf:type.
 */
class Num_Type_Of_Class extends RPC_Service
{
	/**
	 * URI of the class.
	 * @datatype uri
	 * @required
	 */
	static $PARAM_CLASS_URI = 'uri';
	
	/**
	 * Number.
	 */
	public function execute()
	{
		$classUri = $this->getParam(self::$PARAM_CLASS_URI);
		
		$rpcclient = $this->getRpcClient();
		$classId = $rpcclient->{'query/Get_Resource_Id_By_Uri'}($classUri);
		
		$store = GAMA_Store::singleton();
		echo $store->sqlFetchValue('
			select count(*)
			from RESOURCE
			where type = ?
			', $classId );
	}
}

?>