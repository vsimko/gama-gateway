<?php

require_once 'config-endpoint.php';

// =============================================================================
// DEBUG
// =============================================================================
//chdir(dirname(__FILE__));
////$_FILES['file']['tmp_name'] = '/home/vlx/workspace-rdf/GamaSchema/gs-worktypes.owl';
////$_FILES['file']['tmp_name'] = dirname(__FILE__).'/rdf-examples/he_person.rdf';
////$_FILES['file']['tmp_name'] = '/dev/shm/gama-sample-data/he_collective.xml';
//$_FILES['file']['tmp_name'] = '/dev/shm/instants_person.xml';
//$_REQUEST['what'] = 'data';
// =============================================================================

try
{
	if (!isset($_FILES['file']))
	{
		throw new Exception('Maximum post size reached or other nasty file-upload problem.');
	}

	$parser = RDF_Parser_Factory::getParserByLocation($_FILES['file']['tmp_name']);
	
	dispatcher($parser)->attach( $parser );
	dispatcher($parser)->attach( new Triple_Logger );
	
	switch(@$_REQUEST['what'])
	{
		case 'schema':
			dispatcher($parser)->attach( new GAMA_Schema_Loader );
			break;
			
		default:
			dispatcher($parser)->attach( new GAMA_Data_Loader );
			break;
	}
} catch(Exception $e)
{
	render_error_page($e);
}

set_time_limit(0); // disable execution timeout during the loading process
?>
<?php // ==================================================================== ?>
<?php include 'design/page-header.php' ?>
<?php // ==================================================================== ?>
<pre>
<?php
	try
	{
		dispatcher($parser)->onParse();
	} catch (Exception $e)
	{
		echo "Exception: ".$e->getMessage();
	}
?>
</pre>
<?php // ==================================================================== ?>
<?php include 'design/page-footer.php' ?>
<?php // ==================================================================== ?>
