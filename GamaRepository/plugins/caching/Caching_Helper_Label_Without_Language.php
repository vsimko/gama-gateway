<?php
/**
 * @author Viliam Simko
 */
class Caching_Helper_Label_Without_Language extends Caching_Helper_Multi_Lang_Label
{
	public function startCaching()
	{
		debug_time_measure(__METHOD__);
		
		// ===============
		app_lock(LOCK_EX);
		// ===============
		try
		{
			// execute pre-caching actions defined in the parent class
			Caching_Helper::startCaching();
			
			$this->preColumnCaching('value');
			$this->onColumnCaching('value');
			$this->postColumnCaching('value');
			
		} catch(Exception $e)
		{
			app_unlock();
			throw $e;
		}
		// ==========
		app_unlock();
		// ==========
			
		$this->report("\nCaching data from '{$this->sourceProperty->getUri()}' to '{$this->targetProperty->getUri()}' took ".debug_time_measure(__METHOD__)." seconds\n");
	}
	
	protected function onColumnCaching($columnName)
	{
		$this->report("\nCaching the language '$columnName' of the property '{$this->targetProperty->getUri()}'\n");
		
		$store = GAMA_Store::singleton();
		
		$this->report("Creating the column '$columnName' and temporary sorting column '{$columnName}_tmp'\n");
		$store->sql("alter table {$this->targetProperty->getStmtTab()} add {$columnName}_tmp varchar(120) character set utf8 null default null");
		$store->sql("alter table {$this->targetProperty->getStmtTab()} add {$columnName} varchar(120) character set utf8 null default null");
		
		// the priority of values is defined here
		// feel free to shuffle lines if necessary
		$this->fill($columnName, self::HARMON, '*');
		$this->fill($columnName, self::NORMAL, '*');
	}
	
	/**
	 * The only change from the parent class is that the column_tmp will
	 * be renamed to column_sort in the postColumnCaching function.
	 * 
	 * (non-PHPdoc)
	 * @see plugins/caching/Caching_Helper_Multi_Lang_Label#preColumnCaching($columnName)
	 */
	protected function preColumnCaching($columnName)
	{
		// nothing to do here
	}
	
	/**
	 * The only change from the parent class is that the column_tmp will
	 * be renamed to column_sort in the postColumnCaching function.
	 * 
	 * (non-PHPdoc)
	 * @see plugins/caching/Caching_Helper_Multi_Lang_Label#postColumnCaching($columnName)
	 */
	protected function postColumnCaching($columnName)
	{
		$store = GAMA_Store::singleton();
				
		$this->report("\nExecuting post-caching actions on column '$columnName':\n");
		debug_time_measure(__METHOD__);
		
		// rename the column_tmp to column_sort
		$this->report("  Renaming the '{$columnName}_tmp' to {$columnName}_sort:\n");
		$store->sql("
			alter table {$this->targetProperty->getStmtTab()}
			change column {$columnName}_tmp {$columnName}_sort varchar(120) character set utf8 null default null");
			
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