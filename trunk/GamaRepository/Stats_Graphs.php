<?php
class Stats_Graphs extends Stats_Base 
{
	public $numStmt;
	
	/**
	 * @return array
	 */
	public function reloadStats()
	{
		$this->cleanupStats();
		$store = GAMA_Store::singleton();
		
		$listProperties = $store->sql('
			select
				propid as propertyId,
				uri as propertyUri
			from PROPERTY
			')->fetchAll(PDO::FETCH_ASSOC);
		
		$stmt = $store->sql('select uri from GRAPH');
		while($row = $stmt->fetch(PDO::FETCH_ASSOC))
		{
			$graphName = $row['uri'];
			$this->numStmt[$graphName] = 0;
		}
		
		foreach($listProperties as $property)
		{
			$propertyId = $property['propertyId'];
			$propertyUri = $property['propertyUri'];
			
			$sqlQueryString = "
				SELECT g.uri as uri, count(*) as count
				FROM  S_$propertyId s left join GRAPH g on g.id = s.g
				group by s.g
				";
				
			$stmt = $store->sql($sqlQueryString);
			while( $row = $stmt->fetch(PDO::FETCH_ASSOC) )
			{
				$graphName = $row['uri'];
				$graphStmtCount = $row['count'];
				$this->numStmt[$graphName] += $graphStmtCount; 
			}
		}
		
		arsort($this->numStmt, SORT_NUMERIC);
	}
}

?>