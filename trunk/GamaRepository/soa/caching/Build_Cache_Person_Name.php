<?php
/**
 * Prepares the property cache:person_name from the gama:person_name.
 * - Every person has exactly one instance of the name.
 * - No languages are used (same value for all languages).
 * - Higher priority for harmonised names.
 * 
 * @author Viliam Simko
 */
class Build_Cache_Person_Name extends RPC_Service
{
	/**
	 * Progress report as a simple text.
	 */
	public function execute()
	{
		ob_implicit_flush(1);
		echo '<html><body><pre>';

		$caching = new Caching_Helper_Label_Without_Language(
			'http://gama-gateway.eu/schema/person_name',
			'http://gama-gateway.eu/cache/person_name' );
		
		$caching->startCaching();
		
		echo "all done.\n";
		echo '</pre></body></html>';
	}
}
?>