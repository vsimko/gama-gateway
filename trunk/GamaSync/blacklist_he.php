<?php

require_once 'lib/GAMA_Blacklist.php';

$updater = new GAMA_Blacklist('he');
$updater->handleHttpRequest($_REQUEST);

?>