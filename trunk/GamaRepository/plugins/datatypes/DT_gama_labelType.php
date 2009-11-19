<?php

require_once 'DT_gama_noLangLabel.php';

/**
 * This datatype is suitable for storing names or titles.
 * It also supports the language definition for every value.
 *
 * @see DT_gama_noLangLabel
 * @author Viliam Simko
 */
class DT_gama_labelType extends DT_gama_noLangLabel
{
	static public function getUri()
	{
		return 'http://gama-gateway.eu/schema/labelType';
	}
	
	public function getLangJoinDefinition($tabalias)
	{
		return "$tabalias.lang";
	}

	/**
	 * Expected value is composed of two parts - the sorting value and the
	 * displaying value.
	 *
	 * @param RDFS_Literal $literal
	 * @return array
	 */
	public function getParsedLiteral(RDFS_Literal $literal)
	{
		$pv = self::parseDisplayAndSortingValue($literal->value);
		$lang = $literal->lang ? $literal->lang : '';
		
		return array(	'object'	=> hash('md5', "$lang:$pv[display]", true),
						'lang'		=> $lang,
						'value_sort'=> $pv['sort'],
						'value'		=> $pv['display'] );
	}
}
?>