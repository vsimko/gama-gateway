<?php
/**
 * Returns some metadata about the given resource identified by URI.
 * @author Viliam Simko
 */
class Get_Some_Metadata extends RPC_Service
{
	/**
	 * URI of the resource
	 * @datatype uri
	 * @required
	 */
	static $PARAM_URI	= 'uri';
	
	/**
	 * Work title, Manifestation title or Person name
	 * @datatype boolean
	 * @default true
	 * @optional
	 */
	static $PARAM_GET_TITLE = '-title';
	
	/**
	 * Work description, Manifestation description or Person biography
	 * @datatype boolean
	 * @default true
	 * @optional
	 */
	static $PARAM_GET_INFO = '-info';
	
	/**
	 * Names of artists for a given work.
	 * @datatype boolean
	 * @optional
	 */
	static $PARAM_GET_ARTISTS = '-artists';
	
	/**
	 * Date interval such as creation of a work, or lifespan of a person.
	 * @datatype boolean
	 * @optional
	 */
	static $PARAM_GET_DATES = '-dates';
	
	/**
	 * List of work types
	 * @datatype boolean
	 * @optional
	 */
	static $PARAM_GET_TYPES = '-types';
	
	/**
	 * Source archive.
	 * @datatype boolean
	 * @optional
	 */
	static $PARAM_GET_ARCHIVE = '-archive';
	
	/**
	 * Some metadata in JSON format.
	 */
	function execute()
	{
		$uri = $this->getParam(self::$PARAM_URI, self::REQUIRED);
		
		$rpcclient = $this->getRpcClient();
		
		$query = array('
			PREFIX gama: <http://gama-gateway.eu/schema/>
			AGGREGATE type => ?type
			select * {'.GAMA_Utils::escapeSparqlUri($uri).' a ?type}
			');
		
		// ----------------------------------------
		if($this->getParam(self::$PARAM_GET_TITLE, self::OPTIONAL))
		{
			$query[] = '
			AGGREGATE title => ?x_lang => ?x + ", "
			select * {'.GAMA_Utils::escapeSparqlUri($uri).' gama:work_title ?x}
			
			AGGREGATE title => ?x_lang => ?x + ", "
			select * {'.GAMA_Utils::escapeSparqlUri($uri).' gama:manif_title ?x}
			
			AGGREGATE title => ?x_lang => ?x + ", "
			select * {'.GAMA_Utils::escapeSparqlUri($uri).' gama:person_name ?x}
			';
		}
	
		// ----------------------------------------
		if($this->getParam(self::$PARAM_GET_INFO, self::OPTIONAL))
		{
			$query[] = '
			AGGREGATE info => ?x_lang => ?x + ", "
			select * {'.GAMA_Utils::escapeSparqlUri($uri).' gama:work_description ?x}
			

			AGGREGATE info => ?x_lang => ?x + ", "
			select * {'.GAMA_Utils::escapeSparqlUri($uri).' gama:manif_description ?x}
			
			AGGREGATE info => ?x_lang => ?x + ", "
			select * {'.GAMA_Utils::escapeSparqlUri($uri).' gama:biography ?x}
			';
		}
		
		// ----------------------------------------
		if($this->getParam(self::$PARAM_GET_ARTISTS, self::OPTIONAL))
		{
			$query[] = '
			AGGREGATE artists => ?p => ?name_lang => ?name + ", "
			select * {
				'.GAMA_Utils::escapeSparqlUri($uri).' gama:has_creator ?p.
				?p gama:person_name ?name
			}';
		}
		
		// ----------------------------------------
		if($this->getParam(self::$PARAM_GET_DATES, self::OPTIONAL))
		{
			$query[] = '
			AGGREGATE work_created => ?x + ", "
			select * {'.GAMA_Utils::escapeSparqlUri($uri).' gama:work_created ?x}
			';
			
			$query[] = '
			AGGREGATE life_span => ?x + ", "
			select * {'.GAMA_Utils::escapeSparqlUri($uri).' gama:life_span ?x}
			';
		}

		// ----------------------------------------
		if($this->getParam(self::$PARAM_GET_TYPES, self::OPTIONAL))
		{
			$query[] = '
			AGGREGATE work_types => ? => ?t + ", "
			select * {
				'.GAMA_Utils::escapeSparqlUri($uri).' gama:work_type ?t}
			';
		}
		
		// ----------------------------------------
		if($this->getParam(self::$PARAM_GET_ARCHIVE, self::OPTIONAL))
		{
			$query[] = '
			AGGREGATE archive => ?archive => name => ?name + ", "
			select * {
				'.GAMA_Utils::escapeSparqlUri($uri).' gama:provided_by ?archive.
				?archive gama:archive_name ?name.
			}';
			
			$query[] = '
			AGGREGATE archive => ?archive => homepage => ?homepage
			select * {
				'.GAMA_Utils::escapeSparqlUri($uri).' gama:provided_by ?archive.
				?archive gama:archive_homepage ?homepage.
			}';
		}
		
		echo $this->getRpcClient()->{'query/Aggregated_Sparql'}( implode("\n", $query) );
	}
}
?>