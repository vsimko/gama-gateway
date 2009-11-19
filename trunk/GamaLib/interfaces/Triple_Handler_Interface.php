<?php
interface Triple_Handler_Interface
{
	public function onBeginDocument();
	public function onEndDocument();
	
	/**
	 * @param string $s
	 * @param string $p
	 * @param string|RDFS_Label $o
	 */
	public function onNewTriple($s, $p, $o);
}
?>