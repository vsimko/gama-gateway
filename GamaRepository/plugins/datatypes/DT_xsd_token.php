<?php

/**
 * The most appropriate datatype to use for strings that don't care about whitespace.
 * All the extra whitespace replaced by single spaces, leading and trailing
 * spaces removed, and contiguous sequences of spaces replaced by single spaces.
 * 
 * MySQL: varchar(250) with UTF-8 encoding and b-tree + fulltext indexes
 */
class DT_xsd_token extends DT_xsd_normalizedString
{
	static public function getUri()
	{
		return 'http://www.w3.org/2001/XMLSchema#token';
	}
}
?>