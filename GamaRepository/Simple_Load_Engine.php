<?php

/**
 * @author Viliam Simko
 */
class Simple_Load_Engine
{
	/**
	 * @var SPARQL_Result_Handler_Interface
	 */
	private $resultHandler;
	
	/**
	 * Parses the query string in "simple load" format.
	 * @param $queryString
	 */
	public function useQueryString($queryString)
	{
		// write every SPARQL query to the debug log
		if(Config::get('sparql.logusedsparql'))
		{
			debug("SPARQL : ".str_repeat('-',80)."\n".preg_replace('/^\s+/m', '', $queryString));
		}
		
		// remove comments
		$queryString = preg_replace('/^\s*#.*/m', '', $queryString);
		
		// cleanup URIs
		$queryString = preg_replace('/[^a-zA-Z0-9\s\.\_\-\:\/\*#]/','', $queryString);
				
		if(preg_match('/^\s*SIMPLE\s+LOAD\s*\n(.*)\n\s*PROPERTIES\s*\n(.*)/is', $queryString, $match))
		{
			$arrayOfResources = self::prepareUris($match[1]);
			$arrayOfProperties = self::prepareUris($match[2]);
			$this->onSetResources($arrayOfResources);
			$this->onSetProperties($arrayOfProperties);
		} else
		{
			throw new Exception('Error parsing the query string');
		}
	}
	
	/**
	 * 
	 */
	public function runQuery()
	{
		$outputVariables = array('graph', 'subject', 'predicate', 'object');
		$debugMessage = array(
			"using database:'".GAMA_Store::getDatabaseName()."' timestamp:".date('Y-m-d h:i:s'),
			"using simple load query",
			);
		 
		$this->resultHandler->onBeginResults($this, $outputVariables, $debugMessage);
				
		if( !empty($this->resourceFilter) && !empty($this->mapPropertyUriToId))
		{
			// ==================================================================
			// execute a single query for each property
			// ==================================================================
			foreach($this->mapPropertyUriToId as $propertyUri => $propertyId)
			{
				$property = Resource_Manager::singleton()->getResourceByUri($propertyUri);
				if($propertyUri == GAMA_Store::RDF_TYPE_URI)
				{
					$sql = "
					select
					  NULL as graph,
					  r.uri as subject,
					  t.uri as object
					from
					  RESOURCE as r
					  join RESOURCE as t on t.id = r.type
					where
					  r.uri in ($this->resourceFilter)";
				
				} elseif($property->isPropType(GAMA_Store::TYPE_DATATYPE_PROPERTY))
				{
					// explicit column
					if(isset($this->mapExplicitColumnNames[$propertyUri]))
					{
						$object_column = 'p.'.$this->mapExplicitColumnNames[$propertyUri];
					} else
					{
						$object_column = $property->stmtValue('p');
					}
					
					$sql = "
					select
						g.uri as graph,
						s.uri as subject,
						$object_column as object,
						{$property->stmtLang('p')} as object_lang,
						1 as object_dt
					from
						S_{$propertyId} as p
						join GRAPH as g on g.id = p.g
						join RESOURCE as s on s.id = p.subject
					where
						s.uri in ($this->resourceFilter)
					";
				} elseif($property->isPropType(GAMA_Store::TYPE_EQUIVALENCE_PROPERTY) )
				{
					$sql = "
					select
					  g.uri as graph,
					  s.uri as subject,
					  o.uri as object
					from
					  S_{$propertyId} as p
					  join S_{$propertyId} as eqp on p.object = eqp.object
					  join GRAPH as g on g.id = eqp.g
					  join RESOURCE as s on s.id = p.subject
					  join RESOURCE as o on o.id = eqp.subject
					where
					  s.uri in ($this->resourceFilter)";
					
				} elseif($property->isPropType(GAMA_Store::TYPE_SYMMETRIC_PROPERTY) )
				{
					$sql = "
					select
					  g.uri as graph,
					  s.uri as subject,
					  o.uri as object
					from
					  S_{$propertyId} as p
					  join GRAPH as g on g.id = p.g
					  join RESOURCE as s on s.id = p.subject
					  join RESOURCE as o on o.id = p.object
					where
					  s.uri in ($this->resourceFilter)
					  or o.uri in ($this->resourceFilter)";
					
				} elseif($property->isPropType(GAMA_Store::TYPE_TRANSITIVE_PROPERTY) ||
						$property->isPropType(GAMA_Store::TYPE_OBJECT_PROPERTY) )
				{
					if($property->isHavingInverse() && !$property->isThisInverseMaster())
					{
						$sql = "
						select
						  g.uri as graph,
						  s.uri as object,
						  o.uri as subject
						from
						  S_{$property->getInverseMaster()->getID()} as p
						  join GRAPH as g on g.id = p.g
						  join RESOURCE as s on s.id = p.subject
						  join RESOURCE as o on o.id = p.object
						where
						  o.uri in ($this->resourceFilter)";
					} else
					{
						$sql = "
						select
						  g.uri as graph,
						  s.uri as subject,
						  o.uri as object
						from
						  S_{$propertyId} as p
						  join GRAPH as g on g.id = p.g
						  join RESOURCE as s on s.id = p.subject
						  join RESOURCE as o on o.id = p.object
						where
						  s.uri in ($this->resourceFilter)";
					}
				} else
				{
					throw new Exception('Unsupported property type in: '.$propertyUri);
				}
								
				// report the beginning of a new property
				$this->resultHandler->onComment("PROPERTY: $propertyUri");
				
				$stmt = GAMA_Store::singleton()->sql($sql, $propertyUri);
				
				while($row = $stmt->fetch(PDO::FETCH_ASSOC))
				{
					$row['predicate'] = $propertyUri;
					$this->resultHandler->onFoundResult($this, $row);
				}
			}
		}
		
		$this->resultHandler->onEndResults($this);
	}
	
