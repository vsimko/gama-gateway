<?php
/**
 * @author Viliam Simko
 */
class Build_Cache_Person_Biography extends RPC_Service
{
	/**
	 * The caching progress will be reported progressively.
	 */
	public function execute()
	{
		ob_implicit_flush(1);
		@header('Content-type: text/plain');
		
		$caching = new Caching_Helper_Multi_Lang_Aggreg_String(
			'http://gama-gateway.eu/schema/biography',
			'http://gama-gateway.eu/cache/biography' );
		
		$caching->startCaching();
		
		echo "all done.\n";
	}
}
?>