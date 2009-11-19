<?php
/**
 * Works that have main manifestations with a preview.
 * gama:has_manifestation.(gama:idx_main=true & gama:idx_stream_avail = 1)
 * @author Viliam Simko
 */
class Build_Cache_Work_Has_Preview extends RPC_Service
{
	/**
	 * The caching progress will be reported progressively.
	 */
	public function execute()
	{
		ob_implicit_flush(1);
		@header('Content-type: text/plain');
		
		$caching = new Caching_Helper_Column_From_Sparql(
			'http://gama-gateway.eu/cache/work_has_preview' );

		$caching->startCaching();
		
		$caching->buildBooleanColumn('object', '
			PREFIX gama: <http://gama-gateway.eu/schema/>
			select * {
				_:m gama:manifestation_of ?uri.
				_:m gama:idx_main "1".
				_:m gama:idx_stream_avail ?avail.
				FILTER gama:inList(?avail, 1,2 ).
			}
		');
		
		echo "all done.\n";
	}
}
?>