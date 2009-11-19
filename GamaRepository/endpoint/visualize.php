<?php
require_once 'config-endpoint.php';

$GRAPH_FNAME = '/tmp/gama.graph';
$PNG_FNAME = "$GRAPH_FNAME.png";

$fh = fopen($GRAPH_FNAME, 'w');
fwrite($fh, 'digraph G {
	nodesep=.2; rankdir=LR;
	edge [
		fontsize=9
		fontname=Sans
	];
	
	node [
		fillcolor=black
		fontcolor=white
		fontname=Sans
		shape=hexagon orientation=90
		fontsize=16
		style=filled
	];
');

$namespaceManager = new Namespace_Manager;
$namespaceManager->onNewNamespace('rdf', GAMA_Store::RDF_NAMESPACE_URI);
$namespaceManager->onNewNamespace('rdfs', GAMA_Store::RDFS_NAMESPACE_URI);
$namespaceManager->onNewNamespace('owl', GAMA_Store::OWL_NAMESPACE_URI);
$namespaceManager->onNewNamespace('xsd', GAMA_Store::XSD_NAMESPACE_URI);
$namespaceManager->onNewNamespace('gama', 'http://gama-gateway.eu/schema/');

$results = GAMA_Store::singleton()->sql('

	select
		p.propid	as propertyId,
		p.inverse	as inversePropertyId,
		d.uri		as domainUri,
		r.uri		as rangeUri,
		p.uri		as propertyUri,
		p.datatype	as datatypeUri
		
	from PROPERTY p
		left join RESOURCE d on p.dom = d.id
		left join RESOURCE r on p.rng = r.id
		
')->fetchAll(PDO::FETCH_ASSOC);

$props = array();
foreach($results as $row)
{
	$props[$row['propertyId']] = $row;
}

foreach($props as $id => $p)
{
	$propertyUri = $p['propertyUri'];
	$inversePropertyId = $p['inversePropertyId'];
	$domainUri = $p['domainUri'];
	$rangeUri = $p['rangeUri'];
	$datatypeUri = $p['datatypeUri'];
	
	if($inversePropertyId)
	{
		$domainUri = $props[$inversePropertyId]['rangeUri'];
		$rangeUri = $props[$inversePropertyId]['domainUri'];
		$color = 'color=red fontcolor=red';
	} else
	{
		$color = 'color=blue fontcolor=blue';
	}
	
	if( !empty($domainUri) )
	{
		$srcnode = addslashes( $namespaceManager->resolvePrefix($domainUri) );
		
		if(empty($rangeUri))
		{
			$dstnode = addslashes( $namespaceManager->resolvePrefix($datatypeUri) );
			$color = 'color="#00dd00" fontcolor="#00dd00"';
			
			if(!isset($datatypeAlreadyDefined[$datatypeUri]))
			{
				$datatypeAlreadyDefined[$datatypeUri] = true;
				fwrite($fh, "\"$dstnode\" [fillcolor=\"#00aa00\" shape=box fontsize=14];\n");
			}
		} else
		{
			$dstnode = addslashes( $namespaceManager->resolvePrefix($rangeUri) );
		}
		$label = 'label="'.addslashes($namespaceManager->resolvePrefix($propertyUri) ).'"';
		
		fwrite($fh, "\"$srcnode\" -> \"$dstnode\" [$color $label];\n");
	}
}

fwrite($fh, "}\n");
fclose($fh);

exec('dot -Tpng -o '.escapeshellarg($PNG_FNAME).' '.escapeshellarg($GRAPH_FNAME));

header("Cache-Control: cache, must-revalidate");
header("Content-type: image/png");

$fh = fopen($PNG_FNAME, 'r');
while(!feof($fh))
{
	echo fread($fh, 65536);
}
fclose($fh);
exit;
?>