<?php

/**
 * Implemented using the State design pattern.
 */
class Resource
{
	// =========================================================================
	// Event handlers
	// =========================================================================
	
	/**
	 * @category dispatcher
	 * @param string $newState
	 */
	public function onStateChanged($newState)
	{
		// never change to parent class (never downgrade)
		if($this->state instanceof $newState)
			return;
		
		$this->state = new $newState($this);
		$this->state->reloadFromStore();
	}
	
	// IMPORTANT: other events are propagated to the state classes
	
	// =========================================================================
	// Context stuff
	// =========================================================================
	
	/**
	 * @var array
	 */
	private $context;
	
	/**
	 * @param string $name
	 * @return mixed
	 */
	public function getContextItem($name)
	{
		return $this->context[$name];
	}
	
	/**
	 * @param string $name
	 * @return boolean
	 */
	public function isSetContextItem($name)
	{
		return isset($this->context[$name]);
	}
	
	/**
	 * @param string $name
	 * @param mixed $value
	 */
	public function setContextItem($name, $value)
	{
		$this->context[$name] = $value;
	}
	
	// =========================================================================
	// Other stuff
	// =========================================================================

	/**
	 * @var Base_State
	 */
	private $state;
	
	/**
	 * Forbidden.
	 */
	final protected function __clone(){}
	
	/**
	 * @param string $uri
	 */
	public function __construct($uri)
	{
		// INVARIANT: each resource has a URI
		$this->setContextItem('uri', $uri);
		
		// INVARIANT: each resource listens to own events
		dispatcher($this)->attach($this);

		// setting the default state
		$this->state = new OWL_Class($this);
	}
	
	/**
	 * Function calls are propagated to the state instances.
	 *
	 * @param string $name
	 * @param array $args
	 * @return mixed
	 */
	public function __call($name, $args)
	{
		return call_user_func_array(array($this->state, $name), $args);
	}
	
	/**
	 * Used with the dispatcher.
	 * @param string $name
	 * @return boolean
	 */
	public function __methodExists($name)
	{
		return method_exists($this->state, $name);
	}
	
	/**
	 * Textual representation for debugging.
	 * @return string
	 */
	public function __toString()
	{
		return "$this->state";
	}
}
?>