<?php

/**
 * Useful functions that need to be autoloaded only in certain situations.
 * @author Viliam Simko
 */
abstract class GAMA_Utils
{
	/**
	 * Make use we use HTTPS protocol.
	 * @return unknown_type
	 */
	static public function forceHttpsMode()
	{
		if( ! Config::get('endpoint.nohttps'))
		{
			if($_SERVER['SERVER_PORT'] != 443)
			{
				$pageurl = 'https://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
				header("Location: $pageurl");
		    	header("Content-type: text/html");
		    	echo "<html><head><meta http-equiv='Refresh' Content='1; URL=".htmlspecialchars($pageurl)."' />
		    	<title>Redirect</title></head>
		    	<body><a href='".htmlspecialchars($pageurl)."'>Redirect to ".htmlspecialchars($pageurl)."</a></body></html>";
		    	exit;
			}
		}
	}
	
	/**
	 * All non-alphanumerical characters will be replaced by underscore '_' and lowercased.
	 * This is useful when mapping URIs to methods or functions.
	 * 
	 * @param string $uri
	 * @return string
	 */
	static public function normaliseUri($uri)
	{
		return preg_replace('/[^0-9a-zA-Z]/', '_', strtolower($uri) );
	}
	
	/**
	 * Convert a value to pretty encoded JSON format.
	 * 
	 * @author umbrae at gmail dot com
	 * @author Viliam Simko
	 * @see http://cz.php.net/json_encode
	 * @param $json_obj
	 * @return string
	 */
	static public function jsonPrettyEncode($json_obj)
	{
	    $tab = "  ";
	    $new_json = "";
	    $indent_level = 0;
	    $in_string = false;
	
	    //$json_obj = json_decode($json);
	
	    $json = json_encode($json_obj);
	    $len = strlen($json);
	
	    for($c = 0; $c < $len; $c++)
	    {
	        $char = $json[$c];
	        switch($char)
	        {
	            case '{':
	            case '[':
	                if(!$in_string)
	                {
	                    $new_json .= $char . "\n" . str_repeat($tab, $indent_level+1);
	                    $indent_level++;
	                }
	                else
	                {
	                    $new_json .= $char;
	                }
	                break;
	            case '}':
	            case ']':
	                if(!$in_string)
	                {
	                    $indent_level--;
	                    $new_json .= "\n" . str_repeat($tab, $indent_level) . $char;
	                }
	                else
	                {
	                    $new_json .= $char;
	                }
	                break;
	            case ',':
	                if(!$in_string)
	                {
	                    $new_json .= ",\n" . str_repeat($tab, $indent_level);
	                }
	                else
	                {
	                    $new_json .= $char;
	                }
	                break;
	            case ':':
	                if(!$in_string)
	                {
	                    $new_json .= ": ";
	                }
	                else
	                {
	                    $new_json .= $char;
	                }
	                break;
	            case '"':
	                if($c > 0 && $json[$c-1] != '\\')
	                {
	                    $in_string = !$in_string;
	                }
	            default:
	                $new_json .= $char;
	                break;                   
	        }
	    }
	
	    return $new_json;
	}

	/**
	 * Turn a string into CamelCase format.
	 * @param $string
	 * @return string
	 */
	static public function strToCamelCase($string)
	{
		// remove weird sequences of characters
		$string = preg_replace('/[^a-zA-Z]+/',' ', $string);
				
		// uppercase first characer in a word
		return trim(preg_replace_callback(
			array('/ ([^ ])/', '/(^.)/'),
			create_function('$match', 'return strtoupper($match[1]);'),
			$string ));
	}
	
	/**
	 * Use in the situation where we want to pass the user-specified value
	 * to the preg_* function.
	 */
	static public function pregPatternEncode($string)
	{
		return addcslashes($string, '/^$.[]|()?*+{}\\-');
	}
	
	/**
	 * Filteres entered text to desired length.
	 *
	 * @param string $text
	 * @param int $max_length
	 * @return string
	 */
	static public function filterStringLength($text, $max_length)
	{
		return  (strlen($text) > $max_length)
			? substr($text,0,$max_length - 3).'...'
			: $text;
	}
	
