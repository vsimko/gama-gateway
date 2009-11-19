<?php
/**
 * @author Viliam Simko
 */
class Caching_Helper_Multi_Lang_Label extends Caching_Helper_Multi_Lang_String
{
	protected function getUpdateRules($sourceAlias, $targetAlias, $targetColumn)
	{
		$store = GAMA_Store::singleton();
		return "
			$targetAlias.g = {$store->getGraphID()},
			$targetAlias.{$targetColumn} = {$this->sourceProperty->stmtValue($sourceAlias)},
			$targetAlias.{$targetColumn}_tmp = {$this->sourceProperty->stmtOrderByValue($sourceAlias)}
		";
	}
	
	protected function preColumnCaching($columnName)
	{
		$store = GAMA_Store::singleton();
		
		$this->report("Creating the sorting column '{$columnName}_sort'\n");
		$store->sql("alter table {$this->targetProperty->getStmtTab()} add {$columnName}_sort int not null default 0");
	}
	
	protected function onColumnCaching($columnName)
	{
		$store = GAMA_Store::singleton();
		
		$this->report("Creating the column '$columnName' and temporary sorting column '{$columnName}_tmp'\n");
		$store->sql("alter table {$this->targetProperty->getStmtTab()} add {$columnName}_tmp varchar(120) character set utf8 null default null");
		$store->sql("alter table {$this->targetProperty->getStmtTab()} add {$columnName} varchar(120) character set utf8 null default null");
		
		parent::onColumnCaching($columnName);
	}
	
	protected function postColumnCaching($columnName)
	{
		$store = GAMA_Store::singleton();
		
		$this->report("\nExecuting post-caching actions on column '$columnName':\n");
		debug_time_measure(__METHOD__);
		
		// these indexes are not needed during the caching phase
		$this->report("  Building b-tree index on column '{$columnName}_tmp'\n");
		$store->sql("alter table {$this->targetProperty->getStmtTab()} add index({$columnName}_tmp)");
		
		// fill the sorting column (which is an integer)
		// it uses the b-tree index of the source column 
		$this->report("  Generating the sorting sequence into '{$columnName}_sort'\n");
		$store->sql("
			set @seq = 0;
			update {$this->targetProperty->getStmtTab()}
			set {$columnName}_sort = (@seq := @seq + 1)
			order by {$columnName}_tmp
		");
		
		$this->report("  Dropping the temporary sorting column '{$columnName}_tmp'\n");
		$store->sql("alter table {$this->targetProperty->getStmtTab()} drop column {$columnName}_tmp");

		// index should be built after filling the sorting column because the
		// index is not used before and it would only slow down the filling
		$this->report("  Building b-tree index on '{$columnName}_sort'\n");
		$store->sql("alter table {$this->targetProperty->getStmtTab()} add index({$columnName}_sort)");
		
		$this->report("  Building fulltext index on column '{$columnName}'\n");
		$store->sql("alter table {$this->targetProperty->getStmtTab()} add fulltext index({$columnName})");
		
		$this->report("Post-caching actions took ".debug_time_measure(__METHOD__)." seconds\n");
	}
}
?>