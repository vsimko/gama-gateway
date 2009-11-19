<?php
/**
 * Collects all triples produced by an RDF_Parser
 * into a single PHP array.
 */
class Triple_Collector implements Triple_Handler_Interface
{
	// =========================================================================
	// Event handlers
	// =========================================================================

	/**
	 * @see Triple_Handler_Interface::onBeginDocument()
	 */
	public function onBeginDocument()
	{
		$this->triples = array();
	}
	
	/**
	 * @see Triple_Handler_Interface::onEndDocument()
	 */
	public function onEndDocument()
	{
		// nothing to do in this class
	}
	
	/**
	 * @see Triple_Handler_Interface::onNewTriple()
	 * @param string $s
	 * @param string $p
	 * @param string|RDFS_Label $o
	 */	
	public function onNewTriple($s, $p, $o)
	{
		$this->triples[] = array($s, $p, $o);
	}
		
	// =========================================================================
	// Other stuff
	// =========================================================================
	
	/**
	 * Collected triples.
	 * @var array()
	 */
	private $triples = array();
	
	/**
	 * @return array
	 */
	public function getTriples()
	{
		return $this->triples;
	}
	
	/**
	 * @return string
	 */
	public function __toString()
	{
		return print_r($this->triples, true);
	}
}
?>