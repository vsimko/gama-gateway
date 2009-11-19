<?php

/**
 * Attached to the dispatcher the namespace manager can listen
 * and resolve incomming namespaces as they appear in the XML stream.
 * 
 * A single namespace consists of prefix and the namespace itself.
 * If there are two namespaces with the same prefix, the last declared
 * namespace prevails and creates a new view. The event onUndoNamespace allows
 * for backtracking the namespace views.
 */
class Namespace_Manager
{
	// =========================================================================
	// Event handlers
	// =========================================================================

	/**
	 * Interrested objects may obtain the namespace manager reference by calling
	 * the onGetNamespaceManager event.
	 * @return Namespace_Manager
	 */
	public function onGetNamespaceManager()
	{
		return $this;
	}
	
	/**
	 * @param string $prefix
	 * @param string $uri
	 */
	public function onNewNamespace($prefix, $uri)
	{
		if(empty($prefix)) $prefix = '';
		
		$pn = & $this->namespaces[$prefix];
		if(isset($pn))
		{
			$s[] = $prefix;
			$this->next[$prefix][] = $pn;
		}
		$pn = $uri; // assignes to the reference
	}
	
	/**
	 * When using PHP XML parser be aware of the bug
	 * "Namespace end handler is not called"
	 * http://bugs.php.net/bug.php?id=30834
	 * @param string $prefix
	 */
	public function onUndoNamespace($prefix)
	{
		$pn = array_pop($this->next[$prefix]);
		$this->namespaces[$prefix] = $pn;
	}
	
	// =========================================================================
	// Public methods
	// =========================================================================
	
	/**
	 * Returns current list of namespaces.
	 * @return array
	 */
	public function getNamespaces()
	{
		return $this->namespaces;
	}
	
	/**
	 * Substitutes prefix part of the URI with appropriate namespace.
	 * 
	 * @param string $uri URI to be substituted
	 * @return string
	 */
	function resolveUri($uri)
	{		
		// According to http://www.w3.org/TR/1999/REC-xml-names-19990114/#dt-qname
		//
		//  QName         ::=   (Prefix ':')? LocalPart
		//  Prefix        ::=   NCName
		//  LocalPart     ::=   NCName
		//  NCName        ::=   (Letter | '_') (NCNameChar)*
		//  NCNameChar    ::=   Letter | Digit | '.' | '-' | '_' | CombiningChar | Extender
		//  Letter        ::=   see http://www.w3.org/TR/REC-xml#NT-Letter
		//  Digit         ::=   see http://www.w3.org/TR/REC-xml#NT-Digit
		//  #CombiningChar ::=   see http://www.w3.org/TR/REC-xml/#NT-CombiningChar
		//  #Extender      ::=   see http://www.w3.org/TR/REC-xml/#NT-Extender
		
		// "#" means not supported in our implementation
		// Note: at the moment, we support LocalPart with loading digit
		
		if(preg_match('/^(([_a-zA-Z][_a-zA-Z0-9\-\.]*):|)([_a-zA-Z0-9\-\.]*)$/', $uri, $m))
		{
			$expanded = @$this->namespaces[$m[2]];
			
			if($expanded)
			{
				$uri = $expanded.$m[3];
			} else
			{
				$uri = $this->namespaces[''].$uri; // expand default namespace
			}
		}

		assert('/* this should is a single string value */ is_string($uri)');
		return $uri;
	}
	
	/**
	 * Replaces known namespace with the prefix.
	 * @param string $uri
	 * @return string
	 */
	public function resolvePrefix($uri)
	{
		if(list($ns, $lpart) = GAMA_Utils::splitUri($uri))
		{
			$key = array_search($ns, $this->namespaces);
			if(isset($key))
				return empty($key) ? $lpart : "$key:$lpart";
		}
		return $uri;
	}
	
	/**
	 * Keys of the array will be resolved against known namespaces.
	 * @param array $a
	 * @return array
	 */
	public function resolveArrayKeys($a)
	{
		assert('/* parameter must be array */ is_array($a)');

		foreach($a as $key => $value)
		{
			$newkey = $this->resolveUri($key);

			// we hope that if the unchanged keys stay untouched
			// it will be faster than rebuilding the whole array
			if($key != $newkey)
			{
				unset($a[$key]);
				$a[$newkey] = $value;
			}
		}
		return $a;
	}
	
	/**
	 * Copy namespaces from other namespace manager.
	 * @param Namespace_Manager $other
	 */
	public function mergeWith(Namespace_Manager $other)
	{
		$this->next = array();
		$this->namespaces = array_merge($this->namespaces, $other->namespaces);
	}
	
	// =========================================================================
	// Other stuff
	// =========================================================================
	
	/**
	 * Namespaces indexed as prefix => namespace
	 * @var array
	 */
	private $namespaces = array();

	/**
	 * Chains of overriden namespaces
	 * prefix => array of overriden namespaces in reverse order
	 * @var array
	 */
	private $next = array();
}
?>