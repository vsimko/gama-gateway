<?php
/**
 * The following steps will be performed:
 * - data and schema cleanup
 * - schema definition from FTP
 * - data ingest from FTP
 * - similarities ingest from FTP
 * - caching properties
 * - initialisation of tagcloud structures
 * 
 * [gama.sync.dir]/Update directory will be used.
 *
 * @author Viliam Simko
 */
class Sync_Rebuild_Repository extends RPC_Service
{
	private $ingestDir;
	private $keepItReal = true;
	
	/**
	 * Reports the rebuild progress to the output.
	 */
	public function execute()
	{
		$this->ingestDir = Config::get('gama.sync.dir').'/Update';
		
		ob_implicit_flush(true);
		@header('Content-type: text/plain');
		
		set_time_limit(0); // disable execution timeout during the loading process
		
		app_lock(LOCK_EX);
			$this->doCopyExternalConfig();
			$this->doCleanup();
			//$this->doRebuildTagcloud();
			$this->doLoadSchema();
			$this->doLoadData();
			$this->doLoadSimilarities();
			$this->doRebuildCaches();
		app_unlock();
	}
	
	private function showAvailableDirs()
	{
		echo "Generating the list of avaiable dirs:\n";
		
		foreach(glob(Config::get('repo.rebuild.dir').'/*') as $filename)
		{
			echo basename($filename)."\n";
		}
	}
	
	private function doCopyExternalConfig()
	{
		echo "\nCopying external configuration  ...\n";
		
		$srcIdxConfigFile = Config::get('gama.sync.dir').'/Update/indexing/config.php';
		$dstIdxConfigFile = Config::get('gama.idxconfig');

		if(!file_exists($srcIdxConfigFile))
		{
			throw new Exception("File not found: $srcIdxConfigFile");
		}
			
		if($this->keepItReal)
		{
			if(!@copy($srcIdxConfigFile, $dstIdxConfigFile))
			{
				throw new Exception(
					"Could not copy '$srcIdxConfigFile' to '$dstIdxConfigFile'. ".
					"Make sure you have read and write permissions for these files and dirs.");
			}
		}
		echo "Succesfully copied '$srcIdxConfigFile' to '$dstIdxConfigFile'.\n";
	}
	
	private function doCleanup()
	{
		echo "\nCleaning the repository data and schema ...\n";
		if($this->keepItReal)
		{
			GAMA_Store::singleton()->rebuildStore();
		}
		echo "Cleanup performed successfully\n";
	}
	
	private function doLoadSchema()
	{
		echo "\nLoading metadata schemas (this may take 60 seconds)...\n";
		if($this->keepItReal)
		{
			foreach(glob($this->ingestDir.'/schema/*') as $filename)
			{
				$file = array(
					'name' => $filename,
					'tmp_name' => $filename
					);
					
				echo $this->getRpcClient()->{'schema/Update_Schema_From_File'}(
						array( 'file' => $file) )."\n";
			}
		}
		echo "All schemas loaded\n";
	}
	
	private function doLoadData()
	{
		echo "\nLoading data (just be patient)...\n";
		if($this->keepItReal)
		{
			$srcfiles = array_merge(
				(array) glob($this->ingestDir.'/schema/*'),
				(array) glob($this->ingestDir.'/vocabulary/*'),
				(array) glob($this->ingestDir.'/harmonisation/*'),
				(array) glob($this->ingestDir.'/dba/*'),
				(array) glob($this->ingestDir.'/indexing/*')
				);
			
			foreach($srcfiles as $filename)
			{
				if(is_file($filename))
				{
					$file = array(
						'name' => $filename,
						'tmp_name' => $filename
						);

					$this->getRpcClient()->callPassthruService(
						'update/Load_Data_From_File',
						array( 'file' => $file) );
				}
			}
		}
		echo "All data loaded\n";
	}
	
	private function doLoadSimilarities()
	{
		echo "\nLoading similarities (just be patient)...\n";
		if($this->keepItReal)
		{
			foreach( (array) glob($this->ingestDir.'/similarities/*') as $filename)
			{
				$file = array(
					'name' => $filename,
					'tmp_name' => $filename
					);
					
				$this->getRpcClient()->callPassthruService(
					'update/Load_Similarities_From_File',
					array( 'file' => $file) );
			}
		}
		echo "All similarities loaded\n";
	}

	private function doRebuildCaches()
	{
		echo "\nRebuilding caches (this may take 20 seconds)...\n";
		if($this->keepItReal)
		{
			$this->getRpcClient()->callPassthruService('caching/Build_Cache_Person_Biography');
			$this->getRpcClient()->callPassthruService('caching/Build_Cache_Person_Name');
			$this->getRpcClient()->callPassthruService('caching/Build_Cache_Person_Birth_Year');
			$this->getRpcClient()->callPassthruService('caching/Build_Cache_Person_Url');
			$this->getRpcClient()->callPassthruService('caching/Build_Cache_Transitive_Member_Of');
			$this->getRpcClient()->callPassthruService('caching/Build_Cache_Work_Created_Year');
			$this->getRpcClient()->callPassthruService('caching/Build_Cache_Work_Creator');
			$this->getRpcClient()->callPassthruService('caching/Build_Cache_Work_Description');
			$this->getRpcClient()->callPassthruService('caching/Build_Cache_Work_Has_Fullstream');
			$this->getRpcClient()->callPassthruService('caching/Build_Cache_Work_Has_Preview');
			$this->getRpcClient()->callPassthruService('caching/Build_Cache_Work_Title');
			$this->getRpcClient()->callPassthruService('caching/Build_Cache_Fulltext_Works');
			$this->getRpcClient()->callPassthruService('caching/Build_Cache_Work_Sorting');
		}
		echo "Caches are ready\n";
		
	}
	
	private function doRebuildTagcloud()
	{
		echo "\nRebuilding the tagcloud ...\n";
		
		if($this->keepItReal)
		{
			$this->getRpcClient()->{'tagcloud/Init_Tagcloud_Structure'}();
		}
		echo "Tagcloud is ready\n";
	}
}
?>