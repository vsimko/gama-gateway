<?php

require_once 'lib/GAMA_Update_Trigger.php';

$trigger = new GAMA_Update_Trigger;
echo $trigger->getCurrentPhase()."\n";
?>