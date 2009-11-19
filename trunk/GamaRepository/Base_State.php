<?php

class Resource_State_Exception extends Exception {}

/**
 * Implementation of the state pattern.
 * Works in a conjunction with Resource class.
 * It is not possible to install dispatcher inside this hierarchy.
 * States should use the $handler as their dispatcher.
 */
abstract class Base_State
{
	// =========================================================================
	// Main public methods
	// =========================================================================

	/**
	 * URI is a world-wide textual identifier
	 * @return string
	 */
	public function getUri()
	{
		return $this->handler->getContextItem('uri');
	}
	
	/**
	 * The ID is a local unique identifier.
	 *
	 * @return string|integer
	 */
	public function getID()
	{
		return $this->handler->getContextItem('id');
	}
	
	/**
	 * @param boolean $demandStored if true an exception is thrown if not stored
	 * @return boolean
	 */
	final public function isInStore($demandStored = false)
	{
		if(empty($this->id))
		{
			if($demandStored)
			{
				throw new Resource_State_Exception(
					'Resource not in the repository : '. $this->getUri() );
			} else
			{
				return false;
			}
		}
		return true;
	}
	
	// =========================================================================
	// Event handlers
	// =========================================================================
		
	/**
	 * Reload metadata from the repostiory.
	 */
	public function reloadFromStore()
	{
		assert('/* uri must be defined */ $this->uri');

		// load ID and type in a single query
		list($id, $typeId) = GAMA_Store::singleton()
			-> sql('select id, type from RESOURCE where uri=?', $this->uri)
			-> fetch(PDO::FETCH_NUM);
		
		// this is necessary because $this->id calls the magic function __set()
		$this->id = $id;
		
		// get type URIs from the given type ID 
		$typeUris = Resource_Manager::singleton()->getResourceUrisById($typeId);
		
		$knownProperties = array(
			GAMA_Store::RDF_PROPERTY_URI,
			GAMA_Store::OWL_ANNOTATION_PROPERTY_URI,
			GAMA_Store::OWL_DATATYPE_PROPERTY_URI,
			GAMA_Store::OWL_OBJECT_PROPERTY_URI,
			GAMA_Store::OWL_INVERSE_FUNCTIONAL_PROPERTY_URI,
			GAMA_Store::OWL_TRANSITIVE_PROPERTY_URI,
			GAMA_Store::OWL_SYMMETRIC_PROPERTY_URI,
			GAMA_Store::GAMA_EQUIVALENCE_PROPERTY_URI,
			);

		if(array_intersect($knownProperties, $typeUris))
		{
			dispatcher($this->handler)->onStateChanged('RDF_Property');
		} else
		{
			dispatcher($this->handler)->onStateChanged('OWL_Class');
		}
	}
	
	/**
	 * Adds resource into the store if necessary.
	 */
	abstract public function createInStore();

	/**
	 * Permanently delete the resource from the repository.
	 */
	abstract public function deleteFromStore();
	
	
	// =========================================================================
	// Other stuff
	// =========================================================================
	
	/**
	 * Dispatcher is not allowed in state classes.
	 * Only the Resource class holds the dispatcher
	 * @var Dispatcher
	 */
	protected $dispatcher = 'not allowed';
	
	/**
	 * State classes cannot be directly attached to the dispatcher
	 * because it would directly contradict the state design pattern.
	 * After the state has been changed some dispatchers would be holding
	 * the reference to the old state.
	 * 
	 * A solution to this problem is to attach the master Resource to the
	 * dispatcher and let the function calls to be passed through the
	 * PHP's __call mechanism to the state classes.
	 * 
	 * The master Resource class could also filter some of the calls by
	 * implementing them.
	 * 
	 * @var array
	 */
	protected $attachedTo = 'not allowed';
	
	/**
	 * Reference to the handler resource
	 * @var Resource
	 */
	protected $handler;
		
	/**
	 * The instance of the master Resource class is passed as the only
	 * parameter of the constructor. It is then stored in the $handler
	 * property and allows the class to access the handler's context.
	 * @param Resource $handler
	 */
	final public function __construct(Resource $handler)
	{
		$this->handler = $handler; 
	}
	
	/**
	 * Creating multiple copies of the same state instance is not allowed.
	 * Only the master Resource class handles the creation and destruction
	 * of the state instance.
	 */
	final protected function __clone() {}
	
	/**
	 * All other properties can be found in the context rather than in
	 * the state class itself. This allows for sharing the context
	 * throughout subsequent states.
	 * @param string $name
	 * @return mixed
	 */
	protected function __get($name)
	{
		return $this->handler->getContextItem($name);
	}
	
	/**
	 * All other properties can be found in the context.
	 * @param string $name
	 * @param mixed $value
	 */
	protected function __set($name, $value)
	{
		$this->handler->setContextItem($name, $value);
	}
	
	/**
	 * @param string $name
	 * @return boolean
	 */
	protected function __isset($name)
	{
		return $this->handler->isSetContextItem($name);
	}
	
	/**
	 * Textual representation for debugging.
	 * @return string
	 */
	public function __toString()
	{
		$out = get_class($this).' '.$this->uri;
		$out .= $this->isInStore()
			? " with ID:$this->id"
			: " does not exist in the repository";

		return $out;
	}
}

?>