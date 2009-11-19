<?php
/**
 * Two strings that sound almost the same should have identical soundex strings. 
 * This function, as currently implemented, is intended to work well with
 * strings that are in the English language only. Strings in other languages
 * may not produce reliable results.
 * This function is not guaranteed to provide consistent results with
 * strings that use multi-byte character sets, including utf-8.
 * This limitation will be removed in the newer MySQL version
 * http://bugs.mysql.com/bug.php?id=22638
 * @see http://dev.mysql.com/doc/refman/5.0/en/string-functions.html#function_soundex
 * 
 * @author Viliam Simko
 */
class http___www_mysql_com_soundex extends SPARQL_Function
{
	/**
	 * (non-PHPdoc)
	 * @see SPARQL_Function#execute()
	 */
	function execute()
	{
		$str = $this->shiftParam(self::P_LITERAL + self::P_VAR)->getOutValue();
		return "SOUNDEX($str)";
	}
}
?>