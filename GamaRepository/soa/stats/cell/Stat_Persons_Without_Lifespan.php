<?php
/**
 * Number of persons without lifespan
 */
class Stat_Persons_Without_Lifespan extends Stat_Num_Persons
{
	/**
	 * (non-PHPdoc)
	 * @see soa/stats/cell/Stat_Num_Works#constructQuery()
	 */
	protected function constructQuery($filter)
	{
		return "
			PREFIX gama: <http://gama-gateway.eu/schema/>
			SELECT count_distinct(?person) as ?num {
				?person a gama:Person.
				OPTIONAL { ?person gama:life_span ?name }
				FILTER gama:isEmpty(?name).
				$filter
			}";
	}
}

?>