	/**
	 * @param $object
	 */
	public function setResultHandler(SPARQL_Result_Handler_Interface $object)
	{
		$this->resultHandler = $object;
	}

	/**
	 * Create the URI->ID mapping for given resources.
	 * @param array $resourceUris
	 */
	public function onSetResources(array $resourceUris)
	{
		$this->resourceFilter = GAMA_Utils::encodeArrayToSql($resourceUris);
		
		if(empty($this->resourceFilter))
		{
			throw new Exception('No resources to process');
		}
		
		$found = GAMA_Store::singleton()->sqlFetchColumn('uri', "select uri from RESOURCE where uri in ($this->resourceFilter)");
		$missing = array_diff($resourceUris, $found);

		if(count($missing))
		{
			throw new Exception('The following resources were not found in the repository: '. implode(", ", $missing) );
		}
	}
	
	/**
	 * Create the URI->ID mapping for given properties.
	 * @param array $propertyUris
	 */
	function onSetProperties(array $propertyUris)
	{
		if($propertyUris[0] == '*')
		{
			$stmt = GAMA_Store::singleton()->sql('
				select
					propid as id,
					uri as uri
				from PROPERTY
				order by datatype desc
			');
			
			$this->mapPropertyUriToId = array();
			
			// also include the rdf:type
			$this->mapPropertyUriToId[GAMA_Store::RDF_TYPE_URI] = 0;
			
			while($row = $stmt->fetch(PDO::FETCH_ASSOC))
			{
				$this->mapPropertyUriToId[ $row['uri'] ] = $row['id']; 
			}
		} else
		{
			$this->mapPropertyUriToId = $this->getUriToIdMapping($propertyUris);
			//debug($this->mapExplicitColumnNames);
		}
	}
		
	/**
	 * @param string $queryString
	 * @return boolean
	 */
	static public function isValidSimpleLoadQueryString($queryString)
	{
		$queryString = preg_replace('/^\s*#.*/m', '', $queryString);
		return preg_match('/^\s*SIMPLE\s+LOAD\s*\n/i', $queryString);
	}

	// =========================================================================
	// Other stuff
	// =========================================================================
		
	/**
	 * @var Namespace_Manager
	 */
	private $nsmgr;
	
	/**
	 */
	function __construct()
	{
		$this->nsmgr = new Namespace_Manager;
		
		// some namespaces added for the convienience
		$this->nsmgr->onNewNamespace('gama', 'http://gama-gateway.eu/schema/');
		$this->nsmgr->onNewNamespace('cache', 'http://gama-gateway.eu/cache/');
		$this->nsmgr->onNewNamespace('rdf',  'http://www.w3.org/1999/02/22-rdf-syntax-ns#');
		$this->nsmgr->onNewNamespace('rdfs', 'http://www.w3.org/2000/01/rdf-schema#');
		$this->nsmgr->onNewNamespace('owl',  'http://www.w3.org/2002/07/owl#');
		$this->nsmgr->onNewNamespace('xsd',  'http://www.w3.org/2001/XMLSchema#');
		$this->nsmgr->onNewNamespace('', ''); // default namespace
	}
	
	/**
	 * Retreive list of IDs for given URIs and create URI->ID mapping.
	 * @param array $uris
	 * @return array
	 */
	private function getUriToIdMapping(array $uris)
	{
		$result = array();
		foreach($uris as $uri)
		{
			// remove everything after first whitespace
			$resolvedUri = preg_replace('/\s.*/', '', $uri);
			
			// resolve the prefix to get the full URI
			$resolvedUri = $this->nsmgr->resolveUri($resolvedUri);
			
			$result[] = $resolvedUri;
			
			// optionally store the explicit column name mapping
			if(preg_match('/\s+column\s+([^\s]+)/i', $uri, $match))
			{
				$this->mapExplicitColumnNames[$resolvedUri] = $match[1];
			}
		}
		
		$sqlvars = '?'.str_repeat(',?', count($result) - 1);
		$stmt = GAMA_Store::singleton()->sql(
			'select id, uri from RESOURCE where uri in ('.$sqlvars.')', $result );
		
		$result = array();
		while($row = $stmt->fetch(PDO::FETCH_ASSOC))
		{
			$result[ $row['uri'] ] = $row['id']; 
		}
		return $result;
	}
	
	/**
	 * Concatenated list of URIs to be included directly into SQL commands.
	 * Example: 'uri1','uri2';
	 * @var string
	 */
	private $resourceFilter;
	
	/**
	 * URI->ID mapping.
	 * @var array
	 */
	private $mapPropertyUriToId = array();
	
	/**
	 * Maps property URI to column name explicitly.
	 * @var array
	 */
	private $mapExplicitColumnNames = array();

	/**
	 * Prepare list of URIs.
	 * @param array|string $uris
	 * @return array
	 */
	static public function prepareUris($uris)
	{
		if(! is_array($uris))
		{
			$uris = explode("\n", $uris);
		}
	
		$result = array();
		foreach($uris as $uri)
		{
			$uri = trim($uri);
			if(! empty($uri))
			{
				$result[] = $uri;
			}
		}
		return $result;
	}

}
?>