<?php
/**
 * The output should be displayed progressively line by line 10 times.
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