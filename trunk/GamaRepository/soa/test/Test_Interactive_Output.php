<?php
/**
 * The output should be displayed progressively.
 * If you just see the output rendered instantly after 10 seconds, you should
 * update your php.ini configuration by changing the paramter
 * 
 * output_buffering = Off
 * 
 */
class Test_Interactive_Output extends RPC_Service
{
	/**
	 * 5 lines
	 */
	public function execute()
	{
		ob_implicit_flush(true);
		
		@header('Content-type: text/plain');
		
    	for($i=1; $i<=4; ++$i)
		{
			echo "Sending line #$i\n";
			sleep(1);
		}
		
		echo "Sending chars:";
		for($i=1; $i<=4; ++$i)
		{
			echo "X";
			sleep(1);
		}
		echo "\n";
		
		echo "done";
	}
}
?>