<?php

require_once '../config.php';

$what = $_REQUEST['what'];

if($what == 'data')
{
	GAMA_Data_Loader::cleanupData();
} elseif($what == 'similar')
{
	echo "<pre>All similarities deleted from database: ".GAMA_Store::getDatabaseName()."</pre>\n";
	GAMA_Store::singleton()->sql('delete from SIMILARITY');
} elseif($what == 'all')
{
	GAMA_Store::singleton()->rebuildStore();
} else
	die("Don't know what to cleanup");

?>
done
