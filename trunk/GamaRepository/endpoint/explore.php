<?php
require_once 'config-endpoint.php';

$resourceUri = @$_REQUEST['uri'];

class Explorer_Renderer implements SPARQL_Result_Handler_Interface
{
	function onFoundResult($caller, array $record)
	{
		echo "<tr>\n";
		foreach($this->outputVariables as $varname)
		{
			if(empty($record[$varname.'_dt']))
			{
				$uri = $record[$varname];
				echo "<td class='uricell'>";
				echo "<a href='?uri=".urlencode($uri)."'>".htmlspecialchars($uri)."</a>";
				echo "</td>\n";
			} else
			{
				echo "<td class='literalcell'>";
				echo '<span class="lang">'.htmlspecialchars($record[$varname.'_lang']).'&#9873;</span>';
				echo str_replace("\n", "<br/>", strip_tags($record[$varname], '<br><b><ul><li><u><img><i><hr><a>'));
				echo "</td>\n";
			}
		}
		echo "</tr>\n";
	}
	
	private $outputVariables;
	function onBeginResults($caller, array $outputVariables, array $debugString = array())
	{
		$this->outputVariables = array('graph', 'predicate', 'object');
		
		echo "<table>\n";
		foreach($this->outputVariables as $varname)
		{
			echo '<th>'.htmlspecialchars($varname).'</th>';
		}
		echo "\n";
	}
	
	function onEndResults($caller)
	{
		echo "</table>\n";
	}
	
	function onComment($commentString){}
}

try
{
	if(!empty($_REQUEST['id']))
	{
		$r = Resource_Manager::singleton()->getResourceUrisById($_REQUEST['id']);
		$resourceUri = @$r[0];
	}
	
	echo '<form action="">';
	echo 'Start from URI:<input style="width:600px" type="text" name="uri" value="'.htmlspecialchars($resourceUri).'"/>
			or ID: <input style="width:50px"type="text" name="id"/>&nbsp;
			<input type="submit" value="Explore"/>';
	
	if(empty($resourceUri))
	{
		throw new Exception('No resource URI provided');
	} else
	{
		echo '&nbsp;<span onclick="javascript:toggleBugReport()" style="cursor: pointer" title="Report a bug">&#9888;</span>';
	}
	
	echo '</form>';
	
	set_time_limit(Config::get('query.sql.timeout'));
	
	$engine = new Simple_Load_Engine;
	$engine->useQueryString('
		simple load
		'.GAMA_Utils::escapeSparqlUri($resourceUri).'
		properties
		*
	');
	
	$engine->setResultHandler( new Explorer_Renderer );
	
	set_time_limit(Config::get('query.request.timeout'));
		
	// collect the results
	ob_start();
	$engine->runQuery();
	$queryResults = ob_get_clean();
	
} catch(Exception $e)
{
	set_time_limit(Config::get('query.request.timeout'));
	render_error_page($e);
}

?>
<?php // ==================================================================== ?>
<?php include 'design/page-header.php' ?>
<?php // ==================================================================== ?>

<h1>GAMA Repository resource explorer</h1>
<div>
	Used database: <b><?php echo GAMA_Store::getDatabaseName() ?></b>	
</div>

<!-- ============= Bug Reporting ============================= -->
<script type="text/javascript">//<![CDATA[
function toggleBugReport()
{
	e = document.getElementById('bugReportDiv');
	e.style.display = (e.style.display != 'block') ? 'block' : 'none';
}
//]]</script>
<div id="bugReportDiv" style="display:none; background-color: #ffeecc; margin: 2px; border: solid 1px black;padding: 20px;">
	<form action="" method="POST">
	<textarea rows="4" cols="40" style="width:100%;">Bug reporting does not work at the moment !!! </textarea>
	<br/><input type="submit" value="Send the bug report now"/>
	</form>
	
	<p/>
	<b>The email will be sent to the following addresses:</b><br/>
	<?php foreach( GAMA_Utils::getBugReportEmailsByUri($resourceUri) as $emailAddress ): ?>
	<?php echo htmlspecialchars($emailAddress) ?><br/>
	<?php endforeach ?>
</div>
<!-- ========================================================= -->

<div><?php echo $queryResults ?></div>

<?php // ==================================================================== ?>
<?php include 'design/page-footer.php' ?>
<?php // ==================================================================== ?>
