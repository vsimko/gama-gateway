<?php

require_once 'lib/GAMA_Blacklist.php';

$updater = new GAMA_Blacklist('monte');
$updater->handleHttpRequest($_REQUEST);

?>