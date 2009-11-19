<?php
/**
 * Describes an RDFS class given as a parameter.
 */
class Get_Class_Info extends RPC_Service
{	
	/**
	 * The URI of the class to describe.
	 * @datatype uri
	 * @required
	 */
	static $PARAM_CLASS_URI = 'class';
	
	/**
	 * JSON format.
	 * <table>
	 *   <tr><th>class-uri</th><td>The URI of the described class.</td></tr>
	 *   <tr><th>num-type-of</th><td>How many resources have rdf:type this class.</td></tr>
	 *   <tr><th>num-domain-of</th><td>How many properties use this class as a domain.</td></tr>
	 *   <tr><th>num-range-of</th><td>How many properties use this class as a range.</td></tr>
	 * </table>
	 */
	public function execute()
	{
		$classUri = $this->getParam(self::$PARAM_CLASS_URI);
		
		$rpcclient = $this->getRpcClient();
		
		$classId = $rpcclient->{'query/Get_Resource_Id_By_Uri'}($classUri);
		$numTypeOf = $rpcclient->{'stats/Num_Type_Of_Class'}($classUri);
		$numDomainOf = $rpcclient->{'stats/Num_Domain_Of_Class'}($classUri); 
		$numRangeOf = $rpcclient->{'stats/Num_Range_Of_Class'}($classUri);
		
		header('Content-type: text/plain');
	
		echo GAMA_Utils::jsonPrettyEncode(array(
			"class-uri"		=> $classUri,
			"class-id"		=> $classId,
			"num-type-of"	=> $numTypeOf,
			"num-domain-of"	=> $numDomainOf,
			"num-range-of"	=> $numRangeOf,
		));
	}
}

?>