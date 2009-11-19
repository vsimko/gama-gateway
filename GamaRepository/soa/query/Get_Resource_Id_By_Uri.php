<?php

/**
 * Converts a given URI into local ID in the repository.
 */
class Get_Resource_Id_By_Uri extends RPC_Service
{	
	/**
	 * URI to be converted.
	 * @datatype uri
	 * @required
	 */
	static $PARAM_RESOURCE_URI = 'uri';
	
	/**
	 * Result is a single number representing the ID.
	 */
	public function execute()
	{
		$resourceUri = $this->getParam(self::$PARAM_RESOURCE_URI);
		
		$store = GAMA_Store::singleton();
		
		$resourceId = $store->sqlFetchValue('select id from RESOURCE where uri=?', $resourceUri);
		
		if(empty($resourceId))
		{
			throw new Exception("Resource '$resourceUri' not found in the repository");
		}
		
		@header('Content-type: text/plain');
		echo $resourceId;
	}
}

?>