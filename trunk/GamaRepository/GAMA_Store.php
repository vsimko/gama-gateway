<?php
/**
 * Singleton design pattern.
 * See http://cz.php.net/manual/en/language.oop5.patterns.php
 * 
 * - URIs limited to latin1, 512 bytes
 * - number of graphs limited to 65536
 */
class GAMA_Store
{
	// well-known namespaces
	const RDF_NAMESPACE_URI		= 'http://www.w3.org/1999/02/22-rdf-syntax-ns#';
	const RDFS_NAMESPACE_URI	= 'http://www.w3.org/2000/01/rdf-schema#';
	const OWL_NAMESPACE_URI 	= 'http://www.w3.org/2002/07/owl#';
	const XSD_NAMESPACE_URI 	= 'http://www.w3.org/2001/XMLSchema#';

	// well-known URIs
	const RDF_TYPE_URI			= 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type';
	const RDF_PROPERTY_URI		= 'http://www.w3.org/1999/02/22-rdf-syntax-ns#Property';

	const RDFS_SUBCLASSOF_URI	= 'http://www.w3.org/2000/01/rdf-schema#subClassOf';
	const RDFS_COMMENT_URI		= 'http://www.w3.org/2000/01/rdf-schema#comment';
	const RDFS_LABEL_URI		= 'http://www.w3.org/2000/01/rdf-schema#label';
	const RDFS_RESOURCE_URI		= 'http://www.w3.org/2000/01/rdf-schema#Resource';
	const RDFS_CLASS_URI		= 'http://www.w3.org/2000/01/rdf-schema#Class';
	const RDFS_DATATYPE_URI		= 'http://www.w3.org/2000/01/rdf-schema#Datatype';
	const RDFS_RANGE_URI		= 'http://www.w3.org/2000/01/rdf-schema#range';
	const RDFS_DOMAIN_URI		= 'http://www.w3.org/2000/01/rdf-schema#domain';

	const OWL_CLASS_URI					= 'http://www.w3.org/2002/07/owl#Class';
	const OWL_ANNOTATION_PROPERTY_URI	= 'http://www.w3.org/2002/07/owl#AnnotationProperty';
	const OWL_DATATYPE_PROPERTY_URI		= 'http://www.w3.org/2002/07/owl#DatatypeProperty';
	const OWL_OBJECT_PROPERTY_URI		= 'http://www.w3.org/2002/07/owl#ObjectProperty';
	const OWL_SYMMETRIC_PROPERTY_URI	= 'http://www.w3.org/2002/07/owl#SymmetricProperty';
	const OWL_TRANSITIVE_PROPERTY_URI	= 'http://www.w3.org/2002/07/owl#TransitiveProperty';
	const OWL_INVERSE_FUNCTIONAL_PROPERTY_URI	= 'http://www.w3.org/2002/07/owl#InverseFunctionalProperty';
	const OWL_INVERSEOF_URI				= 'http://www.w3.org/2002/07/owl#inverseOf';
	const OWL_VERSIONINFO_URI			= 'http://www.w3.org/2002/07/owl#versionInfo';
	const OWL_SAME_AS_URI				= 'http://www.w3.org/2002/07/owl#sameAs';
	
	const GAMA_EQUIVALENCE_PROPERTY_URI	= 'http://gama-gateway.eu/schema/EquivalenceProperty';
	
	// -------------------------------------------------------------------------
	
	const MAX_URI_LENGTH = 521;
	
	/** Default graph must always be 0 */
	const DEFAULT_GRAPH_ID = 0;
	
	const TYPE_PROPERTY  			= 'rdf:Property';
	const TYPE_OBJECT_PROPERTY		= 'owl:ObjectProperty';
	const TYPE_DATATYPE_PROPERTY	= 'owl:DatatypeProperty';
	const TYPE_SYMMETRIC_PROPERTY	= 'owl:SymmetricProperty';
	const TYPE_TRANSITIVE_PROPERTY	= 'owl:TransitiveProperty';
	const TYPE_EQUIVALENCE_PROPERTY	= 'gama:EquivalenceProperty';
	
	/**
	 * Database connection.
	 * @var PDO
	 */
	private $db;
	
	/**
	 * Singleton instance.
	 * @var GAMA_Store
	 */
	static private $instance = null;

