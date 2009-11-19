<?php
/**
 * Useful for filtering a list of lanugages.
 * - first parameter is the sparql variable
 * - then an arbitrary number of language definitions
 * 
 * @author Viliam Simko
 */
class http___gama_gateway_eu_schema_lang extends SPARQL_Function
{
	/**
	 * (non-PHPdoc)
	 * @see SPARQL_Function#execute()
	 */
	public function execute()
	{
		// first parameter is the sparql variable
		$var = $this->shiftParam(self::P_VAR);
		
		// also include statements without the language (empty 'lang' column)
		$langs = array('""');
		
		while($this->hasMoreParameters())
		{
			$langs[] = $this->shiftParam(self::P_LITERAL)->getBindValue();
		}
		
		return $var->getLangValue().' in ('.implode(',', $langs).')';
	}	
}
?>