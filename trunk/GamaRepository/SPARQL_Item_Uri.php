<?php
/**
 * Represents the URI.
 * @author Viliam Simko
 */
class SPARQL_Item_Uri extends SPARQL_Item
{	
	/**
	 * (non-PHPdoc)
	 * @see SPARQL_Item#getBindValue()
	 */
	public function getBindValue()
	{
		return $this->engine->uri2id( $this->getRawValue() );
	}
	
	/**
	 * (non-PHPdoc)
	 * @see SPARQL_Item#getOutValue()
	 */
	public function getOutValue()
	{
		return '"'.mysql_escape_string( $this->getRawValue() ).'"';
	}
	
	/**
	 * (non-PHPdoc)
	 * @see SPARQL_Item#getRawValue()
	 */
	public function getRawValue()
	{
		return $this->p['uri'];
	}

	/**
	 * (non-PHPdoc)
	 * @see SPARQL_Item#getLangValue()
	 */
	public function getLangValue()
	{
		throw new SPARQL_Engine_Exception('Language binding not supported for URIs');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see SPARQL_Item#getParsedDatatype()
	 */
	public function getDatatypeBinding($dtUri)
	{
		assert('/* Datatypes not supported for URIs */');
	}

	/**
	 * @return string
	 */
	public function getSpecialBinding($columnName)
	{
		assert('/* Special binding not supported for URIs */');
	}	
}
?>