	/**
	 * Constructor establishes connection to database.
	 * Using the configured username and password.
	 */
	protected function __construct()
	{
		$this->db = new PDO(
			'mysql:host='.Config::get('repo.dbhost').';dbname='.Config::get('repo.dbname'),
			Config::get('repo.dbuser'),
			Config::get('repo.dbpass'),
			array(
				// http://cz2.php.net/manual/en/ref.pdo-mysql.php
				PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false,
			
				// Perform direct queries, don't use prepared statements. 
				PDO::MYSQL_ATTR_DIRECT_QUERY => true,
				
				// Enable LOAD LOCAL INFILE. 
				PDO::MYSQL_ATTR_LOCAL_INFILE => true,
				
				// Throw exceptions instead of PHP errors
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
				
				// Force mysql PDO driver to use UTF-8 for the connection.
				// Will automatically be re-executed when reconnecting. 
				PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES UTF8',
			));
	}
	
	/**
	 * Which database is used by the repository.
	 * @return string
	 */
	function getDatabaseName()
	{
		return Config::get('repo.dbname');
	}
	
	/**
	 * Forbidden in singleton design pattern.
	 */
	private function __clone(){}

	/**
	 * Implementation of singleton design pattern in PHP5.
	 *
	 * @return GAMA_Store
	 */
	static public function singleton()
	{
		if(!self::$instance)
		{
			$classname = __CLASS__;
			self::$instance = new $classname();
		}

		return self::$instance;
	}

	/**
	 * What should be the textual representation of the repository.
	 */
	public function __toString()
	{
		return 'singleton '.__CLASS__;
	}
	
	/**
	 * Destructor
	 */
	public function __destruct()
	{
		//debug(__METHOD__);
	}
	
	//===============================================================
	//
	// Public methods
	//
	//===============================================================
	
