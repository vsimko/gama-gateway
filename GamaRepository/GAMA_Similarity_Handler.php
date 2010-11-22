<?php

/**
 * Write information about similar manifestations into the repository.
 * TODO: the current implementation requires the triples to be placed one after another
 */
class GAMA_Similarity_Handler implements Triple_Handler_Interface, Report_Unknown_Resource_Interface
{
	// =========================================================================
	// Event handlers
	// =========================================================================
	
	/**
	 * @see Triple_Handler_Interface::onBeginDocument()
	 */
	public function onBeginDocument()
	{
		$this->lastSubject = null;
		$this->buffer = array();
	}
	
	/**
	 * @see Triple_Handler_Interface::onEndDocument()
	 */
	public function onEndDocument()
	{
		$this->buffer2frame();
	}
	
	/**
	 * @see Triple_Handler_Interface::onNewTriple()
	 * @param string $s
	 * @param string $p
	 * @param string|RDFS_Label $o
	 */
	public function onNewTriple($s, $p, $o)
	{
		if($this->lastSubject != $s)
		{
			if(!empty($this->lastSubject))
			{
				$this->buffer2frame();
			}
			$this->lastSubject = $s;
		}
		$this->buffer[$p] = $o;
	}
	
	/**
	 * @param string $main URI of the main manifestation
	 * @param string $similar URI of the similar manifestation
	 * @param RDFS_Literal $shotid
	 * @param RDFS_Literal $weight
	 */
	public function onNewFrame($main, $similar, RDFS_Literal $shotid, RDFS_Literal $weight, RDFS_Literal $best_match)
	{
		//echo "NEW FRAME: $main, $similar, $shotid, $weight, $best_match\n";
		
		$store = GAMA_Store::singleton();
		
		$manifId = $store->sqlFetchValue('select id from RESOURCE where uri=?', $main);
		if(!$manifId)
		{
			throw new Exception('Unknown URI or main manifestation:'.$main);
		}
		
		$smanifId = $store->sqlFetchValue('select id from RESOURCE where uri=?', $similar);
		if(!$smanifId)
		{
			throw new Exception("Unknown URI of similar manifestation: <$similar>");
		}
				
		if( !is_numeric($shotid->value))
		{
			throw new Exception('Expected integer value as shot ID');
		}
		
		if(!is_numeric($weight->value))
		{
			throw new Exception('Expected integer value 0..255 as weight');
		}
		
		if(!is_numeric($best_match->value))
		{
			throw new Exception('Expected integer value 0..255 as best_match');
		}
		
		if($this->isDeleteMode)
		{
			GAMA_Store::singleton()->sql(
				'delete from SIMILARITY where manif=? and smanif=? and shotid=?',
				$manifId, $smanifId, $shotid->value );
		} else
		{
			GAMA_Store::singleton()->sql(
				'insert ignore into SIMILARITY (manif, smanif, shotid, weight, bestmatch) values (?,?,?,?,?)',
				$manifId, $smanifId, $shotid->value, $weight->value,$best_match->value );
		}
	}
	
	/**
	 * @param boolean $isDeleteMode
	 */
	public function onSetDeleteMode($isDeleteMode = true)
	{
		echo 'Turning '.($isDeleteMode ? 'ON' : 'OFF')." delete mode\n";
		$this->isDeleteMode = $isDeleteMode;
	}
	
	/**
	 * @param string $uri
	 */
	public function onUnknownClass($uri)
	{
		echo "  Unknown class detected: $uri\n";
	}
	
	/**
	 * @param string $uri
	 */
	public function onUnknownProperty($uri)
	{
		echo "  Unknown property detected: $uri\n";
	}
	
	// =========================================================================
	// Other stuff
	// =========================================================================
	
	/**
	 * @var string
	 */
	private $lastSubject;
	
	/**
	 * @var array
	 */
	private $buffer = array();
	
	/**
	 * @var boolean
	 */
	private $isDeleteMode = false;
	
	/**
	 * Create a new frame from the buffer.
	 */
	private function buffer2frame()
	{
		try {
			dispatcher()->onNewFrame(
				$this->buffer['http://gama-gateway.eu/schema/main_manif'],
				$this->buffer['http://gama-gateway.eu/schema/similar_manif'],
				$this->buffer['http://gama-gateway.eu/schema/shotid'],
				$this->buffer['http://gama-gateway.eu/schema/weight'],
				$this->buffer['http://gama-gateway.eu/schema/best_match']
				);
		} catch(Exception $e) {
			echo "EXCEPTION: $e";
		}
	}
}

?>