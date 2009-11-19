<?php
require_once 'config-endpoint.php';

$queryString = @$_REQUEST['query'];	
$resultFormat = & $_REQUEST['format']; //xml(default), html

// remove unnecessary slashes
if(get_magic_quotes_gpc())
{
	$queryString = stripslashes($queryString);
}

// remove unnecessary leading spaces
$queryString = preg_replace('/^\t+/m','', $queryString);

try
{
	if(empty($queryString))
	{
		throw new Exception('Query was empty');
	}

	set_time_limit(Config::get('query.sql.timeout'));
	if(Simple_Load_Engine::isValidSimpleLoadQueryString($queryString))
	{
		$engine = new Simple_Load_Engine;
		$engine->useQueryString( $queryString );
	} else
	{
		$engine = new SPARQL_Engine;
		$engine->useSparql($queryString);
	}
	
	$engine->setResultHandler( new SPARQL_XML_Results_Renderer );
	
	set_time_limit(Config::get('query.request.timeout'));
	
	if(empty($resultFormat) || $resultFormat == 'xml')
	{
		header('Content-type: text/xml');
		$engine->runQuery();
		exit;
	}
	
	// collect the results
	ob_start();
	$engine->runQuery();
	$queryResults = ob_get_clean();
	
} catch(Exception $e)
{
	set_time_limit(Config::get('query.request.timeout'));
	render_error_page($e);
}

function make_explore_link($x)
{
	$y = htmlspecialchars_decode($x[2]);
	return $x[1].'<a href="explore.php?uri='.urlencode($y).'">'.htmlspecialchars($y).'</a>'.$x[3];
}

// turn all URIs to links using the explore.php
$queryResults = preg_replace_callback(
	'/(&lt;uri&gt;)(.*)(&lt;\/uri&gt;)/Ui',
	'make_explore_link',
	htmlspecialchars($queryResults)
	);

?>
<?php // ==================================================================== ?>
<?php include 'design/page-header.php' ?>
<?php // ==================================================================== ?>
<div>SPARQL query:</div>
<hr/>
<?php $query_num_lines = (int) count(explode("\n", $queryString)) ?>
<form method="post" action="" enctype="application/x-www-form-urlencoded">
<div>
<textarea name="query" rows="<?php echo max(3, $query_num_lines) ?>" cols="120"><?php echo htmlspecialchars($queryString) ?></textarea>
<input type="hidden" name="format" value="<?php echo htmlspecialchars($resultFormat) ?>">
<input type="submit" value="Query Again"/>
</div>
</form>

<div>
	SPARQL XML results:
	<a href="?format=<?php echo urlencode($resultFormat) ?>&query=<?php echo urlencode($queryString)?>">
		(Here is the link to this result set)
	</a>
</div>
<hr/>
<pre><?php echo $queryResults ?></pre>
<?php // ==================================================================== ?>
<?php include 'design/page-footer.php' ?>
<?php // ==================================================================== ?>
