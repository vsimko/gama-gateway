<?php

require_once 'config-endpoint.php';

// =============================================================================
// DEBUG
// =============================================================================
//chdir(dirname(__FILE__));
//$_FILES['file']['tmp_name'] = dirname(__FILE__).'/rdf-examples/similar.rdf';
// =============================================================================

try
{
	// check the uploaded file
	if (!isset($_FILES['file']))
	{
		throw new Exception('Maximum post size reached or invalid file');
	}
	
	// get the parser depending on the file format (this may throw an exception)
	$parser = RDF_Parser_Factory::getParserByLocation($_FILES['file']['tmp_name']);
	
	dispatcher($parser)->attach($parser);
	dispatcher($parser)->attach( new GAMA_Similarity_Handler );
	
	// set the delete mode if needed
	if(!empty($_REQUEST['delete']))
	{
		dispatcher($parser)->onSetDeleteMode();
	}
	
} catch(Exception $e)
{
	render_error_page($e);
}

// execution timeout diabled while processing
set_time_limit(0);

?>
<?php // ==================================================================== ?>
<?php include 'design/page-header.php' ?>
<?php // ==================================================================== ?>
<pre>
<?php dispatcher($parser)->onParse() ?>
</pre>
<?php // ==================================================================== ?>
<?php include 'design/page-footer.php' ?>
<?php // ==================================================================== ?>
