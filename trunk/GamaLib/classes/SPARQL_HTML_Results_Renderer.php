<?php

/**
 * TODO: when finished, it should render a HTML table.
 * @author Viliam Simko
 */
class SPARQL_HTML_Results_Renderer implements SPARQL_Result_Handler_Interface
{
	const INDENT_STR = ' ';
	
	private $outputVariables;
	
	/**
	 * (non-PHPdoc)
	 * @see SPARQL_Result_Handler_Interface#onBeginResults($caller)
	 */
	function onBeginResults($caller, array $outputVariables, array $debugString = array())
	{
		$this->outputVariables = $outputVariables;
		
		echo "<table>\n";

		echo self::INDENT_STR."<tr>\n";
		foreach($this->outputVariables as $varname)
		{
			echo self::INDENT_STR.self::INDENT_STR;
			echo "<th>".htmlspecialchars($varname)."</th>\n";
		}
		echo self::INDENT_STR."</tr>\n";
	}
	
	/**
	 * (non-PHPdoc)
	 * @see SPARQL_Result_Handler_Interface#onEndResults($caller)
	 */
	function onEndResults($caller)
	{
		echo "</table>\n";
	}
	
	/**
	 * (non-PHPdoc)
	 * @see SPARQL_Result_Handler_Interface#onFoundResult($caller, $record)
	 */
	function onFoundResult($caller, array $record)
	{
		echo self::INDENT_STR."<tr>\n";
		
		foreach($this->outputVariables as $varname)
		{
    		echo self::INDENT_STR.self::INDENT_STR;
    		echo "<td>".htmlspecialchars($record[$varname])."</td>\n";
    		
//			$value = htmlspecialchars($record[$varname]);
//			
//			if( isset($this->vars[$varname]['isdatatype']) )
//			{
//				$lang = empty($record[$varname.'_lang'])
//					? '' : ' xml:lang="'.$record[$varname.'_lang'].'"';
//					
//				$out .= "<literal$lang>" . htmlspecialchars($record[$varname]) . '</literal>';
//			} else
//			{
//				$out .= "<uri>$value</uri>";
//			}
//			
//			$out .= '</binding>' . "\n";
		}
		echo self::INDENT_STR."</tr>\n";
	}
	
	/**
	 * (non-PHPdoc)
	 * @see SPARQL_Result_Handler_Interface#onComment($commentString)
	 */
	function onComment($commentString)
	{
		// ignored at the moment
	}
	
}
?>