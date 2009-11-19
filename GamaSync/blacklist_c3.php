<?php

require_once 'lib/GAMA_Blacklist.php';

$updater = new GAMA_Blacklist('c3');
$updater->handleHttpRequest($_REQUEST);

?>