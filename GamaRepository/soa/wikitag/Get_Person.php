<?php
/**
 * Retrieve metadata about persons.
 * Persons are stored in GAMA RDF Repository as instances of gama:Person class.
 * 
 * @author Viliam Simko
 */
class Get_Person extends RPC_Service
{
	/**
	 * URI of the person.
	 * @datatype uri
	 * @required
	 */
	static $PARAM_URI	= 'uri';
	
	/**
	 * Person name.
	 * @datatype boolean
	 * @default true
	 * @optional
	 */
	static $PARAM_GET_NAME = '-name';
	
	/**
	 * Biography.
	 * @datatype boolean
	 * @default true
	 * @optional
	 */
	static $PARAM_GET_BIO = '-bio';
	
	/**
	 * Birthdate.
	 * @datatype boolean
	 * @optional
	 */
	static $PARAM_GET_BIRTH = '-birth';
	
	/**
	 * Date of death.
	 * @datatype boolean
	 * @optional
	 */
	static $PARAM_GET_DEATH = '-death';
	
	/**
	 * Country.
	 * @datatype boolean
	 * @optional
	 */
	static $PARAM_GET_COUNTRY = '-country';
		
	/**
	 * Some metadata in JSON format.
	 */
	function execute()
	{
		$uri = $this->getParam(self::$PARAM_URI, self::REQUIRED);
		
		$rpcclient = $this->getRpcClient();
		
		$query = array('
			PREFIX gama: <http://gama-gateway.eu/schema/>
			');
		
		// ----------------------------------------
		if($this->getParam(self::$PARAM_GET_NAME, self::OPTIONAL))
		{
			$query[] = '
			AGGREGATE name => ?name_lang => ?name
			select * {'.GAMA_Utils::escapeSparqlUri($uri).' gama:person_name ?name}
			';
		}
	
		// ----------------------------------------
		if($this->getParam(self::$PARAM_GET_BIO, self::OPTIONAL))
		{
			$query[] = '
			AGGREGATE bio => ?x_lang => ?x
			select * {'.GAMA_Utils::escapeSparqlUri($uri).' gama:biography ?x}
			';
		}
		
		// ----------------------------------------
		if($this->getParam(self::$PARAM_GET_BIRTH, self::OPTIONAL))
		{
			$query[] = '
			AGGREGATE birth => ?birth
			select * {
				'.GAMA_Utils::escapeSparqlUri($uri).' gama:life_span ?ls.
				FILTER (?birth = gama:dateIntervalBegin(?ls))
			}';
		}
		
		// ----------------------------------------
		if($this->getParam(self::$PARAM_GET_DEATH, self::OPTIONAL))
		{
			$query[] = '
			AGGREGATE death => ?death
			select * {
				'.GAMA_Utils::escapeSparqlUri($uri).' gama:life_span ?ls.
				FILTER (?death = gama:dateIntervalEnd(?ls))
			}';
		}
		
		// ----------------------------------------
		if($this->getParam(self::$PARAM_GET_COUNTRY, self::OPTIONAL))
		{
			$query[] = '
			AGGREGATE country => ?c => ?clabel_lang => ?clabel
			select * {
				'.GAMA_Utils::escapeSparqlUri($uri).' gama:person_country ?c.
				?c rdfs:label ?clabel
			}';
		}

		echo $this->getRpcClient()->{'query/Aggregated_Sparql'}( implode("\n", $query) );
	}
}
?>