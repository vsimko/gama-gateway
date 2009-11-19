<?php

/**
 * An instance of this class can be attached to the SPARQL engine
 * to handle results by generating the SPARQL Query Results XML Format.
 * @see http://www.w3.org/TR/rdf-sparql-XMLres/
 * 
 * @author viliam Simko
 */
class SPARQL_XML_Results_Renderer implements SPARQL_Result_Handler_Interface
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
				
		echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
		echo "<sparql xmlns=\"http://www.w3.org/2005/sparql-results#\">\n";

		foreach($debugString as $str)
		{
			$str = htmlspecialchars($str, ENT_NOQUOTES);
			
			// the replacement of '--' is necessary to buggy XML parsers
			$str = preg_replace('/(--)\s*(.*)\s*$/m', '/* \2 */', $str);
			
			echo "<!-- $str -->\n";
		}
		
		debug_time_measure(__CLASS__);
		
		// ------------------------------------
		echo self::INDENT_STR."<head>\n";
		foreach($this->outputVariables as $varname)
		{
			echo self::INDENT_STR.self::INDENT_STR;
			echo"<variable name=\"$varname\"/>\n";
		}
		echo self::INDENT_STR."</head>\n";
		// ------------------------------------

	    // start the <resutls> section
	    echo self::INDENT_STR."<results>\n";
	}
	
	/**
	 * (non-PHPdoc)
	 * @see SPARQL_Result_Handler_Interface#onEndResults($caller)
	 */
	function onEndResults($caller)
	{
		// end the <resutls> section
		echo self::INDENT_STR."</results>\n";
	    
		$renderTime = debug_time_measure(__CLASS__);
	    echo "<!-- render time: $renderTime seconds -->\n";
	    
		echo "</sparql>\n";
	}
	
	/**
	 * (non-PHPdoc)
	 * @see SPARQL_Result_Handler_Interface#onFoundResult($caller, $record)
	 */
	function onFoundResult($caller, array $record)
	{
		echo self::INDENT_STR.self::INDENT_STR."<result>\n";
		foreach($this->outputVariables as $varname)
		{
    		echo self::INDENT_STR.self::INDENT_STR.self::INDENT_STR;
			echo "<binding name=\"$varname\">";
			$value = htmlspecialchars($record[$varname]);
			
			// _dt suffix indicates that the value represents a literal with a datatype
			if( empty($record[$varname.'_dt']) )
			{
				echo "<uri>$value</uri>";
			} else
			{
				$lang = empty($record[$varname.'_lang'])
					? '' : ' xml:lang="'.$record[$varname.'_lang'].'"';
					
				echo "<literal$lang>" . htmlspecialchars($record[$varname]) . '</literal>';
			}
			
			echo "</binding>\n";
		}
    	echo self::INDENT_STR.self::INDENT_STR."</result>\n";
	}
	
	/**
	 * (non-PHPdoc)
	 * @see SPARQL_Result_Handler_Interface#onComment($commentString)
	 */
	function onComment($commentString)
	{
		echo "<!-- ".htmlspecialchars($commentString)." -->\n";
	}
}
?>