<?php

/**
 * Remove a specific property from the repository.
 * All statements and other references will be removed.
 */
class Delete_Schema_Property extends RPC_Service
{
	/**
	 * URI of the property to be removed
	 * @datatype uri
	 * @required
	 */
	static $PARAM_PROPERTY_URI = 'property';

	/**
	 * TODO: better comment
	 */
	public function execute()
	{
		$propertyUri = $this->getParam(self::$PARAM_PROPERTY_URI, self::REQUIRED);
		$property = Resource_Manager::singleton()->getResourceByUri($propertyUri);

		$property->isInStore(true);
		$property->deleteFromStore();
		
		echo "ok, property deleted";
	}
}

?>