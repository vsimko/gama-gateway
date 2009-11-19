<?php
interface Parser_Interface
{
	/**
	 * Parse the document.
	 * 
	 * Constraints:
	 * - location must be defined prior to parsing
	 * - location must be valid
	 * - parser must be stopped first
	 */
	public function onParse();
		
	/**
	 * Force parser to stop.
	 */
	public function onStopParser();

	/**
	 * In general, the type of parser is determined by the document format.
	 * Changing the location will therefore require different instance of the
	 * parser. This change must be handled by the factory setting-up the
	 * parser. This method should be called only once before parsing.
	 * 
	 * @param string $location
	 */
	public function setLocation($location);
}
?>