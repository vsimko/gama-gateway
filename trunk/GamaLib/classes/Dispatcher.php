<?php

// Implementation of the spl_object_hash for PHP < 5.2.0
// see: http://php.net/manual/en/function.spl-object-hash.php
if (!function_exists('spl_object_hash')) {
    function spl_object_hash($object) {
        if (!is_object($object)) {
            trigger_error(__FUNCTION__ . "() expects parameter 1 to be object", E_USER_WARNING);
            return null;
        }
        if (!isset($object->__oid__)) {
            $object->__oid__ = uniqid();
        }
        return $object->__oid__;
    }
}

/**
 * Implementation of a simplified signal-slot design pattern.
 * 
 * A dispatcher is an entity connected to an existing object
 * (or having a system-wide textual name) through which interrested subject
 * may carry out the communication. It allows for loosly coupling of the
 * components within the application.
 * 
 * - One of the main design goals was the simplicity of use. It might have
 *   been implemented more robustly with lots of features, however,
 *   the this should be the minimal yet powerful implementation of a loosly
 *   coupled communication framework. 
 * 
 * - A dispatcher is accessible through the dispatcher($instance) function.
 * 
 * - Subjects can be attached to the dispatcher dynamically by calling the
 *   "attach" method and detached by "detach" method.
 * 
 * - A dispatcher can be shared by multiple subjects using the "createLink"
 *   method.
 * 
 * - In case of a function-call where the function may return a value, such
 *   values are automatically aggregated from all the subjects and stored into
 *   an array indexed by the spl_object_hash. This also means that the result
 *   will always be an array.
 */
class Dispatcher
{
	/**
	 * List of attached subjects indexed by their spl_object_hash. 
	 * @var array
	 */
	private $attachedSubjects = array();
	
	/**
	 * A dispatcher knows its main container. A container is a class which
	 * holds the dispatcher reference. Multiple classes may share the same
	 * dispatcher. However, only the first class, which created the dispatcher
	 * instance, is stored in this property.
	 * @var string|object
	 */
	private $container;
	
	/**
	 * The container may be as class or a string identifier.
	 * @return object|string
	 */
	public function getContainer()
	{
		return $this->container;
	}
	
	/**
	 * This static property holds the reference to the last dispatcher which
	 * processed the __call method. In a function call produced by an event
	 * we can use the reference to emit further events using the same dispatcher.
	 * @var Dispatcher
	 */
	static private $currentDispatcher = null;	
	
	/**
	 * Subject classes may use this method to obtain the reference to the
	 * current dispatcher from which the event was emitted.
	 * @return Dispatcher
	 */
	static public function getCurrent()
	{
		return self::$currentDispatcher;
	}
	
	/**
	 * @param string|object $container
	 */
	public function __construct($container)
	{
		$this->container = $container;
	}
	
	/**
	 * Connects the subject to the dispatcher.
	 * @param object $subject
	 */
	public function attach($subject)
	{
		assert('/* only an object instance can be attached to the dispatcher */ is_object($subject)');
		assert('/* cannot attach the same subject multiple times */ !isset($subject->attachedTo[spl_object_hash($this)])');

		$subject->attachedTo[spl_object_hash($this)] = $this;
		$this->attachedSubjects[spl_object_hash($subject)] = $subject;
	}
	
	/**
	 * Disconnects the subject from the dispatcher.
	 * @param object $subject
	 */
	public function detach($subject)
	{
		assert('/* only an object instance can be used with the dispatcher */ is_object($subject)');

		if( ! isset($this->attachedSubjects[spl_object_hash($subject)]) )
		{
			throw new Exception('No such subject connected to this dispatcher');
		}
			
		unset($this->attachedSubjects[spl_object_hash($subject)]);
	}
	
	/**
	 * Links the existing dispatcher to the new subject so that it shares the
	 * same instance of the dispatcher with the original subject.
	 * 
	 * This allows us to prepare a dispatcher and install it to newly created
	 * subjects.
	 *
	 * @param string|object $newContainer
	 */
	public function createLink($newContainer)
	{
		if(is_object($newContainer))
		{
			if(isset($newContainer->dispatcher))
			{
				throw new Exception('This object already has a dispatcher');
			}
				
			$newContainer->dispatcher = $this;
			return;
			
		} elseif(is_string($newContainer))
		{
			global $DISPATCHERS;
			$dispatcher = & $DISPATCHERS[$newContainer];
			if(!empty($dispatcher))
			{
				throw new Exception('This dispatcher already exists');
			}
			
			$dispatcher = $this;
			return;
		}
		
		assert('/* Unsupported dispatcher container */');
		exit;
	}
	
	/**
	 * Call an arbitrary function on all subjects connected to the dispatcher.
	 * 
	 * One of the advantages of having a dispatcher calling the functions
	 * is that the interrested subjects could implement just a subset of the
	 * functions.
	 * 
	 * For instance, if the dispatcher were used as a type of a communication
	 * channel (let's say 4 functions), we could easily connect an instance of
	 * a "Logger" class in order to monitor just a subset of the communication.
	 * Our Logger could implement 2 of the 4 functions.
	 * 
	 * This is the reason why the dispatcher does not require methods to be
	 * implemented by the connected subject during the call.
	 * Whether the method exists is checked using the PHP built-in function
	 * method_exists or using a special (magic) method __methodExists.
	 * The latter is especially useful when using __call method in subjects.
	 * 
	 * @param string $name
	 * @param array $args
	 * @return mixed
	 */
	public function __call($name, $args)
	{
		$output = array();

		// Before the event is passed to the attached subjects, we store
		// the reference to the current dispatcher which may be accessed
		// by the subjects.
		$oldDispatcher = self::$currentDispatcher;
		self::$currentDispatcher = $this;
		
			foreach($this->attachedSubjects as $subject)
			{
				if( method_exists($subject, $name)
					|| (method_exists($subject, '__methodExists') && $subject->__methodExists($name)) )
				{
					$output[spl_object_hash($subject)] = call_user_func_array( array($subject, $name), $args);
				}
			}
			
		// revert to the previous value
		self::$currentDispatcher = $oldDispatcher;
		
		// if there are multiple return values they will be returned as array
		if(empty($output))
		{
			return null;
		}
		if(count($output) == 1)
		{
			return reset($output);
		}
		return $output;
	}
}

/**
 * Contains named dispatchers.
 */
if(empty($DISPATCHERS))
{
	$DISPATCHERS = array();
}

/**
 * Dispatcher factory.
 * @param mixed $container
 * @return Dispatcher
 */
function dispatcher($container = null)
{
	if(is_object($container))
	{
		if(empty($container->dispatcher))
		{
			$container->dispatcher = new Dispatcher($container);
		}

		return $container->dispatcher;
	}
	
	elseif(is_string($container))
	{
		global $DISPATCHERS;
		$dispatcher = & $DISPATCHERS[$container];

		if(empty($dispatcher))
		{
			$dispatcher = new Dispatcher($container);
		}			
		return $dispatcher;
	}
	
	elseif(empty($container))
	{
		$dispatcher = Dispatcher::getCurrent();
		if(empty($dispatcher))
		{
			return dispatcher('default');
		}
		return $dispatcher;
	}
	
	assert('/* Unsupported dispatcher container */');
	exit;
}

?>