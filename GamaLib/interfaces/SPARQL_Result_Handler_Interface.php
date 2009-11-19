<?php
/**
 * Implement this interface in your class and pass it to the SPARQL_Engine
 * as a query handler. The engine will call the methods when calling the
 * underlying database layer.
 * 
 * @author Viliam Simko
 */
interface SPARQL_Result_Handler_Interface
{
	/**
	 * Called just before the SQL query is executed in the database.
	 * @param $caller
	 * @param $outputVariables
	 * @param $debugString
	 */
	function onBeginResults($caller, array $outputVariables, array $debugString = array());
	
	/**
	 * Called just after all the records have been fetched from the database.
	 * @param $caller
	 */
	function onEndResults($caller);
	
	/**
	 * Called on every record fetched from the database.
	 * @param $caller
	 * @param $record
	 */
	function onFoundResult($caller, array $record);
	
	/**
	 * Parsers may use this to pass comments to the renderers.
	 * @param $commentString
	 */
	function onComment($commentString);
}
?>