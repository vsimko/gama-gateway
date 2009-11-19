<?php

/**
 * TODO: finish the new SPARQL aggregator
 * ciant:archive:uri =>
	"archive_name"	=> "CIANT"
	"works" =>
		workuri1 =>
			"work_title" => "VIRUS"
			"work_description" => "some descr"
		workuri2 =>
			"work_uri" => "workuri2"
			"work_title" => "GOLEM"
			"work_description" => "some descr"
	"archive_homepage" => "www.ciant.cz"

[archive] => "archive_name" => name + ", "
[archive] => "archive_homepage" => homepage + ", "

[fromarch] => "works" => [workuri] => "work_uri" => workuri
[fromarch] => "works" => [workuri] => "work_title" => title + ",\n"
[fromarch] => "works" => [workuri] => "work_description" => descr + ",\n"

array( "sid", "fromarch" )
array( "idx", "works" )
array( "sid", "workuri" )
array( "idx", "work_title" )
array( "val", "title", ",\n" )

 * @author Viliam Simko
 */
class SPARQL_Aggregator implements SPARQL_Result_Handler_Interface
{
	private $indexVars;
	private $varContainingValue;
	public $aggregatedResult = array();
	
	/**
	 * (non-PHPdoc)
	 * @see SPARQL_Result_Handler_Interface#onBeginResults($caller)
	 */
	function onBeginResults($caller, array $outputVariables, array $debugString = array())
	{
		$this->indexVars = array_slice($outputVariables, 0,-1);
		$this->varContainingValue = end($outputVariables);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see SPARQL_Result_Handler_Interface#onEndResults($caller)
	 */
	function onEndResults($caller)
	{
		// nothing
	}
	
	/**
	 * (non-PHPdoc)
	 * @see SPARQL_Result_Handler_Interface#onFoundResult($caller, $record)
	 */
	function onFoundResult($caller, array $record)
	{
		$ref = & $this->aggregatedResult;
		foreach($this->indexVars as $varname)
		{
			$ref = & $ref[$varname][];
		}
		$ref = $record[$this->varContainingValue];
	}
	
	/**
	 * (non-PHPdoc)
	 * @see SPARQL_Result_Handler_Interface#onComment($commentString)
	 */
	function onComment($commentString)
	{
		// nothing
	}
}
?>