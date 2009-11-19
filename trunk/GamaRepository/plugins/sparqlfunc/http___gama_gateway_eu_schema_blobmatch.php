<?php

/**
 * GAMA-specific usage of a fulltext index.
 * 
 * Since MySQL does not support fulltext indexes across multiple tables,
 * it is worth trying to fit all related textual data into a single table
 * with many columns. We call such tables "blobs" and this function provides
 * a simple SPARQL binding for this purpose.
 * 
 * Moreover, we support a special handling of keywords comming from OCR/ASR
 * indexing process. OCR and ASR keywords need to be hashed separately hence
 * the usage of a prefix "o_" for every OCR hash and "a_" for every ASR hash.
 * Using the "INCLUDING HASH" clause it is possible to extend the query to match
 * these keywords correctly.
 * 
 * Example:
 * <code>
 * PREFIX gama: <http://gama-gateway.eu/schema/>
 * PREFIX cache: <http://gama-gateway.eu/cache/>
 * select ?w {
 *   ?w cache:fulltext_works ?blob.
 *   FILTER gama:blobmatch( "red* INCLUDING HASH", ?blob,
 *     "title_en", "descr_en", "cname", "kw_ocr", "kw_asr" )
 * }
 * limit 10
 * </code>
 * 
 * @author Viliam Simko
 */
class http___gama_gateway_eu_schema_blobmatch extends SPARQL_Function
{
	/**
	 * (non-PHPdoc)
	 * @see SPARQL_Function#execute()
	 */
	function execute()
	{
		// param #1
		$ftMatchValue = $this->shiftParam(self::P_LITERAL)->getRawValue();
		
		// the special modifier INCLUDING HASH
		$match = null;
		if(preg_match('/^(.*)\s+INCLUDING HASH\w*$/i', $ftMatchValue, $match))
		{
			$ftMatchValue = $match[1];
			$ocrHash = DT_gama_keywordType::hashOCR($ftMatchValue);
			$asrHash = DT_gama_keywordType::hashASR($ftMatchValue);
			$ftMatchValue = mysql_escape_string("$ftMatchValue o_$ocrHash a_$asrHash");
		}
		
		// param #2
		$blobVar = $this->shiftParam(self::P_VAR);

		// other params
		$sql = array();
		do
		{
			$columName = $this->shiftParam(self::P_LITERAL);
			$sql[] = $blobVar->getSpecialBinding( $columName->getRawValue() );
		} while($this->hasMoreParameters());
		
		return "MATCH(".implode(',', $sql).") AGAINST('$ftMatchValue' IN BOOLEAN MODE)";
	}
}
?>