<?php
/**
 * Base for all SPARQL functions.
 * @author Viliam Simko
 */
abstract class SPARQL_Function
{
	/**
	 * @var SPARQL_Engine
	 */
	protected $engine;
	
	/**
	 * Parameters of the sparql function.
	 * @var array
	 */
	protected $params;
	protected $curParamIndex;
	
	/**
	 * @param $engine
	 * @param $params
	 */
	public function __construct(SPARQL_Engine $engine, array $params)
	{
		$this->engine = $engine;
		$this->params = $params;
		$this->curParamIndex = 0;
	}
	
	/**
	 * Executes the SPARQL function.
	 */
	abstract public function execute();
	
	/**
	 * 
	 * @return boolean
	 */
	protected function hasMoreParameters()
	{
		return !empty($this->params);
	}

	/**
	 * @param $sqlvardef
	 * @return string
	 */
	protected function addHiddenVar($sqlvardef)
	{
		static $hiddenvarseq;
		$varname = 'd_'.(++$hiddenvarseq);
		$this->engine->hiddenVars[$varname] = $sqlvardef.' as '.$varname;
		return $varname;
	}
	
	/**
	 * @param $condition
	 * @return string
	 */
	protected function addHavingCondition($condition)
	{
		$this->engine->condHaving[] = $condition;
	}
	
	const P_VAR = 1;
	const P_LITERAL = 2;
	const P_URI = 4;
	
	static private $mapParamTypes = array(
		'var'		=> self::P_VAR,
		'literal'	=> self::P_LITERAL,
		'uri'		=> self::P_URI,
	);
	
	/**
	 * @param $allowedTypes
	 * @return SPARQL_Item
	 */
	protected function shiftParam($allowedTypes)
	{
		++$this->curParamIndex;
		$p = array_shift($this->params);
		
		if( ! $allowedTypes & @self::$mapParamTypes[ $p["type"] ])
		{
			throw new SPARQL_Engine_Exception(
				"Type '$p[type]' not allowed for parameter #".$this->curParamIndex);
		}
		
		return $this->engine->factoryItem($p);
	}
}
?>