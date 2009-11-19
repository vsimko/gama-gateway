<?php
/**
 * Base class for URIs, Variables and Literals.
 * @author Viliam Simko
 */
abstract class SPARQL_Item
{
	/**
	 * @var array
	 */
	protected $p;
	
	/**
	 * @var SPARQL_Engine
	 */
	protected $engine;
	
	/**
	 * @param array $p
	 * @param SPARQL_Engine $engine
	 */
	public function __construct(array $p, SPARQL_Engine $engine)
	{
		$this->p = $p;
		$this->engine = $engine;
	}
	
	/**
	 * @return string
	 */
	abstract public function getBindValue();

	/**
	 * @return string
	 */
	abstract public function getOutValue();

	/**
	 * @return string
	 */
	abstract public function getRawValue();
	
	/**
	 * @return string
	 */
	abstract public function getLangValue();

	/**
	 * @return string
	 */
	abstract public function getDatatypeBinding($dtUri);

	/**
	 * @return string
	 */
	abstract public function getSpecialBinding($columnName);

	/**
	 * @param $bind
	 */
	public function bindWith($bind)
	{
		$this->engine->joinDatatypeStatement(
			$this->getRawValue(),
			$this->p['type'],
			$bind,
			preg_replace('/\..*/', '', $bind), // table name
			$bind // TODO: should be orderby
			);
	}
	
	/**
	 * @param $bind
	 */
	public function bindWithTable($bind)
	{
		$this->engine->joinStatement(
			$this->getRawValue(),
			$this->p['type'],
			$bind
			);
	}
}
?>