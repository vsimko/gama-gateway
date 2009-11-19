<?php
/**
 * Number of manifestations without manif_url or at least one empty url.
 */
class Stat_Manif_Without_Url extends Stat_Num_Manif
{
	protected function constructQuery($filter)
	{
		return "
			PREFIX gama: <http://gama-gateway.eu/schema/>
			SELECT count_distinct(?manif) as ?num {
				$filter
				OPTIONAL { ?manif gama:manif_url ?murl }
				FILTER gama:isEmpty(?murl)
			}";
	}
}

?>