<?php

/**
 * Writes RDF files in the RDF/XML format.
 * Uses the dispatcher framework to deliver and listen on events.
 */
class RDF_XML_Writer implements Writer_Interface
{
	// =========================================================================
	// Event handlers
	// =========================================================================
	
	/**
	 * @see Writer_Interface::onBeginWrite()
	 */
	public function onBeginWrite()
	{
		if(!empty($this->xml))
			throw new Exception('The document is not finished');

		// get the namespace manager which is attached to the dispatcher
		$this->nsmgr = dispatcher()->onGetNamespaceManager();
		if(empty($this->nsmgr))
			throw new Exception('No namespace manager. Please attach one to the dispatcher.');
				
		// rdf prefix must always be connected with the correct namespace
		if($this->nsmgr->resolveUri('rdf:RDF') != 'http://www.w3.org/1999/02/22-rdf-syntax-ns#RDF')
			throw new Exception('The "http://www.w3.org/1999/02/22-rdf-syntax-ns" namespace must be defined as "rdf" prefix');
		
		// initialization of internal structures
		$this->triples = null;
		$this->xml = new XMLWriter();
		
		// provided you write to the same file, the filesize() would always
		// report the same size without following call
		clearstatcache(); 
		
		$this->xml->openUri($this->outputLocation);
		$this->xml->setIndent(true);
		$this->xml->setIndentString("  ");

		$this->xml->startDocument('1.0', 'UTF-8');
		$this->xml->startElement('rdf:RDF');
		
		if(! empty($this->baseUri))
		{
			$this->xml->writeAttribute('xml:base', $this->baseUri);

			// use baseUri as DEFAULT NAMESPACE
			// REMOVED on 2009-03-29 because we switched to a URI format which contains lots of collons
			// A typical URI in GAMA could be "gama:argos:main:Manifestation:123"
			// so we might want to use: PREFIX x: <gama:argos:main:Manifestation:> and the same also in the base URI
			// assert('/* in this case baseUri must end with "/" or "#" because it will also be a namespace */ preg_match("/[\/#]\$/", $this->baseUri)');
			dispatcher()->onNewNamespace(null, $this->baseUri);
		}
		
		// namespaces from Namespace_Manager as attributes of the root element
		foreach($this->nsmgr->getNamespaces() as $prefix => $ns)
			$this->xml->writeAttribute(empty($prefix) ? 'xmlns' : "xmlns:$prefix", $ns);
	}
	
	/**
	 * @see Writer_Interface::onEndWrite()
	 */
	public function onEndWrite()
	{
		dispatcher()->onFlushWriteBuffer();
		$this->xml->endElement();
		$this->xml->endDocument();
		
		unset($this->xml);
		unset($this->nsmgr);
		unset($this->triples);
	}
		
	/**
	 * @see Writer_Interface::onFlushWriteBuffer()
	 */
	public function onFlushWriteBuffer()
	{
		foreach((array)$this->triples as $s_uri => $s)
		{
			$this->xml->startElement('rdf:Description');
			$this->xml->writeAttribute('rdf:about', $s_uri);

			foreach((array) $s as $p_uri => $p)
			{
				$p_uri = $this->nsmgr->resolvePrefix($p_uri); // try using prefixed form
				foreach((array)$p as $o)
				{
					$this->xml->startElement($p_uri);
					if($o instanceof RDFS_Literal)
					{
						if(!empty($o->lang))
							$this->xml->writeAttribute('xml:lang', $o->lang);
						$this->xml->text($o->value); // must be called after attributes have been written
					} else
					{
						$this->xml->writeAttribute('rdf:resource', $o);
					}
					$this->xml->endElement();
				}
			}
			$this->xml->endElement();
		}
		$this->triples = null;
		$this->xml->flush();
	}
			
	/**
	 * @see Triple_Handler_Interface::onNewTriple()
	 */
	public function onNewTriple($subject, $predicate, $object)
	{
		$subject = $this->nsmgr->resolveUri($subject);
		$predicate = $this->nsmgr->resolveUri($predicate);

		//string should represent URI, otherwise it should be RDFS_Literal
		if(is_string($object))
			$object = $this->nsmgr->resolveUri($object);

		$this->triples[$subject][$predicate][] = $object;
	}
	
	// =========================================================================
	// Other stuff
	// =========================================================================
		
	/**
	 * Use php://output if you want to write immediately to the output buffer.
	 * Do not use php://stdout because it works only in PHP-CLI mode.
	 * @param string $outputLocation
	 */
	public function setOutputLocation($outputLocation)
	{
		$this->outputLocation = $outputLocation;
	}
	
	/**
	 * The baseUri
	 * @param string $baseUri
	 */
	public function setBaseUri($baseUri)
	{
		$this->baseUri = $baseUri;
	}
	
	/**
	 * Data will be written here.
	 * @var string
	 */
	private $outputLocation;

	/**
	 * URIs without the namespace will use baseUri as their namespace
	 * @var string
	 */
	private $baseUri;
	
	/**
	 * Private instance of the Namespace_Manager otained from the dispatcher.
	 * @var Namespace_Manager
	 */
	private $nsmgr;
	
	/**
	 * Instance of the XMLWriter.
	 * @var XMLWriter
	 */
	private $xml;
	
	/**
	 * RDF buffer contains triples indexed by subject => predicate => object.
	 * @var array
	 */
	private $triples;
}

?>