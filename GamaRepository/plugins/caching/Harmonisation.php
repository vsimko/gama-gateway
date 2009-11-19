<?php
class Harmonisation
{
	private $property;
	
	public function  __construct($propertyUri)
	{
		$this->property = Resource_Manager::singleton()->getResourceByUri($propertyUri);
	}

	public function sqlSingleHarmonisedValuePerSubject()
	{
		return "
			select * from ({$this->sqlHarmonisedProperty()}) h
			group by id
		";
	}
	
	public function sqlAggregatedHarmonisedValuesPerSubject($column)
	{
		return "
			select id, group_concat(distinct $column separator '\\n' ) as value
			from ({$this->sqlHarmonisedProperty()}) h
			group by id
		";
	}
	
	private function getAliasesSql()
	{
		// missing identities in the owl:sameAs
		$owlSameAs = Resource_Manager::singleton()
			->getResourceByUri(GAMA_Store::OWL_SAME_AS_URI);
			
		return "
			select
				s1.subject as orig,
				s2.subject as alias
			from
				{$owlSameAs->getStmtTab()} s1
				join {$owlSameAs->getStmtTab()} s2 on s1.object = s2.object
		";
	}
	
	private function sqlHarmonisedProperty()
	{
		$store = GAMA_Store::singleton();
		$store->fillOwlSameAsIdentities();
		
		$harmonisationGraphId = $store->getGraphID(Caching_Helper::HARMONISATION_GRAPH_ID);
		
		return "
			select
				a.orig as id,
				if(t.g = $harmonisationGraphId, 1, 0) as harmo,
				t.*
			from
				({$this->getAliasesSql()}) a
				join {$this->property->getStmtTab()} t on t.subject = a.alias
			order by subject, harmo desc
		";
	}
	
	/**
	 * @param $targetPropertyUri
	 * @return RDF_Property
	 */
	public function fillEmptyValuesPerSubject($targetPropertyUri)
	{
		$targetProperty = Resource_Manager::singleton()->getResourceByUri($targetPropertyUri);
		
		$store = GAMA_Store::singleton();
		$store->setGraph(Caching_Helper::CACHING_GRAPH_ID);
		
		echo "\nFilling empty values for property '{$targetProperty->getUri()}' ({$targetProperty->getStmtTab()}) ... ";
		debug_time_measure(__METHOD__);
			
			$domainId = $store->sqlFetchValue(
				'select dom from PROPERTY where uri = ?',
				$targetProperty->getUri() );

			$store->sql("DELETE FROM {$targetProperty->getStmtTab()}");
			$store->sql("
				insert into {$targetProperty->getStmtTab()} (g,subject)
				select ?, id from RESOURCE where type=?",
				$store->getGraphID(), $domainId );
			
		echo debug_time_measure(__METHOD__)."s\n";
		
		return $targetProperty;
	}
}