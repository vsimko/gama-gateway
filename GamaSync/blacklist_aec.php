<?php

require_once 'lib/GAMA_Blacklist.php';

$updater = new GAMA_Blacklist('aec');
$updater->handleHttpRequest($_REQUEST);

?>