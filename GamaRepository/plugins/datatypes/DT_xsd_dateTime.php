<?php

/**
 * A combination of date and time of day defined by the ISO 8601.
 * MySQL: "YYYY-MM-DDThh:mm:ss" or "YYYY-MM-DD hh:mm:ss"
 */
class DT_xsd_dateTime extends DT_xsd_date
{
	static public function getUri()
	{
		return 'http://www.w3.org/2001/XMLSchema#dateTime';
	}
	
	public function getColumnDefinition()
	{
		return array( 'object' => 'datetime');
	}
	
	public function getParsedLiteral(RDFS_Literal $literal)
	{
		try
		{
			$dateObject = new DateTime($literal->value);
			$dateStr = $dateObject->format('Y-m-d H:i:s');
		} catch (Exception $e)
		{
			throw new GAMA_Datatype_Value_Exception($e->getMessage());
		}
		
		return array('object' => $dateStr);
	}
}
?>