<?php

/**
 * Any occurrence of #x9 (tab), #xA (linefeed), and #xD (carriage return)
 * have been replaced by an occurrence of #x20 (space) without any
 * whitespace collapsing.
 * 
 * MySQL: varchar(250) with UTF-8 encoding and b-tree + fulltext indexes
 */
class DT_xsd_normalizedString extends DT_xsd_string
{
	static public function getUri()
	{
		return 'http://www.w3.org/2001/XMLSchema#normalizedString';
	}
	
	public function getColumnDefinition()
	{
		$columnDef = parent::getColumnDefinition();
		$columnDef['data'] = 'varchar(250) character set utf8 null default null';
		return $columnDef;
	}
	
	public function getSortingIndexDefinition()
	{
		return 'data';
	}
}
?>