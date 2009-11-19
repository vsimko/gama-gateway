<?php
/**
 * Extracts the first value comming from the SPARQL engine.
 * It acts as an exception that throws itself when the first result appears.
 * 
 * Example:
 * -----------------------------------------------------
 * $engine = new SPARQL_Engine;
 * $engine->useSparql($sparqlQuery);
 * $engine->setResultHandler(new Single_Value_Extractor);
 * try
 * {
 *   $engine->runQuery();
 * } catch(Single_Value_Extractor $e)
 * {
 *   // do some stuff with the value $e->getFoundValue()
 * }
 * -----------------------------------------------------
 * 
 * @author Viliam Simko
 */
class Single_Value_Extractor extends Exception implements SPARQL_Result_Handler_Interface
{
	private $foundValue;

	public function getFoundValue()
	{
		return $this->foundValue;
	} 
	
	public function __construct()
	{
		// nothing
	}
	
	// =========================================================
	// from SPARQL_Result_Handler_Interface
	// =========================================================
	function onFoundResult($caller, array $record)
	{
		$this->foundValue = $record;
		throw $this;
	}
	function onBeginResults($caller, array $outputVariables, array $debugString = array()){}
	function onEndResults($caller){}
	function onComment($commentString){}
	// =========================================================
}

?>