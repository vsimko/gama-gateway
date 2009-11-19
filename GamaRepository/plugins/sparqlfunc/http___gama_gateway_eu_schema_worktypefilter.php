<?php

/**
 * Filters Work according to the work_type property dividing them to 3 distinct
 * groups - Artworks, Resources, Events
 * 
 * - first parameter is a variable representing the URI of the work 
 * - other parameters are one of "Artwork", "Resource", "Event")
 * 
 * Example: FILTER gama:workTypeFilter(?workuri, "Artwork", "Resource")
 */
class http___gama_gateway_eu_schema_worktypefilter extends SPARQL_Function
{
	const WORK_TYPE_PROPERTY_URI = 'http://gama-gateway.eu/schema/work_type';
	const WORK_TYPES_PREFIX = 'http://gama-gateway.eu/schema/WorkType/';
	/**
	 * (non-PHPdoc)
	 * @see SPARQL_Function#execute()
	 */
	function execute()
	{
		$var = $this->shiftParam(self::P_VAR);
		
		$property = Resource_Manager::singleton()->getResourceByUri(self::WORK_TYPE_PROPERTY_URI);
		$property->isInStore(true);
		
		$tab = $this->engine->addStmtTable($property->getID(), 'worktypefilter:'.$property->getUri());
		$var->bindWithTable("$tab.subject");

		$bind = $this->engine->updateVar2('wt', 'RESOURCE');
		$this->engine->joinStatement('wt', 'var', "$tab.object");
		
		// table name
		$rtab = preg_replace('/\..*/', '', $bind);
						
		$sql = array();
		do
		{
			$worktype = $this->shiftParam(self::P_LITERAL);
			$sql[] = $rtab.'.uri like "'.self::WORK_TYPES_PREFIX.$worktype->getRawValue().'%"';
	
		} while($this->hasMoreParameters());
		
		array_unique($sql);
		
		return '('.implode(" or\n", $sql).')';
	}
}
?>