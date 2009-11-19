<?php
/**
 * Base class for caching helpers that use individual columns separately.
 * @author Viliam Simko
 */
class Caching_Helper_Column_From_Sparql extends Caching_Helper
{
	/**
	 * Uses on the target property.
	 * @param $targetPropertyUri
	 */
	public function __construct($targetPropertyUri)
	{
		$resmgr = Resource_Manager::singleton();
		$store = GAMA_Store::singleton();
		
		$this->targetProperty = $resmgr->getResourceByUri($targetPropertyUri);
		
		// use this graph ID when updating statement tables
		$store->setGraph(self::CACHING_GRAPH_ID);
	}
	
	/**
	 * Drops and creates the database table column. 
	 * @param $columnName
	 * @param $columnType SQL definition eg. varchar(123) not null 
	 */
	protected function buildColumnStructure($columnName, $columnType)
	{
		$store = GAMA_Store::singleton();
		$tableName = $this->targetProperty->getStmtTab();
		
		try
		{
			$store->sql("alter table $tableName drop column $columnName");
		} catch(Exception $e)
		{
			// ignore the error when the columns does not exist
			// MySQL does not support ALTER TABLE DROP COLUMN IF EXISTS
		}
		
		$store->sql("alter table $tableName add column $columnName $columnType");
	}
	
	/**
	 * @param $_ variable arguments, at least one column name is required
	 */
	public function buildFulltextIndex($_)
	{
		$columns = func_get_args();
		$columns = implode(', ', $columns);
		
		$this->report("Building fulltext index on columns '$columns' ... ");
		debug_time_measure(__METHOD__);
		
		$store = GAMA_Store::singleton();
		$store->sql("alter table {$this->targetProperty->getStmtTab()} add fulltext index ($columns)");
		
		$this->report(debug_time_measure(__METHOD__)." seconds\n");
	}
	
	/**
	 * Create sorting sequence based on SPARQL.
	 * @param $columnName
	 * @param $sparqlQuery
	 */
	public function buildSortingColumn($columnName, $sparqlQuery)
	{
		$this->buildColumnStructure($columnName, 'int not null default 0');

		// convert SPARQL to SQL
		$engine = new SPARQL_Engine;
		$engine->useSparql($sparqlQuery);
		$sql = $engine->getSql();
				
		$this->report("Building sorting column '$columnName' ... ");
		debug_time_measure(__METHOD__);
		
		$store = GAMA_Store::singleton();
		
		// reset the sorting column
		$store->sql("update {$this->targetProperty->getStmtTab()} set $columnName=0");
		
		// build a new sorting sequence
		$store->sql("
			set @seq = 0;
			update
				{$this->targetProperty->getStmtTab()} as target,
				(
					select r.id, x.seq from
					(select @seq := @seq + 1 as seq, y.uri from ($sql) as y) as x
					join RESOURCE as r on r.uri = x.uri
					group by r.id
				) as source
			set target.$columnName = source.seq
			where target.subject = source.id
		");

		$this->report(debug_time_measure(__METHOD__)." seconds\n");
	}
	
	/**
	 * @param $columnName
	 * @param $sparqlQuery
	 */
	public function buildTextColumn($columnName, $sparqlQuery)
	{
		$this->buildColumnStructure($columnName, 'text charset "utf8"');
		$this->appendToTextColumn($columnName, $sparqlQuery);
	}
	
	/**
	 * Appends data to an existing column.
	 * @param string $columnName
	 * @param string $sparqlQuery
	 */
	public function appendToTextColumn($columnName, $sparqlQuery)
	{
		$engine = new SPARQL_Engine;
		$engine->useSparql($sparqlQuery);
		$sql = $engine->getSql();

		$this->report("Appending data to column '$columnName' ... ");
		debug_time_measure(__METHOD__);
		
			$store = GAMA_Store::singleton();
			$store->sql("
				SET SESSION group_concat_max_len = @@max_allowed_packet;
				UPDATE
					{$this->targetProperty->getStmtTab()} AS target,
					(
						SELECT
							r.id,
							GROUP_CONCAT(
								DISTINCT x.value
								ORDER BY x.value
								SEPARATOR '\n'
							) AS value
						FROM ($sql) AS x JOIN RESOURCE AS r ON r.uri = x.uri 
						GROUP BY r.id
					) AS source
				SET target.$columnName = CONCAT_WS('\n', target.$columnName, source.value)
				WHERE target.subject = source.id
			");

		$this->report(debug_time_measure(__METHOD__)." seconds\n");
	}
	
	/**
	 * Set TRUE for rows matching the SPARQL query.
	 * @param $columnName which column should be updated
	 * @param $sparqlQuery must contain ?uri output variable
	 */
	public function buildBooleanColumn($columnName, $sparqlQuery)
	{
		$this->buildColumnStructure($columnName, 'bool not null default 0');
		
		$engine = new SPARQL_Engine;
		$engine->useSparql($sparqlQuery);
		$sql = $engine->getSql();
	
		$this->report("Building boolean column '$columnName' ... ");
		debug_time_measure(__METHOD__);
		
			$store = GAMA_Store::singleton();
			$store->sql("
				update
					{$this->targetProperty->getStmtTab()} as target,
					(
						select r.id
						from ($sql) as x join RESOURCE as r on r.uri = x.uri
					) as source
				set target.$columnName = 1
				where target.subject = source.id;
				
				alter table {$this->targetProperty->getStmtTab()} add index ($columnName);
			");
		$this->report(debug_time_measure(__METHOD__)." seconds\n");
	}
}

?>