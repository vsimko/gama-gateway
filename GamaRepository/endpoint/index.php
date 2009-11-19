<?php
require_once 'config-endpoint.php';

/**
 * Returns a list of sparql examples 
 *
 * @return array of the examples
 */
function get_sparql_examples()
{
	assert('/* mandatory configuration option */ Config::get("endpoint.sparql.examples")');
	
	$list = array();
	
	foreach(glob(Config::get('endpoint.sparql.examples')) as $fname)
	{
		$basename = basename($fname);
		$content = file_get_contents($fname);
		$title = explode("\n", $content);
		
		$list[$basename] = array(
			'content'	=> htmlspecialchars($content),
			'title'		=> preg_replace('/^# *(.*) *$/', '$1', $title[0]),
		);
	}
	array_multisort($list, SORT_ASC);
	return $list;
}

?>
<?php // ==================================================================== ?>
<?php include 'design/page-header.php' ?>
<?php // ==================================================================== ?>
<h1>GAMA Repository endpoint</h1>
<div>
	Used database: <b><?php echo GAMA_Store::getDatabaseName() ?></b>
</div>

<!-- ======================================================================= -->
<ul>
	<li><a href="visualize.php">Visualize the current schema (on-line version)</a></li>
	<li><a href="../schema/">Get the schema in RDF/XML format</a></li>
	<li><a href="backup.php">Manage database backups</a></li>
	<li>
		<a href="estimate.php">
			Show repository statistics (graphs, classes, propeties ...)
		</a>
	</li>
	<li>
		<a href="../../OaiExport/">Also take a look at the OAI-PMH exporter
		<sup style="color:green;font-size: xx-small;">(new)</sup>
		</a>
	</li>
	<li><a href="../soa/?service=test/Test_Repository_Structure">Check consistency of the data (Experimental)</a></li>
	<li><a href="explore.php">Explore repository from a given resource URI</a>
</ul>

<!-- ======================================================================= -->
<h2>Query operations</h2>

<!-- ================================== -->
<div>
Select an example:
<?php foreach(get_sparql_examples() as $id => $example): ?>
	<div id='<?php echo $id ?>' style='display:none'><?php echo $example['content'] ?></div>
<?php endforeach ?>
<select onchange="setExample(this.value)">
	<option selected="selected">-- choose an example -- </option>
	<?php foreach(get_sparql_examples() as $id => $example): ?>
	<option value="<?php echo $id ?>"><?php echo $example['title'] ?></option>
	<?php endforeach ?>
</select>
</div>

<script type="text/javascript">//<![CDATA[
function setExample(eid)
{
	e = document.getElementById(eid);
	q = document.getElementsByName('query')[0];
	if(e) q.value = e.textContent;
	else q.value = q.defaultValue;
}
//]]></script>
<!-- ================================== -->

<form method="post" action="query.php" enctype="application/x-www-form-urlencoded">
<div>
<textarea name="query" rows="30" cols="120">
<?php if(empty($_REQUEST['query'])): ?>
<?php echo htmlspecialchars(
'# Write your SPARQL code here or choose an example
# from the list above then press the "Submit Query" button
# Be aware of following UNSUPPORTED features:
# - Comments at the end of the SPARQL command
# - Variables in predicates.
#    - allowed:   ?subject <predicate> ?object
#    - forbidden: ?subject ?predicate ?object

PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX gama: <http://gama-gateway.eu/schema/>
PREFIX cache: <http://gama-gateway.eu/cache/>
PREFIX owl: <http://www.w3.org/2002/07/owl#>
PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX mysql: <http://www.mysql.com/>

select * {
  ?subject a ?type
} limit 100
') ?>
<?php else: ?>
<?php echo htmlspecialchars(stripcslashes($_REQUEST['query'])) ?>
<?php endif ?>
</textarea>
<br/>
<select name="format">
	<option value="html" selected="selected">Results in readable HTML format</option>
	<option value="xml">SPARQL XML Results format</option>
</select>
<input type="submit"/>

</div>
</form>

<!-- ======================================================================= -->
<h2>Insert/Update operations</h2>
<div class="note">
	NOTE: We also support <span style="color:red">gzipped</span> files.
</div>

<form action="insert.php" method="post" enctype='multipart/form-data'>
<div>
	<input type="file" name="file"/>
	<input type="submit" value="Update the metadata schema"/>
	<input type="hidden" name="what" value="schema"/>
	<br/>
	<span class="note">
		* Requires the RDF/XML format
	</span>
</div>
</form>
<form action="insert.php" method="post" enctype='multipart/form-data'>
<div>
	<input type="file" name="file"/>
	<input type="submit" value="Insert RDF data"/>
	<input type="hidden" name="what" value="data"/>
	<br/>
	<span class="note">
		* Requires the RDF/XML format
	</span>
</div>
</form>
<form action="siminsert.php" method="post" enctype='multipart/form-data'>
<div>
	<input type="file" name="file"/>
	<input type="submit" value="Define similar manifestations"/>
	<label for="cxb_del">
		<input id="cxb_del" type="checkbox" name="delete" value="1"/>
		delete given similarities instead of inserting them
	</label>
	<br/>
	<span class="note">
		* Requires the RDF/XML format
		<a href="rdf-examples/similar.rdf">example</a>
	</span>
</div>
</form>

<!-- ======================================================================= -->
<h2>Delete operations</h2>
<ul>
	<li>
		<a href="cleanup.php?what=data">
			Cleanup data (delete data, keep schema)
		</a>
	</li>
	<li>
		<a href="cleanup.php?what=all">
			Cleanup all (data + schema)
		</a>
	</li>
	<li>
		<a href="cleanup.php?what=similar">
			Cleanup information about similar manifestations
		</a>
	</li>
</ul>

<?php // ==================================================================== ?>
<?php include 'design/page-footer.php' ?>
<?php // ==================================================================== ?>
