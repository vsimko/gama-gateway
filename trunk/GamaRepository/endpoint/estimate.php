<?php
require_once 'config-endpoint.php';
?>
<?php // ==================================================================== ?>
<?php include 'design/page-header.php' ?>
<?php // ==================================================================== ?>
<h1>GAMA Repository statistics</h1>
<div>
	Used database: <b><?php echo GAMA_Store::getDatabaseName() ?></b>
	&#9655; <a href="?orderby=updated#props"> show last update</a>
	
</div>

<!-- ======================================================================= -->
<h2>Statments of named graphs</h2>
<div class="intro">
	During the insert operation, all statements are associated with a specific
	graph name. In case of an RDF/XML file, the graph name is defined by the
	<b>xml:base</b> attribute of the <b>rdf:RDF</b> root XML element.
	<p/>
	This section provides an overview of how many statements are
	physically stored in the database from every graph.
	<p/>
	The concept of separate graphs provides an easy yet powerful mechanism
	for identification and manipulation of statements. A graph with all its
	statements can easily be deleted using the SPARQL DELETE statement.
	<p/>
	<i>Example:</i> DELETE FROM &lt;http://desired-graph-name&gt;
	<p/>
</div>
<?php $stats = new Stats_Graphs ?>
<table id="graphs">
	<tr>
		<th>Graph Name</th>
		<th>Number of statements</th>
	</tr>
	<?php foreach($stats->numStmt as $graphName => $graphStmtCount): ?>
	<tr>
		<th><?php echo $graphName ?></th>
		<?php if($graphStmtCount > 0): ?>
			<td><?php echo $graphStmtCount ?></td>
		<?php else: ?>
			<td style="color:red">empty</td>
		<?php endif ?>
	</tr>
	<?php endforeach ?>
</table>

<!-- ======================================================================= -->
<h2>Instances of classes</h2>
<div class="intro">
	This section describes the distribution of domains.
	<p/>
	Each statement stored in the repository consists of 3 parts:
	<b>subject</b>, <b>predicate</b>, <b>object</b>.
	The subject is determined by the domain definition of the property.
	For instance, the domain of the property <b>gama:person_name</b>
	is the class <b>gama:Person</b>. All statements of this property have the
	subject as an instance of the gama:Person class.
	<p/>
	<i>Example:</i> http://WoodyVasulka&nbsp;gama:person_name&nbsp;"Woody"
	<p/>
</div>
<?php $stats = new Stats_Classes ?>
<table id="classes">
	<tr>
		<th>Class URI</th>
		<th>Number of instances</th>
	</tr>
	<?php foreach($stats->numStmt as $classUri => $numInstances): ?>
	<tr>
		<th><?php echo $classUri ?></th>
		<td><?php echo $numInstances ?></td>
	</tr>
	<?php endforeach ?>
</table>

<!-- ======================================================================= -->
<h2>Similar media</h2>
<div class="intro">
	This section describes similar media. The information is stored in a
	separate table with a simple structure:
	<b>manifestation, shot, similar manifestation, weight, best shot</b>
	
</div>
<?php $stats = new Stats_Similar_Media ?>
<table>
	<tr>
		<th>
			Number of defined similarities<br/>
			(similar media based on keyframes)
		</th>
		<td><?php echo $stats->numSimilarMedia ?></td>
	</tr>
</table>

<!-- ======================================================================= -->
<h2>Properties and their statements</h2>
<?php $stats = new Stats_Properties ?>
<table id="props">
	<tr>
		<th>Total number of statements:</th>
		<td colspan="2">
			<?php echo $stats->getTotalStatements() ?>&#9660;
		</td>
	</tr>
	<tr>
		<th>Subtotals:</th>
		<td><?php echo $stats->getSumStored() ?>&#9660;</td>
		<td><?php echo $stats->getSumInferred() ?>&#9660;</td>
	</tr>
	<tr>
		<th><a href="?#props">Property URI</a></th>
		<th><a href="?orderby=stored#props">Stored stmt.</a></th>
		<th><a href="?orderby=inferred#props">Inferred stmt.</a></th>
		<th><a href="?orderby=numDbRows#props">Used db rows</a></th>
		<th><a href="?orderby=avgRowLength#props">Avg. row length</a></th>
		<th><a href="?orderby=dataLength#props">Data length</a></th>
		<th><a href="?orderby=indexLength#props">Index length</a></th>
		<th><a href="?orderby=created#props">Created</a></th>
		<th><a href="?orderby=updated#props">Updated</a></th>
	</tr>
	<?php foreach($stats->getPropertyUris(@$_REQUEST['orderby']) as $propertyUri): ?>
	<tr>
		<th class="uricell"><?php echo $propertyUri ?></th>
		<td><?php echo @$stats->stored[$propertyUri] ?></td>
		<td><?php echo (int) @$stats->inferred[$propertyUri] ?></td>
		
		<td><?php echo @$stats->numDbRows[$propertyUri] ?></td>
		<td><?php echo @$stats->avgRowLength[$propertyUri] ?></td>
		<td><?php echo @$stats->dataLength[$propertyUri] ?></td>
		<td><?php echo @$stats->indexLength[$propertyUri] ?></td>
		<td class="datecell"><?php echo @$stats->created[$propertyUri] ?></td>
		<td class="datecell"><?php echo @$stats->updated[$propertyUri] ?></td>
	</tr>
	<?php endforeach ?>
</table>

<!-- ======================================================================= -->
<h2><a name="atests">Additional tests</a></h2>
<ul>

<li><a href="query.php?format=html&query=%23+Works+without+creation+date%0D%0APREFIX+gama%3A+%3Chttp%3A%2F%2Fgama-gateway.eu%2Fschema%2F%3E%0D%0ASELECT+distinct+%3Fwork+%7B%0D%0A++%3Fwork+a+gama%3AWork.%0D%0A++OPTIONAL+%7B%0D%0A++++%3Fwork+gama%3Awork_created+%3Fc.%0D%0A++%7D%0D%0A++FILTER+gama%3AisEmpty%28%3Fc%29%0D%0A%7D%0D%0A">
	List of Works/Events without creation dates
</a></li>
<li><a href="query.php?format=html&query=%23+List+of+persons+without+life_span%0D%0APREFIX+gama%3A+%3Chttp%3A%2F%2Fgama-gateway.eu%2Fschema%2F%3E%0D%0ASELECT+DISTINCT+%3Fp+%7B%0D%0A++%3Fp+a+gama%3APerson.%0D%0A++OPTIONAL+%7B%0D%0A++++%3Fp+gama%3Alife_span+%3Fx.%0D%0A++%7D%0D%0A++FILTER+gama%3AisEmpty%28%3Fx%29%0D%0A%7D%0D%0A">
	List of Persons without life_span
</a></li>

</ul>
<?php // ==================================================================== ?>
<?php include 'design/page-footer.php' ?>
<?php // ==================================================================== ?>
