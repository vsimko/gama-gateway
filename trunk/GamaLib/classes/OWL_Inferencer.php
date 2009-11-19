<?php

/**
 * Simple OWL Lite API with automatic inferencing capabilities.
 */
class OWL_Inferencer implements Triple_Handler_Interface
{
	// =========================================================================
	// Event handlers
	// =========================================================================
	
	/**
	 * @see Triple_Handler_Interface::onBeginDocument()
	 */
	public function onBeginDocument()
	{
		$this->nsmgr = dispatcher()->onGetNamespaceManager();
		if(empty($this->nsmgr))
			throw new Exception('No namespace manager. Please attach one to the dispatcher.');
		
		unset($owlclass);
		unset($owlclass_norm);
		unset($resource);
		unset($resource_norm);
	}
	
	/**
	 * @see Triple_Handler_Interface::onEndDocument()
	 */
	public function onEndDocument()
	{
		// nothing to do here
	}
	
	/**
	 * @see Triple_Handler_Interface::onNewTriple()
	 * @param string $s
	 * @param string $p
	 * @param string|RDFS_Label $o
	 */
	public function onNewTriple($s, $p, $o)
	{
		$s = $this->nsmgr->resolveUri($s);
		$p = $this->nsmgr->resolveUri($p);

		//string should represent URI, otherwise it should be RDFS_Literal
		if(is_string($o))
			$o = $this->nsmgr->resolveUri($o);
			
		switch($p)
		{
			case self::RDFS_DOMAIN:
				$this->registerResource($s);
				if(empty($this->resource[$s]['domain']))
				{
					$this->resource[$s]['domain'] = $o;
				} elseif($this->resource[$s]['domain'] == $o)
				{
					debug("The same domain has already been defined: $o");
				} else
				{
					throw new Exception('Only properties with single domain are supported at the moment');
				}
				break;
				
			case self::RDFS_RANGE:
				$this->registerResource($s);
				if(empty($this->resource[$s]['range']))
				{
					$this->resource[$s]['range'] = $o;
				} elseif($this->resource[$s]['range'] == $o)
				{
					debug("The same range has already been defined: $o");
				} else
				{
					throw new Exception('Only properties with single range are supported at the moment');		
				}
				break;
				
			case self::RDF_TYPE:
				$this->addResourceType($s, $o);
				break;
				
			case self::OWL_INVERSEOF:
				$this->setInverse($s, $o);
				$this->setInverse($o, $s);
				break;
			
			case self::RDFS_SUBCLASSOF:
				$this->registerOwlClass($s);
				$this->registerOwlClass($o);
				$this->resource[$s]['subclassof'] = & $this->resource[$o];
				$this->resource[$o]['children'][$s] = & $this->resource[$s];
				break;
			
			case self::RDFS_LABEL:
				assert('/* only literals allowed for labels */ $o instanceof RDFS_Literal');
				$this->registerResource($s);
				$this->resource[$s]['label'][$o->lang][] = $o->value;
				break;

			case self::RDFS_COMMENT:
				assert('/* only literals allowed for comments */ $o instanceof RDFS_Literal');
				$this->registerResource($s);
				$this->resource[$s]['comment'][$o->lang][] = $o->value;
				break;
				
			default:
				debug(__CLASS__." ignores the <$p> property");
		}
	}
		
	// =========================================================================
	// Public methods
	// =========================================================================
	
	/**
	 * @param string $uri
	 * @return array
	 */
	public function getInferredByUri($uri)
	{
		$uri = $this->nsmgr->resolveUri($uri);
		return $this->resource[$uri];
	}
	
	/**
	 * See getLiteralValue() function.
	 * @param string $uri
	 * @param string $lang
	 * @return string
	 */
	public function getLabel($uri, $lang = null)
	{
		$label = $this->getLiteralValue($uri, 'label', $lang);
		if(empty($label))
			return preg_replace('/.*(\/|#)([^\/#]*)$/', '$2', $this->nsmgr->resolveUri($uri));
		return $label;
	}
	
	/**
	 * See getLiteralValue() function.
	 * @param string $uri
	 * @param string $lang
	 * @return string
	 */
	public function getComment($uri, $lang = null)
	{
		return $this->getLiteralValue($uri, 'comment', $lang);
	}
	
	// =========================================================================
	// Other stuff
	// =========================================================================
	
	const RDF_TYPE = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type';
	const RDFS_DOMAIN = 'http://www.w3.org/2000/01/rdf-schema#domain';
	const RDFS_RANGE = 'http://www.w3.org/2000/01/rdf-schema#range';
	const RDFS_SUBCLASSOF = 'http://www.w3.org/2000/01/rdf-schema#subClassOf';
	const RDFS_LABEL = 'http://www.w3.org/2000/01/rdf-schema#label';
	const RDFS_COMMENT = 'http://www.w3.org/2000/01/rdf-schema#comment';
	const OWL_CLASS = 'http://www.w3.org/2002/07/owl#Class';
	const OWL_INVERSEOF = 'http://www.w3.org/2002/07/owl#inverseOf';
	const OWL_DATATYPE_PROPERTY = 'http://www.w3.org/2002/07/owl#DatatypeProperty';
	const OWL_OBJECT_PROPERTY = 'http://www.w3.org/2002/07/owl#ObjectProperty';
	const OWL_SYMMETRIC_PROPERTY = 'http://www.w3.org/2002/07/owl#SymmetricProperty';
	const OWL_TRANSITIVE_PROPERTY = 'http://www.w3.org/2002/07/owl#TransitiveProperty';
	
