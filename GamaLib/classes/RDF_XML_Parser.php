<?php

require_once 'Parser_Interface.php';
require_once 'RDFS_Literal.php';

/**
 * Parses the RDF/XML stream in normal and/or abbreviated form.
 */
class RDF_XML_Parser implements Parser_Interface
{
	// =========================================================================
	// Event handlers
	// =========================================================================
	
	/**
	 * @see Parser_Interface::onParse()
	 */
	public function onParse()
	{
		if(empty($this->location))
		{
			throw new Exception('Set the location first');
		}
		
		if(!empty($this->xmlParser))
		{
			throw new Exception('The parser has already started.');
		}
		
		dispatcher()->onBeginDocument();
		
		// Set up the XML parser with namespaces support
		$this->xmlParser = xml_parser_create_ns('UTF-8', ''); // do not add extra separator
		xml_set_object($this->xmlParser, $this);
		xml_parser_set_option($this->xmlParser, XML_OPTION_CASE_FOLDING, false);
		xml_parser_set_option($this->xmlParser, XML_OPTION_TARGET_ENCODING, 'UTF-8');
		xml_set_character_data_handler($this->xmlParser, 'xmlCData');
		xml_set_element_handler($this->xmlParser,'xmlStartElement', 'xmlEndElement');
		xml_set_start_namespace_decl_handler($this->xmlParser, 'xmlStartNS');
		
		// http://bugs.php.net/bug.php?id=30834 (Namespace end handler is not called)
		// not needed anyway: xml_set_end_namespace_decl_handler($xmlParser, 'xmlEndNS');
		
		// XML is read from the stream block-by-block and parsed
		$fh = gzopen($this->location, 'r');
		while (!feof($fh))
		{
			$chunk = @fread($fh, self::READ_BLOCK_SIZE);
			// don't use utf8_encode here. the parser would not fail on illegal
			// characters, however it would produce wrong output.
			if (!xml_parse($this->xmlParser, $chunk, feof($fh)))
			{
				fclose($fh);
				$xmlerrcode = xml_get_error_code($this->xmlParser);
				
				// hacked PHP function
				$xmlspecialerrstr = array(68 => 'CDATA section not properly escaped e.g. "&" should be "&amp;"');
				if(! $xmlerrstr = @$xmlspecialerrstr[$xmlerrcode])
				{
					$xmlerrstr = xml_error_string($xmlerrcode);
				}
				
				throw new Exception(
					'XML error: ' . $xmlerrstr .
					' in ' . $this->location .
					' line ' . xml_get_current_line_number($this->xmlParser)
					);
			}
		}
				
		// releasing allocated resources
		fclose($fh);
		
		if($this->xmlParser)
		{
			xml_parser_free($this->xmlParser);
			unset($this->xmlParser);
		}
			
		dispatcher()->onEndDocument();
	}
	
	/**
	 * @see Parser_Interface::onStopParser()
	 */
	public function onStopParser()
	{
		xml_parser_free($this->xmlParser);
		unset($this->xmlParser);
	}
	
	// =========================================================================
	// Other stuff
	// =========================================================================
	
	/**
	 * @var string
	 */
	private $location;
	
	/**
	 * @see Parser_Interface::setLocation()
	 * @param string $location
	 */
	public function setLocation($location)
	{
		assert('/* cannot set the location after the parser has started */ empty($this->xmlParser)');
		$this->location = $location;
	}
	
	// ==============
	// RDF-parser
	// ==============
	
	const READ_BLOCK_SIZE = 1024;
	
	const RDF_DESCRIPTION = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#Description';
	const RDF_RDF = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#RDF';
	const RDF_ABOUT = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#about';
	const RDF_ID = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#ID';
	const RDF_RESOURCE = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#resource';
	const RDF_TYPE = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type';

	const IDX_PREDICATE = 0;
	const IDX_SUBJECT = 1;
	
	/**
	 * RDF-parser-related stack.
	 * array( array(self::IDX_SUBJECT|self::IDX_PREDICATE, uri) )
	 * @var array
	 */
	private $rdfstack = array();
		
	// ==============
	// XML-parser
	// ==============
	
	/**
	 * Reference to the XML parser.
	 * @var boolean
	 */
	private $xmlParser = null;
	
	/**
	 * XML-parser-related buffer.
	 * Collects CDATA of an element.
	 * @var string 
	 */
	private $xmlBuffer = null;

	/**
	 * XML-pareser-related flag.
	 * @var boolean
	 */
	private $nestedElement = false;
	
	/**
	 * Found namespace prefixes
	 * @var array
	 */
	private $nsp;
	
	/**
	 * Value of the xml:base attribute of the XML root element.
	 * @var string
	 */
	private $baseUri;
	