	/**
	 * Test the prefix of the word.
	 * @param string $prefix
	 * @param string $word
	 * @return boolean TRUE if the $prefix is a prefix of the $word
	 */
	static public function isPrefix($prefix, $word)
	{
		return $prefix == substr($word, 0, strlen($prefix));
	}
	
	/**
	 * Adds a string together with a delimiter if the original string is not empty.
	 * @param $originalString
	 * @param $delim
	 * @param $addString
	 */
	static function addStringDelimited( & $originalString, $delim, $addString)
	{
		if(!empty($originalString))
		{
			$originalString .= $delim;
		}
		
		$originalString .= $addString;
	}
	
	/**
	 * Supports sys_get_temp_dir in older versions of PHP (PHP < 5.2.1)
	 * @return string Returns the path of the temporary directory.
	 */
	static function getSystemTempDir()
	{
		if(function_exists('sys_get_temp_dir'))
		{
			return sys_get_temp_dir();
		}
		
		if (!empty($_ENV['TMP'])) { return realpath($_ENV['TMP']); }
		if (!empty($_ENV['TMPDIR'])) { return realpath( $_ENV['TMPDIR']); }
		if (!empty($_ENV['TEMP'])) { return realpath( $_ENV['TEMP']); }
		
		$tempfile = tempnam( uniqid(rand(), TRUE), '' );
		if( file_exists($tempfile) )
		{
			unlink($tempfile);
			return realpath(dirname($tempfile));
		}
		
		assert('/* not reached */');
	}
	
	/**
	 * Splits an URI to the namespace and local part.
	 * @param string $uri
	 * @return array [0] = namespace part, [1] = local part
	 */
	static public function splitUri($uri)
	{
		if(preg_match('/(.*[\/#])([^\/#]*)$/', $uri, $m))
		{
			return array($m[1], $m[2]);	
		}
	}
	
	/**
	 * Escapes html chars within code element.
	 * @param $string
	 * @return string
	 */
	static public function prepareCommentForHtml($string)
	{
		// uses the ungreedy regex (U) matching multiple lines (s)
		return preg_replace_callback(
			'/\s*<code>\s*(.*)\s*<\/code>\s*/Us',
			create_function('$x', 'return "<code>".htmlspecialchars($x[1])."</code>";'),
			$string );
	}
	
	/**
	 * Prepare the uri for the inclusion into the SPARQL script.
	 * <code>string => <escaped string></code>
	 * @param string $uri
	 * @return string
	 */
	static public function escapeSparqlUri($uri)
	{
		return '<'.preg_replace('/[^a-zA-Z0-9_\-#:\/\,\.]/', '', trim($uri)).'>';
	}
	
	/**
	 * Encode all elements of an array with musql_escape_string, enclose
	 * them with double quotes and use comma as a delimiter. 
	 * @return string
	 */
	static public function encodeArrayToSql(array $param)
	{
		return implode(',', array_map(create_function('$x', 'return "\"".mysql_escape_string($x)."\"";'), $param));
	}
	
	/**
	 * TODO: not yet implemented
	 * @param $uri
	 * @return array
	 */
	static public function getBugReportEmailsByUri($uri)
	{
		return array('viliam.simko@ciant.cz');
	}
	
	/**
	 * XML parsers cannot handle all UTF-8 characters.
	 * Valid UTF-8 does not imply valid XML.
	 * For example the PHP's serialised XML parser would return an error
	 * in the xml_parse() function if there were characters from ranges:
	 * #x9 | #xA | #xD | [#x20-#xD7FF] | [#xE000-#xFFFD] | [#x10000-#x10FFFF]
	 * 
	 * This function replaces these problematic characters.
	 * 
	 * @param $string
	 * @param $replace
	 * @return string
	 */
	static public function xmlUtf8Cleanup($string, $replace="?")
	{
		return preg_replace(
			'/[^\x9\xA\xD\x20-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u',
			$replace, $string);
	}
	
}
?>