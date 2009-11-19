<?php
/**
 * @see http://dev.mysql.com/doc/refman/5.1/en/date-and-time-functions.html#function_year
 * @author Viliam Simko
 */
class http___www_mysql_com_year extends SPARQL_Function
{
	/**
	 * (non-PHPdoc)
	 * @see SPARQL_Function#execute()
	 */
	function execute()
	{
		$date = $this->shiftParam(self::P_LITERAL + self::P_VAR)->getOutValue();
		return "YEAR($date)";
	}
}
?>