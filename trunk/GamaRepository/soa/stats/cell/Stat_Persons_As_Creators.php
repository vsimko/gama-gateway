<?php
/**
 * Number of artists that are not connected to works
 * - is_creator
 * - is_producer
 * - is_contributor
 */
class Stat_Persons_As_Creators extends Stat_Num_Persons
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
				  OPTIONAL {
				    ?person gama:is_creator ?w1.
				    ?person gama:is_producer ?w2.
				    ?person gama:is_contributor ?w3.
				  }
				  FILTER gama:isEmpty(?w1)
				  FILTER gama:isEmpty(?w2)
				  FILTER gama:isEmpty(?w3)
				$filter
			}";
	}
}

?>