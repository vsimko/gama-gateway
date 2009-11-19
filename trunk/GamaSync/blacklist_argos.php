<?php

require_once 'lib/GAMA_Blacklist.php';

$updater = new GAMA_Blacklist('argos');
$updater->handleHttpRequest($_REQUEST);

?>