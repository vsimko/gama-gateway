<?php

/**
 * This datatype is suitable for storing automatically generated keywords
 * such as OCR or ASR.
 */
class DT_gama_keywordType extends GAMA_Datatype
{
	static public function getUri()
	{
		return 'http://gama-gateway.eu/schema/keywordType';
	}
	
	public function getColumnDefinition()
	{
		return array(	'object'	=> 'varchar(32) not null',
						'kwtype'	=> 'enum("ocr","asr") null default null' );
	}
	
	public function getSortingIndexDefinition()
	{
		return 'object';
	}
	
	public function getIndexDefinition()
	{
		return array('index (kwtype)');
	}
		
	public function getValueDefinition($tabalias)
	{
		return "$tabalias.object";
	}
	
	public function getSortingValueDefinition($tabalias)
	{
		return "$tabalias.object";
	}
	
	public function getJoinDefinition($tabalias)
	{
		return "$tabalias.object";
	}


	/**
	 * [type:]<keyword>
	 *
	 * @param RDFS_Literal $literal
	 * @return unknown
	 */
	public function getParsedLiteral(RDFS_Literal $literal)
	{
//multiple keywords not supported at the moment
//		$kwList = preg_split('/[\s,|]+/', $literal->value);
//		
//		foreach($kwList as $kwDef)
//		{
//			if(preg_match('/^(([^:]*):|)([^:]*)$/', $kwDef, $m))
//			{
//				$kwType = $m[2];
//				$kwValue = $m[3];
//			}
//		}

		$kwDef = $literal->value;
		
		$pattern = '/^(([^:]*):|)([^:]*)$/';
		if(preg_match($pattern, $kwDef, $m))
		{
			$kwType = $m[2];
			$kwValue = $m[3];
		} else
		{
			throw new GAMA_Datatype_Value_Exception("Unsupported format of the keyword '$kwDef' required pattern is: '$pattern'");
		}
		
		switch($kwType)
		{
			case 'asr': $kwValue = self::hashASR($kwValue); break;
			case 'ocr': $kwValue = self::hashOCR($kwValue); break;
			default:
				$kwType = 'ocr';
				$kwValue = self::hashOCR($kwValue);
		}
		
		return array(	'object'	=> $kwValue,
						'kwtype'	=> $kwType );
	}
	
	public static function hashOCR($string)
	{
		// hashing function updated by Michal Grega
		$prehash = strtr(
			utf8_decode(strtoupper($string)),
			utf8_decode('A&3BDEQ€C6GO0F{RITL\YHNUMJKPS5VWXZ124789'),
                        'AAABDEEEEEEEEFFFFFFFFHHHHJKPSSVWXZ124789');

        return preg_replace('/[^ABDEFHJKPSVWXZ124789]/', '.', $prehash);

// old hashing function by Viliam Simko
//        $prehash = strtr(
//			utf8_decode(strtoupper($string)),
//			utf8_decode('4@[(O0Q)}B8ßRP®3€{69#1!|LT+†J7]XN5$Z2§%UWµ'),
//						'AACCDDDDDDDDDDDEEEGGHIIIIIIIIIIKMSSSSSSVVV');
//		
//		return preg_replace('/[^ACDEFGHIKMSVY]/', '.', $prehash);
	}
	
	public static function hashASR($string)
	{
		return soundex($string);
	}
}
?>