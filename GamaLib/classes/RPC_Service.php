<?php

/**
 * The service signature is taken from the comment strings. 
 * Annotation inside the comments of static variables in derived classes
 * are taken as a description of parameters. Also additional annotations must
 * be defined within these comments.
 * - @required or @optional
 * - @datatype <string>
 */
abstract class RPC_Service
{
	const SERVICE_PARAM_NAME = 'service';
	const HELP_PARAM_NAME = 'help';
	
	/**
	 * @var RPC_Server
	 * @noparam
	 */
	protected $serverContext;
	
	/**
	 * @var string
	 * @noparam
	 */
	protected $serviceName;
	
	/**
	 * @param string $serviceName
	 * @param RPC_Server $serverContext
	 */
	public function __construct($serviceName, RPC_Server $serverContext)
	{
		$this->serviceName = $serviceName;
		$this->serverContext = $serverContext;
	}
	
	/**
	 * Contains service parameters.
	 * @var array
	 */
	private $params = array();
	
	/**
	 * @param array $params
	 */
	public function setServiceParams(array &$params)
	{
		//debug($params);
		$this->params = &$params;
	}
	
	/**
	 * Exceptions will be transformed to HTTP headers
	 * Comment of the method implemented in the derived class is taken as a
	 * description of the result format.
	 * @param array $params
	 */
	abstract public function execute();
	
	/**
	 * @return array
	 */
	final public function getSignature()
	{
		$ret = array(
			'name' => $this->serviceName,
			'description' => Simple_Annotation::createFromClassComment($this)->getCommentText(),
			'result' => Simple_Annotation::createFromMethodComment($this, 'execute')->getCommentText()
		);
		
		foreach(get_class_vars(get_class($this)) as $varName => $paramName)
		{
			$varAnnot = Simple_Annotation::createFromPropertyComment($this, $varName);

			// we use a special annotation @noparam to disable certain variables from the service
			if(!$varAnnot->hasAnnotation('noparam'))
			{
				$ret['params'][$paramName] = array(
					'name'	=> $paramName,
					'description' => $varAnnot->getCommentText(),
					'datatype'	=> $varAnnot->getAnnotationValue('datatype'),
					'default'	=> @$varAnnot->getAnnotationValue('default'),
					'required'	=> $varAnnot->hasAnnotation('required'),
					);
			}
		}
		return $ret;
	}
	
	/**
	 * Indicates a required parameter.
	 * To be used as the second parameter of the getParam method.
	 */
	const REQUIRED = true;
	
	/**
	 * Indicates an optional parameter.
	 * To be used as the second parameter of the getParam method.
	 */
	const OPTIONAL = false;
	
	private $calledParams = array();
	
	/**
	 * Extracts a specific service parameter with an optional assertion.
	 * @param string $paramName
	 * @param boolean $isRequired
	 * @return mixed
	 */
	protected function getParam($paramName, $isRequired = self::REQUIRED)
	{
		if(isset($this->params[$paramName]))
		{
			$paramValue = & $this->params[$paramName];
		} else
		{
			if(!isset($this->calledParams[$paramName]))
			{
				$this->calledParams[$paramName] = count($this->calledParams);
			}
			
			$idx = $this->calledParams[$paramName];
			
			if($isRequired == self::REQUIRED && !isset($this->params[$idx]))
			{
				$signature = $this->getSignature();
				throw new Exception(
					"Required parameter is missing: $paramName = ".
					$signature['params'][$paramName]['description'] );
			}
			
			$paramValue = & $this->params[$idx];
		}
		return $paramValue;
	}
		
	/**
	 * Client to the local RPC services.
	 * @return RPC_Local_Client
	 */
	protected function getRpcClient()
	{
		return $this->serverContext->getLocalRpcClient();
	}
}
?>