	/**
	 * The instance will be obtained in the onBeginDocument event handler.
	 * An instance of a Namespace_Manager must be attached to the dispatcher
	 * before calling the method.
	 * @var Namespace_Manager
	 */
	private $nsmgr;
	
	/**
	 * Array containing information about all resources.
	 * Indexed by URI.
	 * @var array
	 */
	private $resource;
	
	/**
	 * Alias to the $resource array (just references)
	 * Indexed by normalized URI.
	 * @var array
	 */
	private $resource_norm;
	
	/**
	 * Array of OWL classes which are references to $resource array.
	 * Indexed by URI.
	 * @var array
	 */
	private $owlclass;
	
	/**
	 * alias to the $owlclass array (just references)
	 * Indexed by normalized URI.
	 *
	 * @var array
	 */
	private $owlclass_norm;
		
	/**
	 * @param string $uri
	 */
	private function registerResource($uri)
	{
		if(empty($this->resource[$uri]['uri']))
		{
			$this->resource[$uri]['uri'] = $uri;
			$this->resource_norm[ GAMA_Utils::normaliseUri($uri) ] = & $this->resource[$uri];
		}
	}
	
	/**
	 * @param string $uri
	 */
	private function registerOwlClass($uri)
	{
		$this->registerResource($uri);
		if(empty($this->owlclass[$uri]['uri']))
		{
			$this->owlclass[$uri] = & $this->resource[$uri];
			$this->owlclass_norm[ GAMA_Utils::normaliseUri($uri)] = & $this->owlclass[$uri];
			$this->onNewTriple($uri, self::RDF_TYPE, self::OWL_CLASS);
		}
	}
	
	/**
	 * @param string $p
	 * @param string $i
	 */
	private function setInverse($p, $i)
	{
		$this->registerResource($p);
		$this->registerResource($i);
		
		if(empty($this->resource[$p]['inverse']))
		{
			$this->resource[$p]['inverse'] = & $this->resource[$i];
		} elseif($this->resource[$p]['inverse'] !== $this->resource[$i])
		{
			throw new Exception("Iverse property of $p has already been defined {$this->resource[$p]['inverse']} which differs from $i");
		}

		if(empty($this->resource[$p]['domain']))
		{
			$this->resource[$p]['domain'] = & $this->resource[$i]['range'];
		} elseif(empty($this->resource[$i]['range']))
		{
			$this->resource[$i]['range'] = & $this->resource[$p]['domain'];
		} elseif(
			$this->resource[$i]['range'] == $this->resource[$p]['domain'] &&
			$this->resource[$i]['domain'] == $this->resource[$p]['range'] )
		{
			debug("Domain and range of the inverse property $i has already been defined");
		} else
		{
			throw new Exception("Properties $p and $i defined as inverse but found different domain+range");
		}
	}
	
	/**
	 * @param string $s
	 * @param string $o
	 */
	private function addResourceType($s, $o)
	{
		$this->registerResource($s);
		$this->registerResource($o);
		
		// adds the new type only if the same type does not exist
		if(empty($this->resource[$s]['type'][$o]))
		{
			switch($o)
			{
				case self::OWL_SYMMETRIC_PROPERTY:
				case self::OWL_TRANSITIVE_PROPERTY:
					$this->addResourceType($s, self::OWL_OBJECT_PROPERTY);
					break;
				
				case self::OWL_CLASS:
					$this->registerOwlClass($s);
					break;
			}

			$this->resource[$s]['type'][$o] = & $this->resource[$o];
		}
	}
	
	/**
	 * If no language has been selected the method returns
	 * either the value from default language '' or english language 'en'.
	 * In case of english, 'en' language is examined first, then default language ''.
	 * If the special language '*' has been selected the method returns
	 * all values from all languages.
	 * Multiple values are always concatenated with "\n" character.
	 *
	 * @param string $uri
	 * @param string $idx
	 * @param string $lang
	 * @return string
	 */
	private function getLiteralValue($uri, $idx, $lang)
	{
		$r = $this->getResourceByUri($uri);
		if($lang == '*') return $this->implode_md("\n", $r[$idx]);
		if(empty($r[$idx])) return null;

		$l = @$r[$idx][$lang];
		if(empty($l) && $lang == 'en') $l = $r[$idx][null];
		if(empty($l) && empty($lang)) $l = $r[$idx]['en'];
		if(empty($l) && empty($lang)) $l = end($r[$idx]);
		return implode("\n", $l);
	}
	
	/**
	 * Helper function, a multi-dimensional version of
	 * PHP implode function.
	 *
	 * @param string $glue
	 * @param string $list
	 * @return string
	 */
	private function implode_md($glue, $list)
	{
		$out = array();
		foreach($list as $item)
			$out[] = is_array($item) ? $this->implode_md($glue, $item) : $item;
		return $this->implode($glue, $out);
	}
	
}
?>