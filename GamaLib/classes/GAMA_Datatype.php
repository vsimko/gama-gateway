<?php

/**
 * Thrown in case of wrong datatype value.
 */
class GAMA_Datatype_Value_Exception extends Exception {}

/**
 * Datatypes in GAMA define how the values will be handled by the repository
 * Not only this class handles the datatypes of owl:DatatypeProperty
 * it also deals with other types of properties as well.
 *  
 * A datatype should provide following information:
 * - the datatype URI
 * - how to create columns in the underlying database
 * - how to insert a value into the database  
 * - how to construct results
 * - how to render values
 * 
 * Datatypes are organised in a hierarchy reflected in the class hierarchy
 * starting from this abstract class. They are stored together in a directory.
 * Every datatype is also described in the RDF repository as a rdfs class.
 * 
 * @code
 * select * { ?s a rdfs:Datatype }
 * @endcode
 * 
 * @see http://www.w3.org/TR/xmlschema-2/#built-in-datatypes
 * @see http://dev.mysql.com/doc/refman/5.0/en/data-types.html
 * @see http://books.xmlschemata.org/relaxng/relax-CHP-19.html
 */
abstract class GAMA_Datatype
{
	/**
	 * Converts datatype URI to the classname.
	 *
	 * @param string $uri
	 * @return string
	 */
	final static private function getClassnameByUri($uri)
	{
		return preg_replace(
			array(	'/^http\:\/\/gama-gateway\.eu\/schema\//',
					'/^http\:\/\/www\.w3\.org\/2001\/XMLSchema\#/' ),
			array(	'DT_gama_',
					'DT_xsd_' ),
			$uri);
	}
	
	/**
	 * Checks if the datatype is supported by the repository.
	 *
	 * @param string $uri
	 * @return boolean
	 */
	final static public function isSupportedDatatype($uri)
	{
		$classname = self::getClassnameByUri($uri);
		return @include_once $classname.'.php';
	}
	
	/**
	 * This factory method returns a datatype instance.
	 * Subsequent calls use a simple caching mechanism so that a reference
	 * to the same instance is returned.
	 * 
	 * @param string $uri
	 * @return GAMA_Datatype
	 */
	final static public function getDatatypeByUri($uri)
	{
		static $dtcache = array();

		$instance = @ $dtcache[$uri];
		
		if(empty($instance))
		{
			$classname = self::getClassnameByUri($uri);
			
			// use add_include_path() function to point to the directory
			// containing datatypes. Usually, this is plugins/datatypes
			$instance = new $classname;
			
			assert('/* datatype should provide correct URI */ $instance->getUri() == $uri');
		}
		
		return $instance;
	}
		
	// =========================================================================	
	
	/**
	 * Provides the datatype URI.
	 * Example: return 'http://www.w3.org/2001/XMLSchema#string';
	 * 
	 * @return string URI of the datatype
	 */
	abstract static public function getUri();

	/**
	 * Describes the MySQL datatype of all columns used by the datatype.
	 * In other words, the function defines the mapping from XMLSchema to MySQL.
	 * 
	 * @return array Associative array of columnName => SQL column definition
	 */
	abstract public function getColumnDefinition();
	
	/**
	 * Definition of the sorting index
	 * Example: return 'data(20)';
	 * 
	 * @return string
	 */
	abstract public function getSortingIndexDefinition();
	
	/**
	 * Additional indexes such as special fulltext index.
	 * Example: return array('fulltext index (data)');
	 * 
	 * @return array List of SQL snippets defining the indexes
	 */
	public function getIndexDefinition()
	{
		// no additional indexes
		return array();
	}
	
	/**
	 * Describes the SQL which is used as a consturctor of the value in the
	 * query result list. Usually, the constructor takes directly the "object"
	 * column. Other possible mechanisms may, however, be involved, such as
	 * other MySQL functions.
	 * Example: return "$tabalias.object";
	 * 
	 * @param string $tabalias
	 * @return string SQL snippet
	 */
	abstract public function getValueDefinition($tabalias);

	/**
	 * Constructs the SQL snippet used in the ORDER BY calusule.
	 * Example: return "$tabalias.sort";
	 *
	 * @param string $tabalias
	 * @return string SQL snippet
	 */
	abstract public function getSortingValueDefinition($tabalias);
	
	/**
	 * Constructs the SQL snippet used in query JOIN operations.
	 * Example: return "$tabalias.object";
	 *
	 * @param string $tabalias
	 * @return string SQL snippet
	 */
	abstract public function getJoinDefinition($tabalias);

	/**
	 * Constructs the SQL snippet used in query JOIN operations.
	 * If an empty string is returned, the join operation is omitted.
	 * Example: return "$tabalias.lang"
	 * @param string $tabalias
	 * @return string SQL snippet
	 */
	public function getLangJoinDefinition($tabalias)
	{
		return "''";
	}
	
	/**
	 * Parses a value of a given rdfs:Literal.
	 * The parsing mechanism depends on the particular datatype class.
	 *  
	 * @param RDFS_Literal $literal
	 * @return array Associative array of columns with corresponding values
	 */
	abstract public function getParsedLiteral(RDFS_Literal $literal);
}

?>