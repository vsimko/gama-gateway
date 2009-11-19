<?php

require_once 'lib/GAMA_Blacklist.php';

$updater = new GAMA_Blacklist('filmform');
$updater->handleHttpRequest($_REQUEST);

?>