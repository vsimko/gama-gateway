<?php

/**
 * Update schema from uploaded file.
 */
class Update_Schema_From_File extends RPC_Service
{
	/**
	 * Upload the schema file in RDF/XML format.
	 * @datatype file
	 * @required
	 */
	static $PARAM_SCHEMA_FILE = 'file';

	/**
	 * TODO: better comment
	 */
	public function execute()
	{
		$file = $this->getParam(self::$PARAM_SCHEMA_FILE, self::REQUIRED);
		
		if(!is_array($file))
		{
			throw new Exception('Uploaded file required');
		}
		
//		$file['name']
//		$file['type']
//		$file['tmp_name']
//		$file['error']
//		$file['size']
		
		$parser = RDF_Parser_Factory::getParserByLocation($file['tmp_name']);
	
		dispatcher($parser)->attach( $parser );

		dispatcher($parser)->attach( $this );
		dispatcher($parser)->attach( new GAMA_Schema_Loader );
	
		@header('Content-type: text/plain');
		
		$this->numTriples = 0;
		dispatcher($parser)->onParse();
		
		echo "Processed $this->numTriples triples from file $file[name]";
	}

	private $numTriples;
	public function onNewTriple($s, $p, $o)
	{
		$this->numTriples++;
	}
}
?>