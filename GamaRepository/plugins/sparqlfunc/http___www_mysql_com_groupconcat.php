<?php

/**
 * Limited support for the GROUP_CONCAT function from MySQL.
 * @see http://dev.mysql.com/doc/refman/5.0/en/group-by-functions.html#function_group-concat
 * 
 * Example:
 *   FILTER (?g = mysql:groupConcat("distinct", "prefix:o_", ?kw, "separator:,"))
 */
class http___www_mysql_com_groupconcat extends SPARQL_Function
{
	/**
	 * (non-PHPdoc)
	 * @see SPARQL_Function#execute()
	 */
	function execute()
	{
		$bind = array();
		$useDistinct = '';
		$useSeparator = '';
		$usePrefix = null;
		
		// handle remaining parameters
		while($this->hasMoreParameters())
		{
			$param = $this->shiftParam(self::P_VAR | self::P_LITERAL);
			if($param instanceof SPARQL_Item_Var)
			{
				if(isset($usePrefix))
				{
					$bind[] = 'CONCAT("'.mysql_escape_string($usePrefix).'",'.$param->getBindValue().')';
					$usePrefix = null;
				} else
				{
					$bind[] = $param->getBindValue();
				}
			} else
			{
				$str = $param->getRawValue();
				$match = null;
				
				// handle the modifiers
				if(preg_match('/^distinct/i', $str))
				{
					$useDistinct = 'DISTINCT';
				}
				
				if(preg_match('/separator:(.*)/i', mysql_escape_string($str), $match))
				{
					$useSeparator = "SEPARATOR '".$match[1]."'";
				}
				
				if(preg_match('/prefix:(.*)/i', mysql_escape_string($str), $match))
				{
					$usePrefix = $match[1];
				}
			}
		}
		
		$this->engine->addExtraSql('SET SESSION group_concat_max_len = @@max_allowed_packet');
		
		return "GROUP_CONCAT($useDistinct ".implode(',', $bind)." $useSeparator)";
	}
}
?>