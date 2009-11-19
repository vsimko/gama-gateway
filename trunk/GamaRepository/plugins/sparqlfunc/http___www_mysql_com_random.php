<?php

/**
 * Used with ORDER BY RAND(seed)
 * - first parameter is optioal and represents the seed value
 */
class http___www_mysql_com_random extends SPARQL_Function
{
	/**
	 * (non-PHPdoc)
	 * @see SPARQL_Function#execute()
	 */
	function execute()
	{
		if($this->hasMoreParameters())
		{
			$seed = $this->shiftParam(self::P_LITERAL)->getBindValue();
		} else
		{
			$seed = '';
		}
		
		return "RAND($seed)";
	}
}
?>