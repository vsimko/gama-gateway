<?php
/**
 * Number of works without the creation date.
 */
class Stat_Works_Without_Date extends Stat_Num_Works
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
				OPTIONAL { ?work gama:work_created ?c }
				FILTER gama:isEmpty(?c)
				$filter
			}";
	}
}

?>