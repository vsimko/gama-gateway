<?php

/**
 * Write useful message on each triple.
 */
class Triple_Logger implements Triple_Handler_Interface, Report_Unknown_Resource_Interface
{
	private $numTriples;
	private $timestamp;

	public function onBeginDocument()
	{
		$this->numTriples = 0;
		$this->timestamp = time() + microtime();
	}
	
	public function onEndDocument()
	{
		$taken = round(time() + microtime() - $this->timestamp, 4);
		echo "Triples processed in: $taken second(s)\n";
	}
	
	public function onNewTriple($s, $p, $o)
	{
		$this->numTriples++;
		echo "[$this->numTriples] Predicate: $p\n";
	}
	
	public function onUnknownClass($uri)
	{
		echo "Unknown class detected: $uri\n";
	}
	
	public function onUnknownProperty($uri)
	{
		echo "Unknown property detected: $uri\n";
	}
	
	public function onErrorMessage($msg)
	{
		echo "Error detected: $msg\n";
	}
}
?>