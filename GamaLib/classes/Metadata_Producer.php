<?php

class Metadata_Producer
{
	/**
	 * @var Dispatcher
	 */
	public $dispatcher;
	
	/**
	 * @var string
	 */
	private $currentSubject;

	/**
	 * @var string
	 */
	private $currentDomain;

	/**
	 * @var Schema_Inferencer_Interface
	 */
	private $schemaInferencer;
	
	/**
	 * Schema should be loaded before starting the document.
	 *
	 * @param string $outputLocation
	 * @param string[optional] $baseUri
	 */
	public function beginDocument($outputLocation, $baseUri = null)
	{
		$this->dispatcher->onSetOutputLocation($outputLocation);
		$this->dispatcher->onSetBaseUri($baseUri);
		$this->dispatcher->onBeginWrite();
	}
	
	public function endDocument()
	{
		$this->dispatcher->onEndWrite();
	}
	
	public function flush()
	{
		$this->dispatcher->onFlushWriteBuffer();
	}
	
	/**
	 * Method-calls are mapped to triples according to loaded schema.
	 *
	 * @param string $name
	 * @param array $args
	 */
	public function __call($methodName, $args)
	{
		// TODO: check loaded schema
		$methodName = "gama:$methodName";
		
		$resourceInfo = $this->dispatcher->onGetInferredInfoByUri($name);
		
		// in case of a class
		if(isset($resourceInfo['type']['http://www.w3.org/2002/07/owl#Class']))
		{
			$this->addRdfType($args[0], $methodName);
		}
		
		// in case of a property
		elseif(isset($resourceInfo['domain']['http://www.w3.org/2000/01/rdf-schema#domain']))
		{
			if(isset($resourceInfo['type']['http://www.w3.org/2002/07/owl#DatatypeProperty']))
			{
				$this->addStatement($methodName, new RDFS_Literal($args[0], @$args[1])); //lang=$args[1] is optional
			} else
			{
				$this->addStatement($methodName, $args[0]);
			}
		}
		
		// unexpected element in the context
		else
		{
			throw new ErrorException('WARNING: Unexpected element "'.$methodName.'" in context of subject "'.$this->currentDomain.'"');
		}
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
		$this->dispatcher->onNewTriple($uri, 'rdf:type', $type);
	}
	
	/**
	 * Adds tripple relative to current subject.
	 * @param string $predicate
	 * @param string|RDFS_Literal $object
	 */
	private function addStatement($predicate, $object)
	{
		$this->dispatcher->onNewTriple($this->currentSubject, $predicate, $object);
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