<?php

/**
 * Strings composed of any character allowed in a XML 1.0 document without
 * any treatment done on whitespace.
 *
 * MySQL: text datatype with fulltext index
 */
class DT_xsd_string extends GAMA_Datatype
{
	static public function getUri()
	{
		return 'http://www.w3.org/2001/XMLSchema#string';
	}

	public function getColumnDefinition()
	{
		return array(	'object'	=> 'binary(16) not null',
						'data'		=> 'text character set utf8 null default null' );
	}

	public function getSortingIndexDefinition()
	{
		return 'data(20)';
	}

	public function getIndexDefinition()
	{
		return array( 'fulltext index (data)' );
	}

	public function getValueDefinition($tabalias)
	{
		return "$tabalias.data";
	}

	public function getSortingValueDefinition($tabalias)
	{
		return "$tabalias.data";
	}

	public function getJoinDefinition($tabalias)
	{
		return "$tabalias.object";
	}

	public function getLangJoinDefinition($tabalias)
	{
		return "$tabalias.lang";
	}

	public function getParsedLiteral(RDFS_Literal $literal)
	{
		if( !is_string($literal->value) )
		{
			throw new GAMA_Datatype_Value_Exception();
		}

		$cleanedValue = self::cleanupBrokenString($literal->value);

		// only a few HTML tags are allowed
		$cleanedValue = self::strip_tags_attributes(
			$cleanedValue,
			'<b><p><br><a><i><em><strong><sup><sub>', // allowed tags
			'href' ); // allowed attributes
		
		return array(
			'object'	=> hash('md5', "{$literal->lang}:{$cleanedValue}", true),
			'lang'		=> $literal->lang ? $literal->lang : '',
			'data'		=> $cleanedValue );
	}
	
	/**
	 * Experimenatal safety net for the broken characters comming from DB-Adapters.
	 * @param string $value
	 * @return string
	 */
	static public function cleanupBrokenString($value)
	{
		return str_replace(
			array(	'Å¾',	'Ä',
					'Å¡',	'Ãª',	'Â°',	'Ä‡',	'Â»',	'Â«',	'Ã¨',	'Ã¼',
					'â€˜',	'â€™',	'â€',	'â€œ',	'â€¦',	'â€“',
					'',
					'',	'',	'',	'',	'',	'',	'',	'',
					'',	'',	'',	'',	'',	'',	'',	'',
					'',	'',	'',	'',	'',	'',	'',	'',
					'',	'',	'',	'',	'',	'',	'',	'',
			),
			
			array(	'ž',	'č',
					'š',	'ê',	'°',	'ć',	'»',	'«',	'è',	'ü',
					"'",	"'",	'"',	'"',	'...',	'-',
					' ',
					' ',	' ',	"'",	' ',	"'",	'...',	'-',	' ',
					' ',	' ',	' ',	' ',	"'",	' ',	' ',	' ',
					' ',	"'",	"'",	'"',	'"',	' ',	'-',	'-',
					' ',	'™',	'š',	' ',	' ',	' ',	'ž',	' ',
			),
			$value );
	}
	
	/**
	 * Strip tags and attributes (with allowable attributes).
	 * @see http://www.php.net/manual/en/function.strip-tags.php#91498
	 * @param $string
	 * @param $allowtags
	 * @param $allowattributes Allowable attributes can be comma seperated or array
	 * @return string
	 */
	static public function strip_tags_attributes($string, $allowtags = null, $allowattributes = null)
	{
		$string = strip_tags($string, $allowtags);
		
		// there are some allowed attributes
		if (!is_null($allowattributes))
		{
			// string with comma separated attributes
			if(!is_array($allowattributes))
			{
				$allowattributes = explode(",", $allowattributes);
			}

			// convert the array to regexp
			if(is_array($allowattributes))
			{
				$allowattributes = implode(")(?<!",$allowattributes);
			}

			// NOTE: this condition should be covered with the if(!is_array(...))
			//if (strlen($allowattributes) > 0)
			//	$allowattributes = "(?<!".$allowattributes.")";
			
			$string = preg_replace_callback("/<[^>]*>/i",
				create_function(
            		'$matches',
            		'return preg_replace("/ [^ =]*'.$allowattributes.'=(\"[^\"]*\"|\'[^\']*\')/i", "", $matches[0]);'
				), $string);
		}
		return $string;
	}
}
?>