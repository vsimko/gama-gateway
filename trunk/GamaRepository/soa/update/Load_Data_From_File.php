<?php

/**
 * Loads RDF statements from a file in the RDF/XML format.
 */
class Load_Data_From_File extends RPC_Service
{
	const TRIPLES_PER_DOT = 1000;
	const DOTS_PER_ROW = 100;
	
	/**
	 * Upload the RDF/XML file.
	 * @datatype file
	 * @required
	 */
	static $PARAM_FILE = 'file';

	/**
	 * Override this if you want to use different triple handler.
	 * @return unknown_type
	 */
	protected function createTripleHandler()
	{
		return new GAMA_Data_Loader;
	}
	
	/**
	 * Interactive output will inform the user about the progress.
	 */
	public function execute()
	{
		$file = $this->getParam(self::$PARAM_FILE, self::REQUIRED);
		
		if(!is_array($file))
		{
			throw new Exception('Uploaded file required');
		}
		
		try
		{
			$parser = RDF_Parser_Factory::getParserByLocation($file['tmp_name']);
		} catch(Exception $e)
		{
			echo "Could not parse the file: $file[tmp_name]. ".$e->getMessage()."\n";
			return;
		}
	
		dispatcher($parser)->attach( $parser );

		dispatcher($parser)->attach( $this );
		dispatcher($parser)->attach( $this->createTripleHandler() );
	
		ob_implicit_flush(true);
		@header('Content-type: text/plain');
		
		$this->numTriples = 0;
		$this->numDots = 0;
		
		echo "Processing triples from file $file[name]:\n  ";
		set_time_limit(0);
		dispatcher($parser)->onParse();
		$this->printProcessedTriples();
	}
	
	private $numTriples;
	private $numDots;
	
	public function onNewTriple($s, $p, $o)
	{
		if($this->numTriples % self::TRIPLES_PER_DOT == 0)
		{
			echo "o";
			$this->numDots++;
		}
		
		if($this->numDots >= self::DOTS_PER_ROW)
		{
			$this->printProcessedTriples();
			echo "  ";
			$this->numDots = 0;
		}

		$this->numTriples++;
	}
	
	private function printProcessedTriples()
	{
		echo " = $this->numTriples triples\n";
	}
}
?>