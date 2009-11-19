<?php
/**
 * Creators of works with removed duplicates based on sameAs property.
 */
class Build_Cache_Work_Creator extends RPC_Service
{
	const MYURI = 'http://gama-gateway.eu/cache/work_creator';

	/**
	 * Progress report
	 */
	public function execute()
	{
		ob_implicit_flush(1);

		$rpcclient = $this->getRpcClient();
				
		// get the S_* tbale name of cache:work_creator property
		$myTable = $rpcclient->{'query/Get_Table_Name_By_Uri'}(self::MYURI);
		
		// get the S_* table name of owl:sameAs property 
		$owlSameAsTable = $rpcclient->{'query/Get_Table_Name_By_Uri'}(GAMA_Store::OWL_SAME_AS_URI);
		
		// convert SPARQL to SQL
		$subSql = $rpcclient->{'query/Sparql_To_Sql'}('
			PREFIX gama: <http://gama-gateway.eu/schema/>
			select distinct ?wid ?pid {
			  ?w a gama:Work.
			  ?p a gama:Person.
			  ?w gama:has_creator ?p
			  FILTER gama:dbAlias(?w,"id",?wid)
			  FILTER gama:dbAlias(?p,"id",?pid)
			}
		');
			
		// fill the cache using teporary table operations
		$sql = "
			drop temporary table if exists TMPCACHE;
			
			create temporary table TMPCACHE
			(
			 wid int not null,
			 pid int not null,
			 eqmaster int default null,
			 primary key(wid,pid),
			 key(pid),
			 key(eqmaster)
			);
			
			insert into TMPCACHE (wid,pid) select wid, pid from ($subSql) as x;
			
			-- eqmaster will be marked from owl:sameAs
			update TMPCACHE, $owlSameAsTable set eqmaster=object where pid=subject;
			
			insert into $myTable (subject, object)
			select wid, pid from TMPCACHE
			where eqmaster is null or pid = eqmaster;
			
			drop table temporary TMPCACHE;
		";
		
		// =========================================================================
		debug_time_measure(__METHOD__);

		echo $rpcclient->{'update/Cleanup_Property_Table'}(self::MYURI);
		GAMA_Store::singleton()->sql($sql);

		$timeTaken = debug_time_measure(__METHOD__);
		echo "Caching took $timeTaken seconds\n";
		// =========================================================================
		
		echo "all done.\n";
	}
}
?>