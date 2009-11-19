<?php
class Stats_Classes extends Stats_Base 
{
	public $numStmt;
	
	public function reloadStats()
	{
		$this->cleanupStats();
		$store = GAMA_Store::singleton();
		
		$sqlQueryString = '
			select
			  t.uri as uri,
			  count(r.id) as count
			from
			  RESOURCE r
			  join RESOURCE t on r.type = t.id
			group by r.type
			limit 1000
		';
		
		$stmt = $store->sql($sqlQueryString);
		while( $row = $stmt->fetch(PDO::FETCH_ASSOC) )
		{
			$classUri = $row['uri'];
			$numInstances = $row['count'];
			$this->numStmt[$classUri] = $numInstances; 
		}
		
		arsort($this->numStmt, SORT_NUMERIC);
	}
}
?>