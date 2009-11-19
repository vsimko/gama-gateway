<?php
interface Report_Unknown_Resource_Interface
{
	public function onUnknownClass($uri);
	public function onUnknownProperty($uri);
}
?>