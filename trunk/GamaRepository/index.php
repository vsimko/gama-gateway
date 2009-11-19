<?php

require_once 'config.php';

function query_sparql($querystr)
{
	try
	{
		if(Simple_Load_Engine::isValidSimpleLoadQueryString($querystr))
		{
			$engine = new Simple_Load_Engine;
			$engine->useQueryString($querystr);
		} else
		{
			$engine = new SPARQL_Engine;
			$engine->useSparql($querystr);
		}
		
		$engine->setResultHandler(new SPARQL_XML_Results_Renderer);
		
		ob_start();
		$engine->runQuery();
		//debug($querystr);
		return ob_get_clean();
	} catch(Exception $e)
	{
		ob_end_clean();
		throw new SoapFault("SPARQL",$e->getMessage());
	}
}

function cleanup_data()
{
	GAMA_Data_Loader::cleanupData();
}

function insert_data($rdfcontent)
{
	$fname = tempnam(GAMA_Utils::getSystemTempDir(), 'rdfcontent_');
	
	$parser = RDF_Parser_Factory::getParserByLocation($fname);
	dispatcher($parser)->attach( new GAMA_Data_Loader() );
	$parser->parse();
	
	unlink($fname);
}

$server = new SoapServer(null, array('uri' => "http://gama-gateway.eu/repository"));

$server->addFunction('query_sparql');
$server->addFunction('cleanup_data');
$server->addFunction('insert_data');

header("Content-Type: text/xml; charset=UTF-8");
$server->handle();
?>