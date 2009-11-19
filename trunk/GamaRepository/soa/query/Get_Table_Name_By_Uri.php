<?php

/**
 * Converts a property URI to the name of the database table
 * representing the statements of that property. 
 * 
 * See also <a href="?service=ListRepoProperties&help">ListRepoProperties</a>.
 * TODO: unstable service
 */
class Get_Table_Name_By_Uri extends RPC_Service
{
	/**
	 * URI of the property.
	 * @datatype uri
	 * @required
	 */
	static $PARAM_PROPERTY_URI = 'uri';
	
	/**
	 * Returns the table name as a single value.
	 */
	public function execute()
	{
		$propertyUri = $this->getParam(self::$PARAM_PROPERTY_URI, self::REQUIRED);
		
		$rpcclient = $this->getRpcClient();
		$id = $rpcclient->{'query/Get_Resource_Id_By_Uri'}($propertyUri);
		
		echo "S_$id";
	}
}
?>