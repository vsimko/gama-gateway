<?php
/**
 * Cached work titles.
 * @author Viliam Simko
 */
class Build_Cache_Work_Title extends RPC_Service
{
	/**
	 * The caching progress will be reported progressively.
	 */
	public function execute()
	{
		ob_implicit_flush(1);
		echo '<html><body><pre>';

		$caching = new Caching_Helper_Multi_Lang_Label(
			'http://gama-gateway.eu/schema/work_title',
			'http://gama-gateway.eu/cache/work_title' );
		
		$caching->startCaching();
		
		echo "all done.\n";
		echo '</pre></body></html>';
			}
}
?>