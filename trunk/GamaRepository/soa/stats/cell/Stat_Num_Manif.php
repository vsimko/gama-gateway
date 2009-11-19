<?php
/**
 * Number of manifestations.
 */
class Stat_Num_Manif extends Stat_Num_Works
{
	
	/**
	 * Restrict the query on main manifestations.
	 * @datatype boolean
	 * @optional
	 */
	static $PARAM_MAINMANIFONLY = '-mainmanifonly';
	
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
			$filters[] = 'OPTIONAL { ?manif gama:manifestation_of ?work }';
			if($this->getParam(self::$PARAM_MAINMANIFONLY, self::OPTIONAL))
			{
				$filters[] = 'OPTIONAL { ?manif gama:idx_main "1" }';
			}
		} else
		{
			$filters[] = '?manif gama:manifestation_of ?work';
			
			if($this->getParam(self::$PARAM_MAINMANIFONLY, self::OPTIONAL))
			{
				$filters[] = '?manif gama:idx_main "1"';
			}
		}
		
		return $filters;
	}
	
	protected function constructQuery($filter)
	{
		return "
			PREFIX gama: <http://gama-gateway.eu/schema/>
			SELECT count_distinct(?manif) as ?num {
				?manif a gama:Manifestation.
				$filter 
			}";
	}
}

?>