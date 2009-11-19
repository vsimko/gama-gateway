<?php
/**
 * This is the Hello World example.
 */
class Hello_World extends RPC_Service
{
	/**
	 * Example of an required parameter.
	 * @datatype label
	 * @required
	 */
	static $PARAM_EXAMPLE = 'example';
	
	/**
	 * Writes the Hello World string together with the given parameter.
	 */
	public function execute()
	{
		$example = $this->getParam(self::$PARAM_EXAMPLE);
		echo "Hello world : $example\n";
	}
}
?>