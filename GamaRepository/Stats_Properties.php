<?php
class Stats_Properties extends Stats_Base 
{
	public $stored;
	public $inferred;
	public $numDbRows;
	public $avgRowLength;
	public $dataLength;
	public $indexLength;
	public $created;
	public $updated;
	
	public function getSumStored()
	{
		return array_sum($this->stored);
	}
	
	public function getSumInferred()
	{
		return array_sum($this->inferred);
	}
	
	public function getTotalStatements()
	{
		return $this->getSumStored() + $this->getSumInferred();
	}
	
	public function getPropertyUris($orderby=null)
	{
		$keys = array_keys($this->stored);
		
		if(empty($orderby))
		{
			sort($keys);
			return $keys;
		}

		$diff = array_diff($keys, array_keys($this->$orderby));
		arsort($this->$orderby);
		
		return array_merge(array_keys($this->$orderby), $diff);
	}
	
	public function getMaxColumnValue($columnName)
	{
		arsort($this->$columnName);
		reset($this->$columnName);
		return next($this->$columnName);
	}
	
	/**
	 * Updates the local variables from the database.
	 */
	public function reloadStats()
	{
		$this->cleanupStats();
		$store = GAMA_Store::singleton();
		
		// ==========================================
		// rdf:type property is handled separately
		// ==========================================
		$this->stored[GAMA_Store::RDF_TYPE_URI] = $store->sqlFetchValue('select count(*) from RESOURCE');
		$this->inferred[GAMA_Store::RDF_TYPE_URI] = 0;
		
		$result = $store->sql('show table status where name="RESOURCE"')->fetchAll(PDO::FETCH_ASSOC);
		$this->numDbRows[GAMA_Store::RDF_TYPE_URI] = (int) $result[0]['Rows'];
		$this->created[GAMA_Store::RDF_TYPE_URI] = $result[0]['Create_time'];
		$this->updated[GAMA_Store::RDF_TYPE_URI] = $result[0]['Update_time'];
		
		
		// ==========================================
		// other properties
		// ==========================================
		$listProperties = $store->sql('
			select
				propid as propertyId,
				uri as propertyUri,
				proptype as propertyType,
				inverse as inverseOfPropertyId
			from PROPERTY
			')->fetchAll(PDO::FETCH_ASSOC);
		
		$listDbTables = array();
		$stmt = GAMA_Store::singleton()->sql('show table status');
		while($row = $stmt->fetch(PDO::FETCH_ASSOC))
		{
			$listDbTables[$row['Name']] = $row;
		}
		
		foreach($listProperties as $property)
		{
			$propertyId = $property['propertyId'];
			$propertyUri = $property['propertyUri'];
			$inverseOfPropertyId = $property['inverseOfPropertyId'];
			$propertyType = $property['propertyType'];
			$stmtTableName = 'S_'.$propertyId;
			
			// data from mysql statistics
			$this->created[$propertyUri] = $listDbTables[$stmtTableName]['Create_time'];
			$this->updated[$propertyUri] = $listDbTables[$stmtTableName]['Update_time'];
			$this->numDbRows[$propertyUri] = $listDbTables[$stmtTableName]['Rows'];
			$this->avgRowLength[$propertyUri] = $listDbTables[$stmtTableName]['Avg_row_length'];
			$this->dataLength[$propertyUri] = $listDbTables[$stmtTableName]['Data_length'];
			$this->indexLength[$propertyUri] = $listDbTables[$stmtTableName]['Index_length'];
			
			// number of stored statements
			$this->stored[$propertyUri] = $store->sqlFetchValue("select count(*) from S_$propertyId");
			
			// number of inferred statements 
			if($inverseOfPropertyId)
			{
				$this->inferred[$propertyUri] = $store->sqlFetchValue("select count(*) from S_$inverseOfPropertyId");
			} elseif($propertyType == GAMA_Store::TYPE_SYMMETRIC_PROPERTY)
			{
				$this->inferred[$propertyUri] = $store->sqlFetchValue("select count(*) from S_$propertyId");
			} elseif($propertyType == GAMA_Store::TYPE_EQUIVALENCE_PROPERTY)
			{
				$this->inferred[$propertyUri] = $store->sqlFetchValue("
					select SUM(numgroup) from (
						select POW(count(subject), 2) - count(subject) as numgroup
						from S_$propertyId group by object
					) as subselect
					");
			}
		}
	}
}

?>