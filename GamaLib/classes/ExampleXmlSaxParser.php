<?php

/**
 * Example:
 * $p = new ExampleXmlSaxParser;
 * $p->setLocation('path/to/some/file.owl');
 * $p->startParser();
 */
class ExampleXmlSaxParser
{
	const READ_BLOCK_SIZE = 65536;
	
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
	 * Call if you want to start parsing.
	 */
	public function startParser()
	{
		if(empty($this->location))
			throw new Exception('Set the location first');
		
		if($this->xmlParser)
			throw new Exception('The parser has already started.');
		
		// =====================================
		// YOUR CODE HERE
		// REPORT BEGINNING OF THE DOCUMENT
		// =====================================
			
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
		while ($chunk = @fread($fh, self::READ_BLOCK_SIZE))
		{
			if (!xml_parse($this->xmlParser, utf8_encode($chunk), feof($fh)))
			//if (!xml_parse($xmlParser, $chunk, feof($fh)))
			{
				fclose($fh);
				throw new Exception(
					'XML error: ' .  xml_error_string(xml_get_error_code($this->xmlParser)) .
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
			
		// =====================================
		// YOUR CODE HERE
		// REPORT END OF THE DOCUMENT
		// =====================================
	}
	
	/**
	 * Call if you want to terminate parsing.
	 */
	public function stopParser()
	{
		xml_parser_free($this->xmlParser);
		unset($this->xmlParser);
	}
	
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
			
			// ================================
			// YOUR CODE HERE
			echo "xmlStartNS: $prefix = $uri\n";
			// ================================
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
		
		// ================================
		// YOUR CODE HERE
		echo "xmlStartElement: $name\n";
		// ================================
	}

	/**
	 * SAX-parser callback function.
	 * @param resource $parser
	 * @param string $name
	 */
	private function xmlEndElement($parser, $name)
	{
		// ================================
		// YOUR CODE HERE
		echo "xmlEndElement: $name\n";
		// ================================
		
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