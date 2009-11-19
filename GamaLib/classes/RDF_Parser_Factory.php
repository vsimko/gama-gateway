<?php

/**
 * Creates instance of appropriate RDF_Parser.
 */
class RDF_Parser_Factory
{
	const FORMAT_RDFXML = 'rdfxml';
	const FORMAT_TURTLE = 'turtle';
	const FORMAT_NTRIPLES = 'ntriples';
	const FORMAT_N3 = 'n3';
	const FORMAT_ATOM = 'atom';
	const FORMAT_HTML = 'html';
	const FORMAT_OPML = 'opml';
	const FORMAT_RSS = 'rss';
	const FORMAT_XML = 'xml';
	
	/**
	 * Returns RDF parser instance according to format.
	 *
	 * @param string $format
	 * @return RDF_Parser
	 */
	static public function getParserByFormat($format)
	{
		switch($format)
		{
			case self::FORMAT_RDFXML:	return new RDF_XML_Parser();
			//case self::FORMAT_RDFXML:	return new RDF_Wrapped_ARC2_RDFXML_Parser();
			//case self::FORMAT_NTRIPLES:	return new RDF_Wrapped_ARC2_Turtle_Parser();
			//case self::FORMAT_TURTLE:	return new RDF_Wrapped_ARC2_Turtle_Parser();
			
			default: throw new Exception("Unsupported format: '$format'");
		}
	}
	
	/**
	 * Returns a RDF parser instance suitable for the file
	 * (estimates the format from the file content)
	 *
	 * @param string $location
	 * @return RDF_Parser
	 */
	static public function getParserByLocation($location)
	{
		// guess file format
		$format = self::getFormat($location);
		$parser = self::getParserByFormat($format);
		$parser->setLocation($location);
		return $parser;
	}
		
	/**
	 * Detects the format of given file (location).
	 * @param string $location
	 */
	final static public function getFormat($location)
	{
		global $http_response_header;
		
		// read first 1024 bytes from the given file (maybe gzipped file)
		$fh = @gzopen($location, 'r');

		if(empty($fh))
		{
			throw new Exception('Could not open the file');
		}
		
		$v = fread($fh, 1024);
		fclose($fh);
		
		if(empty($v)) return null;

		if(!empty($http_response_header))
		{
			$mime = preg_grep('/^Content-Type:/', $http_response_header, null);
			if(! empty($mime))
			{
				$mime = array_shift($mime);
			
				// use to mime type
				if(strpos($mime, '/atom+xml')) return self::FORMAT_ATOM;
				if(strpos($mime, '/rdf+xml')) return self::FORMAT_RDFXML;
				if(strpos($mime, '/turtle') || strpos($mime, '/x-turtle')) return self::FORMAT_TURTLE;
				if(strpos($mime, '/rdf+n3')) return self::FORMAT_N3;
				if(strpos($mime, '/html')) return self::FORMAT_HTML;
			}
		}
		
		// guess format from content
		if(preg_match('/^\<\?xml/', $v))
		{
			while (preg_match('/^\s*\<\?xml[^\r\n]+\?\>\s*/s', $v))
				$v = preg_replace('/^\s*\<\?xml[^\r\n]+\?\>\s*/s', '', $v);

			while (preg_match('/^\s*\<\!--.+?--\>\s*/s', $v))
				$v = preg_replace('/^\s*\<\!--.+?--\>\s*/s', '', $v);
			
			// doctype checks (html, rdf)
			if(preg_match('/^\s*\<\!DOCTYPE\s+html[\s|\>]/is', $v)) return self::FORMAT_HTML;
			if(preg_match('/^\s*\<\!DOCTYPE\s+[a-z0-9\_\-]\:RDF\s/is', $v)) return self::FORMAT_RDFXML;
			
			// markup checks
			$v = preg_replace('/^\s*\<\!DOCTYPE\s.*\]\>/is', '', $v);
			if(preg_match('/^\s*\<rss\s+[^\>]*version/s', $v)) return self::FORMAT_RSS;
			if(preg_match('/^\s*\<feed\s+[^\>]+http\:\/\/www\.w3\.org\/2005\/Atom/s', $v)) return self::FORMAT_ATOM;
			if(preg_match('/^\s*\<opml\s/s', $v)) return self::FORMAT_OPML;
			if(preg_match('/^\s*\<html[\s|\>]/is', $v)) return self::FORMAT_HTML;
			if(preg_match('/^\s*\<[^\s]*RDF[\s\>]/s', $v)) return self::FORMAT_RDFXML;
			if(preg_match('/^\s*\<[^\>]+http\:\/\/www\.w3\.org\/1999\/02\/22\-rdf/s', $v)) return self::FORMAT_RDFXML;
			return self::FORMAT_XML;
		}
	
		if(preg_match('/\@(prefix|base)/i', $v)) return self::FORMAT_TURTLE;
		if(preg_match('/^\s*(_:|<).+?\s+<[^>]+?>\s+\S.+?\s*\.\s*$/m', $v)) return self::FORMAT_NTRIPLES;
		
		// use extension as a last resort
		$ext = substr(strrchr($location, '.'), 1);
		if($ext == 'owl' || $ext == 'rdf' || $ext == 'rdfs') return self::FORMAT_RDFXML;
		if($ext == 'ttl') return self::FORMAT_TURTLE;
		if($ext == 'n3') return self::FORMAT_N3;
		if($ext == 'nt') return self::FORMAT_NTRIPLES;

		return $ext;
	}
	
}
?>