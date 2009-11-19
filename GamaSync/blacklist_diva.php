<?php

require_once 'lib/GAMA_Blacklist.php';

$updater = new GAMA_Blacklist('diva');
$updater->handleHttpRequest($_REQUEST);

?>