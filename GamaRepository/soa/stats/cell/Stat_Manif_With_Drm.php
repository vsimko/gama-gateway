<?php
/**
 * Number of manifestations with at least one gama:manif_drmid statement.
 */
class Stat_Manif_With_Drm extends Stat_Num_Manif
{
	protected function constructQuery($filter)
	{
		return "
			PREFIX gama: <http://gama-gateway.eu/schema/>
			SELECT count_distinct(?manif) as ?num {
				$filter.
				?manif gama:manif_drmid ?drmid
			}";
	}
}

?>