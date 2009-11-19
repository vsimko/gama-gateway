<?php

require_once 'lib/GAMA_Update_Trigger.php';

$trigger = new GAMA_Update_Trigger;
$trigger->setSubject('just testing');
$trigger->setMessage("
This is a testing email message sent by the GAMA Update mechanism.
Somebody executed the ".__FILE__." script
at {$trigger->getScriptUrl('')}
");

//$trigger->notifyByEmail('your@email.here');
//$trigger->notifyByHttpCall('http://your/url/here');

?>