	/**
	 * Rebuilds database tables of the repostiory
	 * and creates some important axioms.
	 */
	public function rebuildStore()
	{
		// ===============
		app_lock(LOCK_EX);
		// ===============
		
		$this->dropTablesByPattern('S_%');

		//----------------------------------------------------------------------
		$this->sql('drop table if exists SIMILATIRY');
		$this->sql('
			create table SIMILARITY
			(
				manif int(10) unsigned NOT NULL,
				smanif int(10) unsigned NOT NULL,
				shotid int(10) unsigned NOT NULL default 0,
				weight tinyint(3) unsigned NOT NULL default 100,
				bestmatch tinyint(3) unsigned NOT NULL default 0,
				
				PRIMARY KEY (manif, smanif, shotid)
			) engine=MyISAM CHARACTER SET ascii
		');
		
		//----------------------------------------------------------------------
		$this->sql('drop table if exists GRAPH');
		$this->sql('
			create table GRAPH
			(
				-- primary key
				id smallint unsigned not null auto_increment primary key,
				
				-- URI of resource
				uri varchar('.self::MAX_URI_LENGTH.') not null,
				unique key (uri)
				
			) engine=MyISAM CHARACTER SET ascii
		');
		
		// default graph definition
		$this->sql('insert into GRAPH (uri) values (?)', 'default');
		$this->sql('update GRAPH set id='.self::DEFAULT_GRAPH_ID.' where id=LAST_INSERT_ID()');
		
		//----------------------------------------------------------------------
		$this->sql('drop table if exists RESOURCE');
		$this->sql('
			create table RESOURCE
			(
				-- URI of resource
				uri varchar('.self::MAX_URI_LENGTH.') not null primary key,
				
				-- local identifier of a group of equivalent resources
				id integer unsigned not null,
				index(id),
				
				-- rdf_type will be moved later to a separate table
				type integer unsigned null default null,
				index(type)
				
			) engine=MyISAM CHARACTER SET ascii
		');
			
		//----------------------------------------------------------------------
		$this->sql('drop table if exists PROPERTY');
		$this->sql('
			create table PROPERTY
			(
				-- primary key
				propid integer unsigned not null auto_increment primary key,
			
				-- URI of resource
				uri varchar('.self::MAX_URI_LENGTH.') not null,
				unique (uri),
							
				inverse integer unsigned not null default 0,
				index(inverse),
	
				-- property domain
				dom integer unsigned not null,
				index (dom),
	
				-- property range
				rng integer unsigned not null,
				index (rng),
			
				-- rdf_type
				proptype enum(
					"'.self::TYPE_PROPERTY.'",
					"'.self::TYPE_OBJECT_PROPERTY.'",
					"'.self::TYPE_DATATYPE_PROPERTY.'",
					"'.self::TYPE_SYMMETRIC_PROPERTY.'",
					"'.self::TYPE_TRANSITIVE_PROPERTY.'",
					"'.self::TYPE_EQUIVALENCE_PROPERTY.'"
					) null default null,
				index (proptype),
				
				-- datatype in case of owl_DatatypeProperty
				datatype varchar('.self::MAX_URI_LENGTH.') null default null,
				index (datatype)
				
			) engine=MyISAM CHARACTER SET ascii
		');

		$axioms = array(
		
			'http://www.w3.org/2000/01/rdf-schema#Class' => array(
		
				'http://www.w3.org/2000/01/rdf-schema#Resource',
				'http://www.w3.org/2000/01/rdf-schema#Class',
				'http://www.w3.org/2000/01/rdf-schema#Datatype',
				'http://www.w3.org/1999/02/22-rdf-syntax-ns#Property',
		
				'http://www.w3.org/2002/07/owl#Class',
				'http://www.w3.org/1999/02/22-rdf-syntax-ns#Statement',
				'http://www.w3.org/2002/07/owl#AnnotationProperty',
				'http://www.w3.org/2002/07/owl#DatatypeProperty',
				'http://www.w3.org/2002/07/owl#ObjectProperty',
				'http://www.w3.org/2002/07/owl#SymmetricProperty',
				'http://www.w3.org/2002/07/owl#TransitiveProperty',
				'http://gama-gateway.eu/schema/EquivalenceProperty',
				'http://www.w3.org/2002/07/owl#InverseFunctionalProperty',	
				),
				
			'http://www.w3.org/2002/07/owl#Class' => array(
				'http://www.w3.org/2002/07/owl#Ontology',
				),
				
			'http://www.w3.org/1999/02/22-rdf-syntax-ns#Property' => array(
				
				'http://www.w3.org/1999/02/22-rdf-syntax-ns#type',
				'http://www.w3.org/2000/01/rdf-schema#domain',
				'http://www.w3.org/2000/01/rdf-schema#range',
				'http://www.w3.org/2002/07/owl#inverseOf',
				'http://www.w3.org/2002/07/owl#versionInfo',
				'http://www.w3.org/2002/07/owl#imports',
				'http://www.w3.org/2002/07/owl#sameAs',
				
				'http://www.w3.org/2000/01/rdf-schema#label',
				'http://www.w3.org/2000/01/rdf-schema#comment',
				'http://www.w3.org/2000/01/rdf-schema#subClassOf',
				'http://www.w3.org/1999/02/22-rdf-syntax-ns#subject',
				'http://www.w3.org/1999/02/22-rdf-syntax-ns#predicate',
				'http://www.w3.org/1999/02/22-rdf-syntax-ns#object',
				),
		);
		
		// insert only axiom URIs store uri-id mapping, the types will be filled after
		$mapUriToId = array();
		foreach($axioms as $axiomGroup)
		{
			foreach($axiomGroup as $uri)
			{
				$id = $this->getNewResourceID();
				$this->sql('insert into RESOURCE (id, uri) values (?, ?)', $id, $uri);
				$mapUriToId[$uri] = $id;
			}
		}
		
		// now fill the types
		foreach($axioms as $typeUri => $axiomGroup)
		{
			foreach($axiomGroup as $uri)
			{
				$typeId = $mapUriToId[$typeUri];
				$this->sql('update RESOURCE set type=? where uri=?', $typeId, $uri);
			}
		}
		
		// =====================================================================
		// Include all supported datatype (GAMA-specific + XMLSchema)
		// All XMLSchema built-in simple datatypes are defined here:
		//   http://www.w3.org/TR/xmlschema-2/#built-in-datatypes
		// =====================================================================
		$xsdDatatypeId = $mapUriToId['http://www.w3.org/2000/01/rdf-schema#Datatype'];
		foreach(GAMA_Datatype::getAllDatatypeUris() as $datatypeUri)
		{
			$this->sql('
				insert into RESOURCE (id, type, uri)
				select max(id)+1, ?, ? from RESOURCE
			', $xsdDatatypeId, $datatypeUri);
		}

		// =====================================================================
		// add some axiom properties
		// =====================================================================

		$this->setGraph('system');
		$resmgr = Resource_Manager::singleton();
		
		// rdfs:label
		$property = $resmgr->getPreparedResourceByUri(self::RDFS_LABEL_URI, 'RDF_Property');
		$property->setDomain( $resmgr->getResourceByUri(self::RDFS_RESOURCE_URI) );
		$property->setPropertyType( self::TYPE_DATATYPE_PROPERTY, DT_xsd_normalizedString::getUri() );
		
		// rdfs:comment
		$property = $resmgr->getPreparedResourceByUri(self::RDFS_COMMENT_URI, 'RDF_Property');
		$property->setDomain( $resmgr->getResourceByUri(self::RDFS_RESOURCE_URI) );
		$property->setPropertyType( self::TYPE_DATATYPE_PROPERTY, DT_xsd_string::getUri() );

		$property->addStatement(self::RDFS_LABEL_URI,
			"rdfs:label is an instance of rdf:Property that may be used to provide a human-readable version of a resource's name." );
		
		// add comments to some resources
		$property->addStatement(self::RDFS_LABEL_URI,
			"rdfs:label is an instance of rdf:Property that may be used to provide a human-readable version of a resource's name." );
		
		$property->addStatement(self::RDFS_COMMENT_URI,
			"rdfs:comment is an instance of rdf:Property that may be used to provide a human-readable description of a resource." );
		
		// owl:versionInfo
		$property = $resmgr->getPreparedResourceByUri(self::OWL_VERSIONINFO_URI, 'RDF_Property');
		$property->setDomain( $resmgr->getResourceByUri(self::RDFS_RESOURCE_URI) );
		//TODO: owl:verionInfo should be an instance of owl:AnnotationProperty
		$property->setPropertyType( self::TYPE_DATATYPE_PROPERTY, DT_xsd_string::getUri() );
		
		// rdfs:subClassOf
		$property = $resmgr->getPreparedResourceByUri(self::RDFS_SUBCLASSOF_URI, 'RDF_Property');
		$property->setDomain( $resmgr->getResourceByUri(self::RDFS_CLASS_URI) );
		$property->setRange( $resmgr->getResourceByUri(self::RDFS_CLASS_URI) );
		$property->setPropertyType( self::TYPE_TRANSITIVE_PROPERTY );
		
		// owl:inverseOf
		$property = $resmgr->getPreparedResourceByUri(self::OWL_INVERSEOF_URI, 'RDF_Property');
		$property->setDomain( $resmgr->getResourceByUri(self::OWL_OBJECT_PROPERTY_URI) );
		$property->setRange( $resmgr->getResourceByUri(self::OWL_OBJECT_PROPERTY_URI) );
		$property->setPropertyType( self::TYPE_SYMMETRIC_PROPERTY );
		
		// owl:sameAs
		$property = $resmgr->getPreparedResourceByUri('http://www.w3.org/2002/07/owl#sameAs', 'RDF_Property');
		$property->setDomain( $resmgr->getResourceByUri(self::RDFS_RESOURCE_URI) ); // should be 'http://www.w3.org/2002/07/owl#Thing'
		$property->setRange( $resmgr->getResourceByUri(self::RDFS_RESOURCE_URI) ); //should be 'http://www.w3.org/2002/07/owl#Thing'
		$property->setPropertyType( self::TYPE_EQUIVALENCE_PROPERTY );

		// owl:imports
		$property = $resmgr->getPreparedResourceByUri('http://www.w3.org/2002/07/owl#imports', 'RDF_Property');
		$property->setDomain( $resmgr->getResourceByUri('http://www.w3.org/2002/07/owl#Ontology') );
		$property->setRange( $resmgr->getResourceByUri('http://www.w3.org/2002/07/owl#Ontology') );
		$property->setPropertyType( self::TYPE_TRANSITIVE_PROPERTY );
		
		// rdfs:domain
		$property = $resmgr->getPreparedResourceByUri(self::RDFS_DOMAIN_URI, 'RDF_Property');
		$property->setDomain( $resmgr->getResourceByUri(self::RDF_PROPERTY_URI) );
		$property->setRange( $resmgr->getResourceByUri(self::RDFS_CLASS_URI) );
		$property->setPropertyType( self::TYPE_OBJECT_PROPERTY );
				
		// rdfs:range
		$property = $resmgr->getPreparedResourceByUri(self::RDFS_RANGE_URI, 'RDF_Property');
		$property->setDomain( $resmgr->getResourceByUri(self::RDF_PROPERTY_URI) );
		$property->setRange( $resmgr->getResourceByUri(self::RDFS_CLASS_URI) );
		$property->setPropertyType( self::TYPE_OBJECT_PROPERTY );
				
		// =====================================================================
		// rebuild done
		// =====================================================================
		
		// ===============
		app_unlock();
		// ===============
		
		debug('The schema definition and all data deleted successfully');
	}
		
	/**
	 * @var integer
	 */
	private $graphID = 0;
	
	/**
	 * Return the current graph ID or resolve graph ID based on the
	 * optional uri parameter. 
	 * @param $uri
	 * @return int
	 */
	public function getGraphID($uri = null)
	{
		return empty($uri)
			? $this->graphID
			: $this->sqlFetchValue('select id from GRAPH where uri=?', $uri);
	}
		
	/**
	 * @param string $uri
	 */
	public function setGraph($uri)
	{
		$this->graphID = $this->getGraphID($uri);
		
		if($this->graphID === null)
		{
			// ===============
			app_lock(LOCK_EX);
			// ===============
			try
			{
				$this->sql('insert ignore into GRAPH (uri) values (?)', $uri);
				$this->graphID = $this->getGraphID($uri);
			} catch(Exception $e)
			{
				app_unlock();
				throw $e;
			}
			// ==========
			app_unlock();
			// ==========
		}
	}
	
	/**
	 * Generates ID not yet inserted in the RESOURCE table.
	 * @return integer
	 */
	public function getNewResourceID()
	{
		$maxID = $this->sqlFetchValue('select max(id) from RESOURCE');
		return $maxID + 1;
	}
	
	/**
	 * Example #1:
	 * @code
	 * $store = GAMA_Store::singleton();
	 * $stmt = $store->sql('select * from PROPERTY where dom = ? and rng = ?', 100, 200);
	 * $result = $stmt->fetchAll(PDO:FETCH_ASSOC);
	 * unset($stmt); // destructor closes the connection
	 * @endcode
	 * 
	 * Example #2:
	 * @code
	 * $store->sql('delete from PROPERTY'); // destructor closes the connection 
	 * @endcode
	 * 
	 * @param string $query
	 * @param mixed $params Also supports variable arguments
	 * @return PDOStatement
	 */
	public function sql($query, $params = array())
	{
		//debug_time_measure(__METHOD__);
		// remove the first parameter if variable arguments detected
		if(!is_array($params))
		{
			$params = func_get_args();
			array_shift($params);
		}
		
		$stmt = $this->db->prepare($query);
		$stmt->execute($params);
		return $stmt;
	}
	
	/**
	 * Fetches first cell in the result set (first column in the first row).
	 *
	 * @param string $query
	 * @param mixed $params Also supports variable arguments
	 * @return string
	 */
	public function sqlFetchValue($query, $params = array())
	{
		// remove the first parameter if variable arguments detected
		if(!is_array($params))
		{
			$params = func_get_args();
			array_shift($params);
		}
		
		$stmt = $this->db->prepare($query);
		$stmt->execute($params);
		$row = $stmt->fetch(PDO::FETCH_NUM);
		// the connection will be closed in the destructor of PDOStatement
		// because the $stmt is a local variable
		return $row[0];
	}
	
	/**
	 * @param string $columnName
	 * @param string $query
	 * @param mixed $params
	 * @return array
	 */
	public function sqlFetchColumn($columnName, $query, $params=array())
	{
		// remove the first two parameters if variable arguments detected
		if(!is_array($params))
		{
			$params = func_get_args();
			array_shift($params);
			array_shift($params);
		}
		
		// do the query
		$stmt = $this->db->prepare($query);
		$stmt->execute($params);
		
		// fetch all data from the column
		$resultData = array();
		while($row = $stmt->fetch(PDO::FETCH_ASSOC))
		{
			$resultData[] = $row[$columnName];
		}
		// the connection will be closed in the destructor of PDOStatement
		// because the $stmt is a local variable
		return $resultData;
	}
	
	/**
	 * Fill missing identities in the owl:sameAs property.
	 */
	public function fillOwlSameAsIdentities()
	{
		$resmgr = Resource_Manager::singleton();
		
		$owlSameAs = $resmgr->getResourceByUri(self::OWL_SAME_AS_URI);
		
		$this->sql("
			insert into {$owlSameAs->getStmtTab()} (g, subject, object)
			select
				0 as g,
				r.id,
				r.id
			from
				RESOURCE r
				left join {$owlSameAs->getStmtTab()} s on s.subject = r.id
			where
				s.object is null 
		");
	}	
	
		
	//===============================================================
	//
	// Private methods
	//
	//===============================================================

	/**
	 * Drops database tables with matching name.
	 * @param string $pattern
	 * @return int
	 */
	protected function dropTablesByPattern($pattern)
	{
		assert('/* pattern cannot be empty */ !empty($pattern)');
		
		$numDropped = 0;
		foreach($this->sql('show tables like ?', $pattern)->fetchAll(PDO::FETCH_NUM) as $row)
		{
			$this->sql("drop table $row[0]");
			debug("table {$row[0]} dropped");
			$numDropped++;
		}
		return $numDropped;
	}
}

?>
