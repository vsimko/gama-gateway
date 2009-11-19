<?php
/**
 * Information about the archive.
 * To obtain the list of archives, use the stats/List_Archives service.
 */
class Stat_Archive extends RPC_Service
{
	/**
	 * URI of the archive.
	 * @datatype uri
	 * @required
	 */
	static $PARAM_URI = 'uri';
	
	/**
	 * Split the results by work_type property instead of returning a single number.
	 * @datatype boolean
	 * @optional
	 */
	static $PARAM_X_SPLITWORKTYPE = '-splitworktype';
	
	/**
	 * Number of works provided by the archive using the provided_by property.
	 * @datatype boolean
	 * @default checked
	 * @optional
	 */
	static $PARAM_X_WORKS = '-works';

	/**
	 * Number of works without the title or with at least one empty title.
	 * @datatype boolean
	 * @default checked
	 * @optional
	 */
	static $PARAM_X_WORKSNOTITLE = '-worksnotitle';
	
	/**
	 * Number of works without the description or with at least one
	 * empty description.
	 * @datatype boolean
	 * @default checked
	 * @optional
	 */
	static $PARAM_X_WORKSNODESCR = '-worksnodescr';
	
	/**
	 * Number of works without the creator.
	 * Wroks without a creator may be invisible for the frontend.
	 * @datatype boolean
	 * @default checked
	 * @optional
	 */
	static $PARAM_X_WORKSNOCREATOR = '-worksnocreator';
	
	/**
	 * Number of works without at least one main manifestation.
	 * Works without manifestation may be invisble for the frontend.
	 * A manifestation doesn't have to have a video counterpart.
	 * It can be only metadata.
	 * @datatype boolean
	 * @default checked
	 * @optional
	 */
	static $PARAM_X_WORKSNOMAINMANIF = '-worksnomainmanif';
	
	/**
	 * Number of works without the creation date.
	 * @datatype boolean
	 * @default checked
	 * @optional
	 */
	static $PARAM_X_WORKSNODATE = '-worksnodate';
	
	/**
	 * Number of works that have at least one manifestation.
	 * @datatype boolean
	 * @default checked
	 * @optional
	 */
	static $PARAM_X_WORKSWITHMANIF = '-workswithmanif';
	
	/**
	 * JSON format.
	 */
	public function execute()
	{
		$archiveUri = $this->getParam(self::$PARAM_URI, self::REQUIRED);
		
		$rpcclient = $this->getRpcClient();
		
		$query = array('
			PREFIX gama: <http://gama-gateway.eu/schema/>
			
			AGGREGATE archive_uri => ?x
			SELECT ?x {
				?x a gama:Archive
				FILTER (?x = '.GAMA_Utils::escapeSparqlUri($archiveUri).')
			}
			
			AGGREGATE archive_name => ?x + ,
			SELECT ?x {'.GAMA_Utils::escapeSparqlUri($archiveUri).' gama:archive_name ?x}
			');
		
		// ----------------------------------------
		if($this->getParam(self::$PARAM_X_WORKS, self::OPTIONAL))
		{
			$query[] = '
			AGGREGATE works => ?x
			select count(?work) as ?x {
			  ?work gama:provided_by '.GAMA_Utils::escapeSparqlUri($archiveUri).'
			}##
			';
		}
				
		// ----------------------------------------
		if($this->getParam(self::$PARAM_X_WORKSNOTITLE, self::OPTIONAL))
		{
			$query[] = '
			AGGREGATE worksnotitle => ?num
			SELECT count_distinct(?work) as ?num {
			  ?work gama:provided_by '.GAMA_Utils::escapeSparqlUri($archiveUri).'.
			  OPTIONAL { ?work gama:work_title ?title }
			  FILTER gama:isEmpty(?title)
			}##
			';
		}
		
		// ----------------------------------------
		if($this->getParam(self::$PARAM_X_WORKSNODESCR, self::OPTIONAL))
		{
			$query[] = '
			AGGREGATE worksnodescr => ?num
			SELECT count_distinct(?work) as ?num {
			  ?work gama:provided_by '.GAMA_Utils::escapeSparqlUri($archiveUri).'.
			  OPTIONAL { ?work gama:work_description ?descr }
			  FILTER gama:isEmpty(?descr)
			}##
			';
		}
		
		// ----------------------------------------
		if($this->getParam(self::$PARAM_X_WORKSNOCREATOR, self::OPTIONAL))
		{
			$query[] = '
			AGGREGATE worksnocreator => ?num
			SELECT count_distinct(?work) as ?num {
			  ?work gama:provided_by '.GAMA_Utils::escapeSparqlUri($archiveUri).'.
			  OPTIONAL { ?work gama:has_creator ?c }
			  FILTER gama:isEmpty(?c)
			}##
			';
		}
		
		// ----------------------------------------
		if($this->getParam(self::$PARAM_X_WORKSNOMAINMANIF, self::OPTIONAL))
		{
			$query[] = '
			AGGREGATE worksnomainmanif => ?num
			SELECT count_distinct(?work) as ?num {
			  ?work gama:provided_by '.GAMA_Utils::escapeSparqlUri($archiveUri).'.
			  OPTIONAL {
			    ?work gama:has_manifestation ?manif.
			    ?manif gama:idx_main "1".
			  }
			  FILTER gama:isEmpty(?manif)
			}##
			';
		}
		
		// ----------------------------------------
		if($this->getParam(self::$PARAM_X_WORKSNODATE, self::OPTIONAL))
		{
			$query[] = '
			AGGREGATE worksnodate => ?num
			SELECT count_distinct(?work) as ?num {
			  ?work gama:provided_by '.GAMA_Utils::escapeSparqlUri($archiveUri).'.
			  OPTIONAL { ?work gama:work_created ?c }
			  FILTER gama:isEmpty(?c)
			}##
			';
		}
		
		// ----------------------------------------
		if($this->getParam(self::$PARAM_X_WORKSWITHMANIF, self::OPTIONAL))
		{
			$query[] = '
			AGGREGATE workswithmanif => ?num
			SELECT count_distinct(?work) as ?num {
			  ?work gama:provided_by '.GAMA_Utils::escapeSparqlUri($archiveUri).'.
			  ?work gama:has_manifestation ?manif.
			}##
			';
		}
		
		// modify all queries 
		if($this->getParam(self::$PARAM_X_SPLITWORKTYPE, self::OPTIONAL))
		{
			$query = preg_replace(
				array(
					'/(AGGREGATE.*?work.*?=>)/i',
					'/(SELECT.*?)(count.*?\?work)/i',
					'/}##/',
				),
				array(
					'$1 ?work_type =>',
					'$1?work_type $2',
					'OPTIONAL{?work gama:work_type ?work_type} } group by ?work_type',
				),
				implode("\n", $query) );
		} else
		{
			$query = preg_replace('/##/', '', implode("\n", $query));
		}
		
		@header('Content-type: text/plain');
		echo $this->getRpcClient()->{'query/Aggregated_Sparql'}( $query );

		//echo $query."\n";
	}
}

?>