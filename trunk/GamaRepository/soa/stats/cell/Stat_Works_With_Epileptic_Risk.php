<?php
/**
 * Number of works with epileptic risk.
 */
class Stat_Works_With_Epileptic_Risk extends Stat_Num_Works
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
				?work gama:has_manifestation ?manif.
				?manif gama:idx_epileptic_risk '1'.
				$filter
			}";
	}
}

?>