<?php

/**
 * This class provides facilities to write metadata
 * in an RDF/XML format according to the GAMA Metadata Schema.
 * 
 * Methods will be created dynamically from the schema definition
 * and will be composed only of [0-9a-zA-Z_] characters.
 * Other characters will be converted to underscore '_'.
 * 
 * Example usage:
 * $w = new GAMA_Metadata_Writer();
 * $w->loadSchema('gama-schema.owl');
 * $w->beginDocument('php://output', 'http://www.ama-nt.cz/');
 * 
 *   $w->Archive('AMANT');
 *     $w->archive_name('AMANT - Experimental archive of video-art');
 *     $w->archive_name('AMANT - Experimentální archív videoartu', 'cs');
 * 
 *   $w->flush();
 * 
 *   $w->Person('CIANT');
 *     $w->person_name('CIANT - International Centre for Art and new Technologies');
 *     $w->is_owner('AMANT');
 * 
 * $w->endDocument();
 */
class GAMA_Metadata_Writer
{
	const GAMA_NAMESPACE = 'http://gama-gateway.eu/schema/';
	
	/**
	 * @var string
	 */
	private $currentSubject;

	/**
	 * @var string
	 */
	private $currentDomain;
	
	/**
	 * @var OWL_Inferencer
	 */
	private $owlInferencer;
	
	/**
	 * @var RDF_XML_Writer
	 */
	private $rdfxmlwriter;
	
	public function __construct()
	{
		// TODO: check loaded schema
		$this->rdfxmlwriter = new RDF_XML_Writer;
		dispatcher($this)->attach($this->rdfxmlwriter);
	}
	
	/**
	 * Loads schema definition from file.
	 * Format of the file will be detected and appropriate
	 * RDF_Parser will be selected automatically.
	 *
	 * @param string $location
	 */
	public function loadSchema($location)
	{
		$parser = RDF_Parser_Factory::getParserByLocation($location);
		$this->owlInferencer = new OWL_Inferencer;
		$nsManager = new Namespace_Manager;
		
		// dispatcher used for parsing the schema stored in OWL file
		dispatcher($parser)->attach($parser);
		dispatcher($parser)->attach($this->owlInferencer);
		dispatcher($parser)->attach($nsManager);
		dispatcher($parser)->onParse();
				
		// this dispatcher is used for writing
		dispatcher($this)->attach($nsManager);
		
		// always add the GAMA namespace
		dispatcher($this)->onNewNamespace('gama', self::GAMA_NAMESPACE);
		
		debug("Schema loaded successfully from: $location");
	}
		
	/**
	 * Schema should be loaded before starting the document.
	 *
	 * @param string $outputLocation
	 * @param string[optional] $baseUri
	 */
	public function beginDocument($outputLocation, $baseUri = null)
	{
		$this->rdfxmlwriter->setOutputLocation($outputLocation);
		$this->rdfxmlwriter->setBaseUri($baseUri);
		
		dispatcher($this)->onBeginWrite();
	}
	
	public function endDocument()
	{
		// TODO: check started document
		dispatcher($this)->onEndWrite();
	}
	
	public function flush()
	{
		// TODO: check started document
		dispatcher($this)->onFlushWriteBuffer();
	}
	
	/**
	 * Method-calls are mapped to triples according to loaded schema.
	 *
	 * @param string $name
	 * @param array $args
	 */
	public function __call($name, $args)
	{
		// TODO: check loaded schema
		$name = "gama:$name";
		
		$r = $this->owlInferencer->getInferredByUri($name);
		//$r = dispatcher($this)->onGetInferredByUri($name);
		
		// in case of a class
		if(isset($r['type']['http://www.w3.org/2002/07/owl#Class']))
		{
			$this->addRdfType($args[0], $name);
		}
		
		// in case of a property
		elseif(isset($r['domain']['http://www.w3.org/2000/01/rdf-schema#domain']))
		{
			if(isset($r['type']['http://www.w3.org/2002/07/owl#DatatypeProperty']))
				$this->addStatement($name, new RDFS_Literal($args[0], @$args[1])); //lang=$args[1] is optional
			else
				$this->addStatement($name, $args[0]);
		}
		
		// unexpected element in the context
		else throw new ErrorException('WARNING: Unexpected element "'.$name.'" in context of subject "'.$this->currentDomain.'"');
	}

	/**
	 * Adds triple rdf:type and sets current subject.
	 * @param string $uri
	 * @param string $type
	 */
	private function addRdfType($uri, $type)
	{
		$this->currentSubject = $uri;
		$this->currentDomain = $type;
		dispatcher($this)->onNewTriple($uri, 'rdf:type', $type);
	}
	
	/**
	 * Adds tripple relative to current subject.
	 * @param string $predicate
	 * @param string|RDFS_Literal $object
	 */
	private function addStatement($predicate, $object)
	{
		dispatcher($this)->onNewTriple($this->currentSubject, $predicate, $object);
	}
	
	/**
	 * @param string $subject
	 * @param string $predicate
	 * @param string|RDFS_Literal $object
	 */
	public function addTriple($subject, $predicate, $object)
	{
		if($this->currentSubject != $subject)
		{
			$this->flush();
			$this->currentSubject = $subject;
		}
		$this->addStatement($predicate, $object);
	}
	
}

?>