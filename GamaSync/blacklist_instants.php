<?php

require_once 'lib/GAMA_Blacklist.php';

$updater = new GAMA_Blacklist('instants');
$updater->handleHttpRequest($_REQUEST);

?>