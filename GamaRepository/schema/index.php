<?php

require_once '../config.php';

// ======================================
// send some HTTP headers
// ======================================
header("Content-type: text/xml");


// ======================================
// references used below
// ======================================
$store = GAMA_Store::singleton();
$resourceManager = Resource_Manager::singleton();


// ======================================
// setup of the dispatcher
// ======================================
dispatcher()->attach(new Namespace_Manager);
dispatcher()->onNewNamespace('rdf', GAMA_Store::RDF_NAMESPACE_URI);
dispatcher()->onNewNamespace('rdfs', GAMA_Store::RDFS_NAMESPACE_URI);
dispatcher()->onNewNamespace('owl', GAMA_Store::OWL_NAMESPACE_URI);
dispatcher()->onNewNamespace('gama', 'http://gama-gateway.eu/schema/');

dispatcher()->attach(new RDF_XML_Writer);
dispatcher()->setOutputLocation('php://output');
dispatcher()->setBaseUri('http://gama-gateway.eu/schema/');

dispatcher()->onBeginWrite();


// ======================================
// export the ontology information
// ======================================
dispatcher()->onNewTriple('', 'rdf:type', 'owl:Ontology');
dispatcher()->onNewTriple('', 'owl:versionInfo', 'Ontology exported from the GAMA RDF repository on '.date(DATE_RFC2822));
dispatcher()->onFlushWriteBuffer();


// ======================================
// export the datatypes
// ======================================
$rdfsDatatype = $resourceManager->getResourceByUri(GAMA_Store::RDFS_DATATYPE_URI);

$results = $store->sql('select uri from RESOURCE where type=?', $rdfsDatatype->getID() )->fetchAll(PDO::FETCH_ASSOC);
foreach($results as $row)
{
	$datatypeUri = $row['uri'];
	dispatcher()->onNewTriple($datatypeUri, 'rdf:type', 'rdfs:Datatype');
}

// ======================================
// export classes
// ======================================
$rdfsSubClassOf = $resourceManager->getResourceByUri(GAMA_Store::RDFS_SUBCLASSOF_URI);
$gamaEntity = $resourceManager->getResourceByUri('http://gama-gateway.eu/schema/Entity');
$gamaEnumeration = $resourceManager->getResourceByUri('http://gama-gateway.eu/schema/Enumeration');

$results = $store->sql("
	select r.uri as gamaClassUri from
		RESOURCE r
		
		join {$rdfsSubClassOf->getStmtTab()} as p
		on r.id = p.subject
		
	where p.object in (?,?)
	", $gamaEntity->getID(), $gamaEnumeration->getID() )->fetchAll(PDO::FETCH_ASSOC);

foreach($results as $row)
{
	$gamaClassUri = $row['gamaClassUri'];
	dispatcher()->onNewTriple($gamaClassUri, 'rdf:type', 'owl:Class');
}

// ======================================
// export properties
// ======================================
$results = $store->sql('
	select
		p.uri as propertyUri,
		proptype as propertyType,
		dom.uri as domainUri,
		inv.uri as inversePropertyUri,
		IFNULL(rng.uri, p.datatype) as rangeUri

	from PROPERTY p
	left join RESOURCE dom on p.dom=dom.id
	left join RESOURCE rng on p.rng=rng.id
	left join RESOURCE inv on p.inverse=inv.id
	')->fetchAll(PDO::FETCH_ASSOC);

foreach($results as $row)
{
	$propertyUri = $row['propertyUri'];
	dispatcher()->onNewTriple($propertyUri, 'rdf:type', $row['propertyType']);
	
	if($row['domainUri'])
	{
		dispatcher()->onNewTriple($propertyUri, 'rdfs:domain', $row['domainUri']);
	}
	
	if($row['rangeUri'])
	{
		dispatcher()->onNewTriple($propertyUri, 'rdfs:range', $row['rangeUri']);
	}

	if($row['inversePropertyUri'])
	{
		dispatcher()->onNewTriple($propertyUri, 'owl:inverseOf', $row['inversePropertyUri']);
	}
}
dispatcher()->onEndWrite();
?>