<?php

require_once 'DT_xsd_string.php';
require_once 'GAMA_Datatype.php';

/**
 * This datatype is suitable for storing names or titles.
 * Uses multiple columns to store display and sorting value separately.
 */
class DT_gama_noLangLabel extends GAMA_Datatype
{
	static public function getUri()
	{
		return 'http://gama-gateway.eu/schema/noLangLabel';
	}
	
	public function getColumnDefinition()
	{
		return array(	'object'	=> 'binary(16) not null',
						'value_sort'=> 'varchar(120) character set utf8 null default null',
						'value'		=> 'varchar(120) character set utf8 null default null' );
	}
	
	public function getSortingIndexDefinition()
	{
		return 'value_sort, value';
	}
	
	public function getIndexDefinition()
	{
		return array(	'index (value)',
						'fulltext index (value)' );
	}
		
	public function getValueDefinition($tabalias)
	{
		// show the original value or the optional value if defined
		return "$tabalias.value";
	}
	
	public function getSortingValueDefinition($tabalias)
	{
		// always sort by the original value
		return "$tabalias.value_sort";
	}
	
	public function getJoinDefinition($tabalias)
	{
		return "$tabalias.object";
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
		
		return array(	'object'	=> hash('md5', $pv['display'], true),
						'value_sort'=> $pv['sort'],
						'value'		=> $pv['display'] );
	}
	
	/**
	 * Function used also outside this class.
	 * @param string $unparsedValue
	 * @return array with 'display' and 'sort' keys
	 */
	static public function parseDisplayAndSortingValue($unparsedValue)
	{
		// experimental filtering of broken values comming from DB-Adapters
		$unparsedValue = DT_xsd_string::cleanupBrokenString($unparsedValue);
		
		// match if [SORTING] is present within the value
		// if there are multiple sections with brackets, the last is used for sorting
		if(preg_match('/^(.*)\[([^\[\]]*)\](.*)$/', $unparsedValue, $parsedValue))
		{
			$sortValue = trim($parsedValue[2]);
			$displayValue = trim($parsedValue[1].$parsedValue[3]);
		} else // same for sorting and display
		{
			$displayValue = trim($unparsedValue);
		}
		
		if(empty($sortValue))
		{
			$sortValue = $displayValue;
		}
		
		return array(	'display'	=> $displayValue,
						'sort'		=> $sortValue );
	}
}
?>