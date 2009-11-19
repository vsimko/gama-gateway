<?php
/**
 * @author Viliam Simko
 */
class Build_Cache_Work_Description extends RPC_Service
{
	/**
	 * The caching progress will be reported progressively.
	 */
	public function execute()
	{
		ob_implicit_flush(1);
		@header('Content-type: text/plain');
		
		$caching = new Caching_Helper_Multi_Lang_String(
			'http://gama-gateway.eu/schema/work_description',
			'http://gama-gateway.eu/cache/work_description' );
		
		$caching->startCaching();
		
		echo "all done.\n";
	}
}
?>