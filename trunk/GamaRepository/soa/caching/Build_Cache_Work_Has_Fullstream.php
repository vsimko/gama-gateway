<?php
/**
 * Works that have main manifestations with a full stream video.
 * gama:has_manifestation.(gama:idx_main=true & gama:idx_stream_avail = 2)
 * @author Viliam Simko
 */
class Build_Cache_Work_Has_Fullstream extends RPC_Service
{
	/**
	 * The caching progress will be reported progressively.
	 */
	public function execute()
	{
		ob_implicit_flush(1);
		@header('Content-type: text/plain');
		
		$caching = new Caching_Helper_Column_From_Sparql(
			'http://gama-gateway.eu/cache/work_has_fullstream' );

		$caching->startCaching();
		
		$caching->buildBooleanColumn('object', '
			PREFIX gama: <http://gama-gateway.eu/schema/>
			select * {
				_:m gama:manifestation_of ?uri.
				_:m gama:idx_main "1".
				_:m gama:idx_stream_avail "2"
			}
		');
		
		echo "all done.\n";
	}
}
?>