<?php
/**
 * @author Viliam Simko
 */
class Caching_Helper_Multi_Lang_String extends Caching_Helper
{	
	const HARMON = true;
	const NORMAL = false;
	
	public function startCaching()
	{
		debug_time_measure(__METHOD__);
		
		// ===============
		app_lock(LOCK_EX);
		// ===============
		try
		{
			// execute pre-caching actions defined in the parent class
			parent::startCaching();
			
			$listLanguages = preg_split('/,\s*/', Config::get('dt.multilangstring.languages')); 
			
			foreach($listLanguages  as $lang)
			{
				$this->preColumnCaching($lang);
			}
			
			foreach($listLanguages  as $lang)
			{
				$this->onColumnCaching($lang);
			}
			
			foreach($listLanguages  as $lang)
			{
				$this->postColumnCaching($lang);
			}
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
	
	protected function preColumnCaching($columnName)
	{
		$store = GAMA_Store::singleton();
		
		$this->report("Creating the column '$columnName'\n");
		$store->sql("alter table {$this->targetProperty->getStmtTab()} add {$columnName} text character set utf8 null default null");
	}
	
	protected function onColumnCaching($columnName)
	{
		$this->report("\nCaching the language '$columnName' of the property '{$this->targetProperty->getUri()}'\n");
		
		// the priority of values is defined here
		// feel free to shuffle lines if necessary
		$this->fill($columnName, self::HARMON, $columnName);
		$this->fill($columnName, self::NORMAL, $columnName);
		$this->fill($columnName, self::HARMON, 'en');
		$this->fill($columnName, self::HARMON, '');
		$this->fill($columnName, self::NORMAL, 'en');
		$this->fill($columnName, self::NORMAL, '');
		$this->fill($columnName, self::HARMON, '*');
		$this->fill($columnName, self::NORMAL, '*');
	}
	
	protected function postColumnCaching($columnName)
	{
		$store = GAMA_Store::singleton();
		
		$this->report("Building a fulltext index on column '$columnName' ... ");
		debug_time_measure(__METHOD__);
		
		$store->sql("alter table {$this->targetProperty->getStmtTab()} add fulltext index({$columnName})");
		
		$this->report(debug_time_measure(__METHOD__)." seconds\n");
	}
	
	protected function getUpdateRules($sourceAlias, $targetAlias, $targetColumn)
	{
		$store = GAMA_Store::singleton();
		return "
			$targetAlias.g = {$store->getGraphID()},
			$targetAlias.{$targetColumn} = {$this->sourceProperty->stmtValue($sourceAlias)}
		";
	}
	
	/**
	 * Fills all empty values from source property of source languag
	 * to the target property column defined by the $lang parameter.
	 * There is no distinction between harmonised data and normal data.
	 * @param $targetColumn
	 * @param $restrictHarmo
	 * @param $lang
	 */
	protected function fill($targetColumn, $restrictHarmo, $lang)
	{
		$store = GAMA_Store::singleton();
		
		$this->report(" FILL ( {$this->targetProperty->getStmtTab()} : $restrictHarmo, $lang ) ... ");
		debug_time_measure(__METHOD__);
		
		$langRestrictionSql = ($lang == '*')
			? ''
			: "and {$this->sourceProperty->stmtLang('source')} = '$lang'";
		
		$harmoGraphRestrictionSql = ($restrictHarmo)
			? "and source.g = $this->harmonisationGraphId"
			: '';
				
		$store->sql("
			update
				{$this->targetProperty->getStmtTab()} as target,
				{$this->sourceProperty->getStmtTab()} as source,
				{$this->owlSameAs->getStmtTab()} as eq1,
				{$this->owlSameAs->getStmtTab()} as eq2
			set {$this->getUpdateRules('source', 'target', $targetColumn)}
			where
				eq2.object = eq1.object
				and target.subject = eq1.subject
				and source.subject = eq2.subject
				and target.$targetColumn is null
				$harmoGraphRestrictionSql
				$langRestrictionSql
		");
		
		$store->sql("
			update
				{$this->targetProperty->getStmtTab()} as target,
				{$this->sourceProperty->getStmtTab()} as source
			set {$this->getUpdateRules('source', 'target', $targetColumn)}
			where
				target.subject = source.subject
				and target.$targetColumn is null
				$harmoGraphRestrictionSql
				$langRestrictionSql
		");

		$this->report(debug_time_measure(__METHOD__)." seconds\n");
	}
}
?>