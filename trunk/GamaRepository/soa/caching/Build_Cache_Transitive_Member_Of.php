<?php
/**
 * Precomputed transitivity based on the gama:member_of property.
 */
class Build_Cache_Transitive_Member_Of extends RPC_Service
{
	/**
	 * Progress report
	 */
	public function execute()
	{
		ob_implicit_flush(1);
		@header('Content-type: text/plain');
				
		$resmgr = Resource_Manager::singleton();
		$store = GAMA_Store::singleton();
		
		$cachingGraphId = $store->getGraphID(Caching_Helper::CACHING_GRAPH_ID);
		
		$cacheTransitiveMemberOf = $resmgr->getResourceByUri(
			'http://gama-gateway.eu/cache/transitive_member_of');
		
		$gamaMemberOf = $resmgr->getResourceByUri(
			'http://gama-gateway.eu/schema/member_of');
		
		$store->sql("delete from {$cacheTransitiveMemberOf->getStmtTab()}");
		
		if($gamaMemberOf->isThisInverseMaster())
		{
			echo "Using data directly from the gama:member_of property...\n";
			$store->sql("
				insert ignore into {$cacheTransitiveMemberOf->getStmtTab()}
				(g, subject, object)
				select {$cachingGraphId}, subject, object from {$gamaMemberOf->getStmtTab()}
			");
		} else
		{
			echo "Using data from the gama:has_member property...\n";
			$store->sql("
				insert ignore into {$cacheTransitiveMemberOf->getStmtTab()}
				(g, subject, object)
				select {$cachingGraphId}, object, subject from {$gamaMemberOf->getInverseMaster()->getStmtTab()}
			");
		}
		
		do
		{
			$store->sql("
				insert ignore into {$cacheTransitiveMemberOf->getStmtTab()}
				(g, subject, object)
				select {$cachingGraphId}, a.subject, b.object
				from {$cacheTransitiveMemberOf->getStmtTab()} a
				join {$cacheTransitiveMemberOf->getStmtTab()} b
				on a.object = b.subject
			");
			
			$affectedRows = $store->sqlFetchValue("SELECT ROW_COUNT()");
			echo "Added another level of transitive closure: $affectedRows relations\n";
			
		} while( $affectedRows > 0 );
		
		echo "all done.\n";
	}
}
?>