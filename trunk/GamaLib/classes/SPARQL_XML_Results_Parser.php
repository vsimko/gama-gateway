<?php

/**
 * TODO: use an interface for the result handler.
 * TODO: allow chaining of SPARQL_XML_Result_Renderer and Parser
 * @author Viliam Simko
 */
class SPARQL_XML_Results_Parser
{
	const READ_BLOCK_SIZE = 1024;
	
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
	 * This callback is called on every SPARQL result element.
	 * @var callback
	 */
	private $resultHandlerCallback;
	
	/**
	 * 
	 * @var unknown_type
	 */
	private $numFoundResults;
	
	/**
	 * Set up the XML parser and default callbacks.
	 */
	public function __construct()
	{
		//default handler for the result elements
		$this->resultHandlerCallback = array($this, 'defaultResultHandler');
		
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
		
		$this->numFoundResults = 0;
	}
	
	/**
	 * Parse everything from a given location.
	 * Gzipped files are also supported.
	 * @param string $inputLocation
	 */
	public function parseFromLocation($inputLocation)
	{
		$inputStream = @gzopen($inputLocation, 'r');
		
		if(!is_resource($inputStream))
		{
			throw new Exception('Could not open the specified input location');
		}
		
		$this->parseFromStream($inputStream);
	}
	
	/**
	 * Parse everything from a given input stream (and close it).
	 * The parser closes the input stream using fclose() when finished
	 * as well as when error occures before the exception is thrown.
	 * @param resource $inputStream
	 */
	public function parseFromStream($inputStream)
	{
		if(!is_resource($inputStream))
		{
			throw new Exception('The intput stream should be a valid resource');
		}
		
		// XML is read from the stream block-by-block and parsed
		try
		{
			$feof = feof($inputStream);
			while (! $feof)
			{
				$chunk = @fread($inputStream, self::READ_BLOCK_SIZE);
				$feof = feof($inputStream);
				$this->parseChunk($chunk, $feof);
			}
		} catch(Exception $e)
		{
			xml_parser_free($this->xmlParser);
			fclose($inputStream);
			throw $e;
		}
		
		fclose($inputStream);
		xml_parser_free($this->xmlParser);
	}
	
	/**
	 * Parse XML file stored in the input string.
	 * @param string $inputString
	 */
	public function parseFromString($inputString)
	{
		$this->parseChunk($inputString, true);
	}
	
	/**
	 * @param string $chunk
	 * @param boolean $is_final See PHP manual of xml_parse function
	 */
	public function parseChunk($chunk, $is_final = false)
	{
		// don't try to use utf8_encode here. the parser would not fail
		// on illegal characters, however it would produce wrong output.
		if (!xml_parse($this->xmlParser, $chunk, $is_final))
		{
			$xmlerrcode = xml_get_error_code($this->xmlParser);
			
			// hacked PHP function
			$xmlspecialerrstr = array(68 => 'CDATA section not properly escaped e.g. "&" should be "&amp;"');
			if(! $xmlerrstr = @$xmlspecialerrstr[$xmlerrcode])
			{
				$xmlerrstr = xml_error_string($xmlerrcode);
			}
			
			throw new Exception(
				'XML error: ' . $xmlerrstr .
				' line ' . xml_get_current_line_number($this->xmlParser)
				);
		}
	}
				
	/**
	 * Set up a handler called on every SPARQL result element.
	 * The callback function/method must use two parameters:
	 * 
	 * - $parser = the parser that called the handler
	 * - $resultElement = the result element from the parser
	 *  
	 * @param array|object|string $arg1 object or function name
	 * @param string $arg2 (optional) if arg1 is an object, arg2 is the method name
	 */
	public function setResultHandler($arg1, $arg2 = null)
	{
		$this->resultHandlerCallback = empty($arg2) ? $arg1 : func_get_args();
	}
		
	/**
	 * Default result element handler just prints the array.
	 * @param object $parser
	 * @param array $resultElement
	 */
	static private function defaultResultHandler($parser, $resultElement)
	{
		echo "\nResult #".$parser->getNumFoundResults()."\n";
		print_r($resultElement);
	}
	
	public function getNumFoundResults()
	{
		return $this->numFoundResults;
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
		}
	}
	
	private $resultElement = array();
	private $binding;
	private $bindingName;
	private $bindingType;
	private $bindingLang;
	
	const BINDING_TYPE_LITERAL = 'literal';
	const BINDING_TYPE_URI = 'uri';
	
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
		switch($name)
		{
			case 'http://www.w3.org/2005/sparql-results#binding':
				$this->bindingName = $attrs['name'];
				break;

			case 'http://www.w3.org/2005/sparql-results#uri':
				$this->bindingType = self::BINDING_TYPE_URI;
				break;
				
			case 'http://www.w3.org/2005/sparql-results#literal':
				$this->bindingType = self::BINDING_TYPE_LITERAL;
				$this->bindingLang = @$attrs['xml:lang'];
				break;
		}
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
		switch($name)
		{
			case 'http://www.w3.org/2005/sparql-results#result':
				$this->numFoundResults++;
				call_user_func($this->resultHandlerCallback, $this, $this->resultElement);
				$this->resultElement = array();
				break;
			
			case 'http://www.w3.org/2005/sparql-results#uri':
			case 'http://www.w3.org/2005/sparql-results#literal':
				$this->binding = $this->xmlBuffer;
				break;
			
			case 'http://www.w3.org/2005/sparql-results#binding':
				$this->resultElement[$this->bindingName] = $this->binding;
				$this->resultElement[$this->bindingName.'_type'] = $this->bindingType;
				if($this->bindingType == 'literal')
				{
					$this->resultElement[$this->bindingName.'_lang'] = $this->bindingLang;
				}
				unset($this->binding);
				break;
		}
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