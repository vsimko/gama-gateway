<?php
/**
 * Number of main manifestations with idx_stream_avail 1 or 2
 */
class Stat_Manif_Avail_Stream extends Stat_Num_Manif
{
	protected function constructQuery($filter)
	{
		return "
			PREFIX gama: <http://gama-gateway.eu/schema/>
			SELECT count_distinct(?manif) as ?num {
				?manif gama:idx_stream_avail ?s.
				FILTER (?s = '1' || ?s = '2').
				$filter
			}";
	}
}

?>