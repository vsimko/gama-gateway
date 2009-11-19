<?php
/**
 * Represents the Literal value.
 * @author Viliam Simko
 */
class SPARQL_Item_Literal extends SPARQL_Item
{	
	/**
	 * (non-PHPdoc)
	 * @see SPARQL_Item#getBindValue()
	 */
	public function getBindValue()
	{
		return '"'.mysql_escape_string( $this->p['value'] ).'"';
	}
	
	/**
	 * (non-PHPdoc)
	 * @see SPARQL_Item#getOutValue()
	 */
	public function getOutValue()
	{
		return $this->getBindValue();
	}
	
	/**
	 * (non-PHPdoc)
	 * @see SPARQL_Item#getRawValue()
	 */
	public function getRawValue()
	{
		return $this->p['value'];
	}
	
	/**
	 * (non-PHPdoc)
	 * @see SPARQL_Item#getLangValue()
	 */
	public function getLangValue()
	{
		if(!isset($this->p['lang']))
		{
			throw new SPARQL_Engine_Exception('The given literal does not contain language information. Did you forget to use "literal"@lang ?');
		}
		return '"'.mysql_escape_string( $this->p['lang'] ).'"';
	}
	
	/**
	 * (non-PHPdoc)
	 * @see SPARQL_Item#getParsedDatatype()
	 */
	public function getDatatypeBinding($dtUri)
	{
		$datatypeInstance = GAMA_Datatype::getDatatypeByUri($dtUri);
		$literal = new RDFS_Literal($this->p['value']);
		
		$result = array();
		foreach($datatypeInstance->getParsedLiteral($literal) as $key => $value)
		{
			$result[$key] = '"'.mysql_escape_string($value).'"';
		}
		
		return $result;
	}
	
	/**
	 * @return string
	 */
	public function getSpecialBinding($columnName)
	{
		assert('/* Special binding not supported for literals */');
	}
	
}
?>