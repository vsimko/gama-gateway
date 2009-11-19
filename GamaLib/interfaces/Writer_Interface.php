<?php
interface Writer_Interface
{
	public function onBeginWrite();
	public function onEndWrite();
	
	/**
	 * Write all remaining triples into the stream.
	 * This should clear the internal tripple buffer.
	 */
	public function onFlushWriteBuffer();
	
	/**
	 * @param string $outputLocation
	 */
	public function setOutputLocation($outputLocation);
	
	/**
	 * @param string $baseUri
	 */
	public function setBaseUri($baseUri);
}
?>