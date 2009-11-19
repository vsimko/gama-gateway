<?php
/**
 * Hashing function for the OCR (Optical Character Recognition) results.
 * Using our SHAPEX hashing algorithm.
 * @author Viliam Simko
 */
class http___gama_gateway_eu_schema_matchocr extends SPARQL_Function
{
	/**
	 * (non-PHPdoc)
	 * @see SPARQL_Function#execute()
	 */
	public function execute()
	{
		$rawValue = $this->shiftParam(self::P_LITERAL)->getRawValue();
		$value = mysql_escape_string(DT_gama_keywordType::hashOCR($rawValue));

		$var = $this->shiftParam(self::P_VAR);
		$dtbind = $var->getSpecialBinding('kwtype');
		
		return "( {$var->getBindValue()} like '$value%' and $dtbind='ocr' )";
	}	
}
?>