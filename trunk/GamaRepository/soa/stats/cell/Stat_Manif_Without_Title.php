<?php
/**
 * Number of manifestations without manif_title or at least one empty title.
 */
class Stat_Manif_Without_Title extends Stat_Num_Manif
{
	protected function constructQuery($filter)
	{
		return "
			PREFIX gama: <http://gama-gateway.eu/schema/>
			SELECT count_distinct(?manif) as ?num {
				$filter
				OPTIONAL { ?manif gama:manif_title ?mtitle }
				FILTER gama:isEmpty(?mtitle)
			}";
	}
}

?>