<?php

/**
 * Extracts anntotations located in comments.
 */
class Simple_Annotation
{
	/**
	 * The constructor parses the given string and extracts annotations.
	 * @param string $commentString
	 */
	public function __construct($commentString)
	{
		// find all annotations
		// an annotation is represented as one of the following strings:
		// - @annotation
		// - @annotation value
		// - @annotation value comment till the end of line
		if(preg_match_all('/@([^@\s]+)([ ]+([^\s]+)(.*))?/', $commentString, $match))
		{
			for($i=0; $i<count($match[0]); ++$i)
			{
				$annotName = $match[1][$i];
				$annotValue = $match[3][$i];
				$annotComment = trim($match[4][$i]);
				
				$this->foundAnnotations[$annotName] = array($annotValue, $annotComment);
			}
		}
		
		// extract the bare comment text
		$this->commentText = trim(preg_replace(
			'/@.*|^\s*\*\/|^\s*\/\*\*|^\s*\* ?/m', '', $commentString));
	}
	
	/**
	 * The comment without annotations.
	 * @var string
	 */
	private $commentText;
	
	/**
	 * Annotations extracted from the comment string.
	 * @var array
	 */
	private $foundAnnotations = array();
	
	/**
	 * Testing the existence of an annotation is useful as boolean operation. 
	 * @param string $annotName
	 * @return boolean
	 */
	public function hasAnnotation($annotName)
	{
		return isset($this->foundAnnotations[$annotName]);
	}
	
	/**
	 * Extracted value of an annotation.
	 * @param string $annotName
	 * @return string
	 */
	public function getAnnotationValue($annotName)
	{
		return $this->foundAnnotations[$annotName][0];
	}
	
	/**
	 * Extracted note (comment) of an annotation
	 * @param string $annotName
	 * @return string
	 */
	public function getAnnotationNote($annotName)
	{
		return $this->foundAnnotations[$annotName][1];
	}
	
	/**
	 * Extracted comment text without annotations.
	 * @return string
	 */
	public function getCommentText()
	{
		return $this->commentText;
	}
	
	/**
	 * List of names of found annotations.
	 * @return array
	 */
	public function getAnnotations()
	{
		return array_keys($this->foundAnnotations);
	}
	
	/**
	 * Syntactic sugar: Extract annatations from class.
	 * This is a factory method.
	 * @param mixed $class
	 * @return Simple_Annotation
	 */
	static public function createFromClassComment($class)
	{
		$reflect = new ReflectionClass($class);
		return new Simple_Annotation($reflect->getDocComment());
	}
	
	/**
	 * Syntactic sugar: Extract annatations from a property of a given class. 
	 * This is a factory method.
	 * @param mixed $class
	 * @param string $property
	 * @return Simple_Annotation
	 */
	static public function createFromPropertyComment($class, $property)
	{
		$reflect = new ReflectionProperty($class, $property);
		return new Simple_Annotation($reflect->getDocComment());
	}
	
	/**
	 * Syntactic sugar: Extract annatations from a method of a given class. 
	 * This is a factory method.
	 * @param mixed $class
	 * @param string $method
	 * @return Simple_Annotation
	 */
	static public function createFromMethodComment($class, $method)
	{
		$reflect = new ReflectionMethod($class, $method);
		return new Simple_Annotation($reflect->getDocComment());
	}
	
}

?>