<?php
/**
 * Number of persons without title or with at least one empty name.
 */
class Stat_Persons_Without_Name extends Stat_Num_Persons
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
				OPTIONAL { ?person gama:person_name ?name }
				FILTER gama:isEmpty(?name).
				$filter
			}";
	}
}

?>