<?php
/**
 * Number of works without at least one main manifestation.
 * Works without manifestation may be invisble for the frontend.
 * A manifestation doesn't have to have a video counterpart.
 * It can be only metadata.
 */
class Stat_Works_Without_Main_Manif extends Stat_Num_Works
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
				OPTIONAL {
					?work gama:has_manifestation ?manif.
					?manif gama:idx_main '1'.
				}
				FILTER gama:isEmpty(?manif)
				$filter
			}";
	}
}

?>