<?php
/**
 * Represents the variable.
 * @author Viliam Simko
 */
class SPARQL_Item_Var extends SPARQL_Item
{	
	/**
	 * (non-PHPdoc)
	 * @see SPARQL_Item#getBindValue()
	 */
	public function getBindValue()
	{
		$varname = $this->p['value'];
		if(isset($this->engine->vars[$varname]['bind']))
		{
			return $this->engine->vars[$varname]['bind'];
		}
		
		throw new SPARQL_Engine_Exception('Orphan variable: ?'.$varname);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see SPARQL_Item#getOutValue()
	 */
	public function getOutValue()
	{
		$varname = $this->p['value'];
		if(isset($this->engine->vars[$varname]['out']))
		{
			return $this->engine->vars[$varname]['out'];
		}
		
		throw new SPARQL_Engine_Exception('This variable is not defined as output variable : ?'.$varname);
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
		$varname = $this->p['value'];
		$outlang = $this->engine->vars[$varname]['outlang'];
		if(empty($outlang))
		{
			throw new SPARQL_Engine_Exception("The variable ?$varname does not support language binding.");
		}
		return $outlang;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see SPARQL_Item#getDatatypeBinding()
	 */
	public function getDatatypeBinding($dtUri)
	{
		$datatypeInstance = GAMA_Datatype::getDatatypeByUri($dtUri);
		$varName = $this->p['value'];
		$tabId = $this->engine->vars[$varName]['tab'];
		
		$binding = array();
		foreach( array_keys($datatypeInstance->getColumnDefinition()) as $colName)
		{
			$binding[$colName] = $tabId.'.'.$colName;
		}
		return $binding;
	}

	/**
	 * @return string
	 */
	public function getSpecialBinding($columnName)
	{
		$varName = $this->p['value'];
		$tabId = $this->engine->vars[$varName]['tab'];
		return $tabId.'.'.$columnName;
	}
	
	/**
	 * This is a nasty hack for the gama:removeEqDups SPARQL function.
	 * @return unknown_type
	 */
	public function getJoinLog()
	{
		$varName = $this->p['value'];
		return $this->engine->vars[$varName]['joinlog'];
	}
}
?>