	/**
	 * SAX-parser callback function.
	 * @param resource $parser
	 * @param string $prefix
	 * @param string $uri
	 */
	private function xmlStartNS($parser, $prefix, $uri)
	{
		// ignore redefinition of a prefix
		if(empty($this->nsp[(string) $prefix]))
		{
			$this->nsp[(string) $prefix] = true;
			dispatcher()->onNewNamespace($prefix, $uri);
		}
	}
	
	/**
	 * SAX-parser callback function.
	 * @param resource $parser
	 * @param string $name
	 * @param array $attrs
	 */
	private function xmlStartElement($parser, $name, $attrs)
	{
		// begin of xml-related stuff
		$this->nestedElement = false;
		$this->xmlBuffer = null;
		// end of xml-related stuff
		
		if($name == self::RDF_DESCRIPTION)
		{
			if(isset($attrs[self::RDF_ABOUT]))
			{
				$this->rdfstack[] = array(self::IDX_SUBJECT, $attrs[self::RDF_ABOUT]);
			} elseif(isset($attrs[self::RDF_ID]))
			{
				$this->rdfstack[] = array(self::IDX_SUBJECT, $attrs[self::RDF_ID]);
			} else
			{
				throw new Exception('The "Description" element must contain "about" or "ID" attribute');
			}
		} elseif($name == self::RDF_RDF)
		{
			$this->baseUri = @$attrs['http://www.w3.org/XML/1998/namespacebase'];
			dispatcher()->onBaseUriFound($this->baseUri);
			$this->rdfstack = array();
		} elseif(array_key_exists(self::RDF_ABOUT, $attrs))
		{
			// fixes the problem with an empty value inside the rdf:about attribute
			// the problem first occured in the rdfs:versionInfo property of the owl:Ontology resource
			if(empty($attrs[self::RDF_ABOUT]))
			{
				$attrs[self::RDF_ABOUT] = $this->baseUri;
			}
			
			dispatcher()->onNewTriple($attrs[self::RDF_ABOUT], self::RDF_TYPE, $name);
			$this->rdfstack[] = array(self::IDX_SUBJECT, $attrs[self::RDF_ABOUT]);
		} elseif(array_key_exists(self::RDF_ID, $attrs))
		{
			dispatcher()->onNewTriple($attrs[self::RDF_ID], self::RDF_TYPE, $name);
			$this->rdfstack[] = array(self::IDX_SUBJECT, $attrs[self::RDF_ID]);
		} elseif(array_key_exists(self::RDF_RESOURCE, $attrs))
		{
			list($t,$s) = end($this->rdfstack);
			assert('/* subject expected */ $t == self::IDX_SUBJECT');

			dispatcher()->onNewTriple($s, $name, $attrs[self::RDF_RESOURCE]);
			$this->rdfstack[] = array(self::IDX_PREDICATE, $name);
			$this->nestedElement = true; // simulate element
		} else
		{
			// a nasty hack which should generate local ID of a resource in case
			// the resource URI is missing
			if(empty($this->rdfstack))
			{
				$this->generatedLocalId++;
				$this->rdfstack[] = array(self::IDX_SUBJECT, "_:$this->generatedLocalId");
				return;
			}
			
			// a nasty hack with "http://www.w3.org/XML/1998/namespacelang" used instead of "xml:lang"
			$this->rdfstack[] = array(self::IDX_PREDICATE, $name, @$attrs['http://www.w3.org/XML/1998/namespacelang']);
		}
	}

	/**
	 * SAX-parser callback function.
	 * @param resource $parser
	 * @param string $name
	 */
	private function xmlEndElement($parser, $name)
	{		
		if($name == self::RDF_RDF)
		{
			assert('/* an RDF document must end with an empty stack */ empty($this->rdfstack)');
			// end of rdf
		} else
		{
			@list($t, $uri, $lang) = array_pop($this->rdfstack);
			
			if($t == self::IDX_PREDICATE && !$this->nestedElement)
			{
				list($t, $s) = end($this->rdfstack);
				assert('/* subject expected */ $t == self::IDX_SUBJECT');
				dispatcher()->onNewTriple($s, $uri, new RDFS_Literal(trim($this->xmlBuffer), $lang) );
			} elseif($t == self::IDX_SUBJECT && !empty($this->rdfstack))
			{
				list($t, $p, $lang) = end($this->rdfstack);
				assert('/* predicate expected */ $t == self::IDX_PREDICATE');
				list($t,$s) = prev($this->rdfstack);
				assert('/* subject expected */ $t == self::IDX_SUBJECT');
				assert('/* cannot use language in case of URI */ empty($lang)');
				dispatcher()->onNewTriple($s, $p, $uri);
			}
		}
		
		// begin xml-related stuff
		$this->xmlBuffer = null;
		$this->nestedElement = true;
		// end of xml-related stuff
	}
	
	/**
	 * SAX-parser callback function.
	 * @param resource $parser
	 * @param string $cdata
	 */
	private function xmlCData($parser, $cdata)
	{
		$this->xmlBuffer .= $cdata;
	}
}
?>