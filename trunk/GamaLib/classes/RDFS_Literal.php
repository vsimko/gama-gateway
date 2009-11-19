<?php
/**
 * Represents RDF literal value.
 * Literals can be associated with a datatype or language.
 * Our implementation, however, does not use datatypes.
 * 
 * Example:
 *   $l = new RDFS_Literal("I'm the literal");
 *   echo $l; //output: I'm the literal
 */
class RDFS_Literal
{
	/**
	 * Literal value also publicly accessible.
	 * @var string
	 */
	public $value;

	/**
	 * Optional language modifier also publicly accessible.
	 * @var string
	 */
	public $lang;
	
	/**
	 * Constructs the literal.
	 *
	 * @param string $value
	 * @param string $lang
	 */
	public function __construct($value, $lang=null)
	{
		$this->value = $value;
		
		assert('/* only ISO 639-1 language codes are allowed */ empty($lang) || preg_match("/^[a-z][a-z]$/", $lang)');
		$this->lang = $lang;
	}
	
	/**
	 * Literal value will also be rendered directly.
	 * @return string
	 */
	public function __toString()
	{
		return $this->value;
	}
}
?>