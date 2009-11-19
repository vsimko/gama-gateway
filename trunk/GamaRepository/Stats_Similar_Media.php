<?php
class Stats_Similar_Media extends Stats_Base
{
	public $numSimilarMedia;
	
	public function reloadStats()
	{
		$this->cleanupStats();
		$store = GAMA_Store::singleton();
		
		$this->numSimilarMedia = $store->sqlFetchValue('select count(*) from SIMILARITY');
	}
}

?>