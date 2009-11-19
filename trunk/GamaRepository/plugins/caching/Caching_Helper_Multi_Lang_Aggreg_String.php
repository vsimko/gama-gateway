<?php
/**
 * @author Viliam Simko
 */
class Caching_Helper_Multi_Lang_Aggreg_String extends Caching_Helper_Multi_Lang_String
{	
	protected function getUpdateRules($sourceAlias, $targetAlias, $targetColumn)
	{
		$store = GAMA_Store::singleton();
		return "
			$targetAlias.g = {$store->getGraphID()},
			$targetAlias.{$targetColumn} = {$sourceAlias}.value
		";
	}
	
	/**
	 * Fills all empty values from source property of source language
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
			SET session group_concat_max_len = @@max_allowed_packet;
			update
				{$this->targetProperty->getStmtTab()} as target,
				{$this->owlSameAs->getStmtTab()} as eq1,
				{$this->owlSameAs->getStmtTab()} as eq2,
				(
					select
						subject,
						group_concat(
							DISTINCT {$this->sourceProperty->stmtValue('source')}
							SEPARATOR '\\n\\n'
						) as value
					from {$this->sourceProperty->getStmtTab()} as source
					where true
						$langRestrictionSql
						$harmoGraphRestrictionSql
					group by subject
				) as source
			set {$this->getUpdateRules('source', 'target', $targetColumn)}
			where
				eq2.object = eq1.object
				and target.subject = eq1.subject
				and source.subject = eq2.subject
				and target.$targetColumn is null;
				
			update
				{$this->targetProperty->getStmtTab()} as target,
				(
					select
						subject,
						group_concat(
							DISTINCT {$this->sourceProperty->stmtValue('source')}
							SEPARATOR '\n\n'
						) as value
					from {$this->sourceProperty->getStmtTab()} as source
					where true
						$langRestrictionSql
						$harmoGraphRestrictionSql
					group by subject
				) as source
			set {$this->getUpdateRules('source', 'target', $targetColumn)}
			where
				target.subject = source.subject
				and target.$targetColumn is null;
		");

		$this->report(debug_time_measure(__METHOD__)." seconds\n");
	}
}
?>