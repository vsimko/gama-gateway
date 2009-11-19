<?php
/**
 * Remove all statemenets of a specific property but keep the property structure.
 */
class Cleanup_Property_Table extends RPC_Service
{
	/**
	 * URI of the property from which the statements will be deleted.
	 * @datatype uri
	 * @required
	 */
	static $PARAM_PROPERTY_URI = 'property';
	
	/**
	 * On success, some information string will be returned. 
	 */
	public function execute()
	{
		$propertyUri = $this->getParam(self::$PARAM_PROPERTY_URI, self::REQUIRED);
		
		$rpcclient = $this->getRpcClient();
		$tableName = $rpcclient->{'query/Get_Table_Name_By_Uri'}($propertyUri);
		
		GAMA_Store::singleton()->sql("DELETE FROM $tableName");
		
		echo "All data deleted from property $propertyUri which is represented by the table $tableName\n";
	}
}
?>