<?php
/**
 * Number of works without the creator.
 * Wroks without a creator may be invisible for the frontend.
 */
class Stat_Works_Without_Creator extends Stat_Num_Works
{
	/**
	 * (non-PHPdoc)
	 * @see soa/stats/cell/Stat_Num_Works#constructQuery()
	 */
	protected function constructQuery($filter)
	{
		return "
			PREFIX gama: <http://gama-gateway.eu/schema/>
			SELECT count_distinct(?work) as ?num {
			  ?work a gama:Work.
			  OPTIONAL { ?work gama:has_creator ?creator }
			  FILTER gama:isEmpty(?creator).
			  $filter
			}";
	}
}

?>