<?php
/**
 * Number of works without title or with at least one empty title.
 */
class Stat_Works_Without_Title extends Stat_Num_Works
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
				OPTIONAL { ?work gama:work_title ?title }
				FILTER gama:isEmpty(?title).
				$filter
			}";
	}
}

?>