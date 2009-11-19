<?php
/**
 * Number of persons.
 */
class Stat_Num_Persons extends Stat_Num_Works
{
	/**
	 * (non-PHPdoc)
	 * @see soa/stats/cell/Stat_Num_Works#getFilters()
	 */
	protected function getFilters()
	{
		$filters = parent::getFilters();

		if( $this->getParam(self::$PARAM_WORK_TYPE, self::OPTIONAL) == self::EMPTY_PLACEHOLDER ||
			$this->getParam(self::$PARAM_ARCHIVE_URI, self::OPTIONAL) == self::EMPTY_PLACEHOLDER )
		{
			$filters[] = 'OPTIONAL { ?work gama:has_creator ?person }';
		} elseif(!empty($filters))
		{
			$filters[] = '?work gama:has_creator ?person';
		}
		
		return $filters;
		
	}
	
	protected function constructQuery($filter)
	{
		return "
			PREFIX gama: <http://gama-gateway.eu/schema/>
			SELECT count_distinct(?person) as ?num {
				?person a gama:Person.
				$filter
			}";
	}
}

?>