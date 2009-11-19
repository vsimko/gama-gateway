<?php

/**
 * Please, connect to the appropriate RDF-parser using the dispatcher.
 */
class GAMA_Data_Loader
{
	public function onBaseUriFound($baseUri)
	{
		debug('BASE URI FOUND: '.$baseUri);
		GAMA_Store::singleton()->setGraph($baseUri);
	}
	
	/**
	 * @param string $s
	 * @param string $p
	 * @param string|RDFS_Literal $o
	 */
	public function onNewTriple($s, $p, $o)
	{
		if($p == GAMA_Store::RDF_TYPE_URI)
		{
			$rdfclass = Resource_Manager::singleton()->getResourceByUri($o);
			$rdfclass->onStateChanged('OWL_Class');
			
			if($rdfclass->isInStore())
			{
				try
				{
					$rdfclass->addIndividual($s, true);
				} catch(Exception $e)
				{
			 		dispatcher()->onErrorMessage($e->getMessage());
				}
			} else
			{
				dispatcher()->onUnknownClass($o);
			}
			return;
		} elseif($p == GAMA_Store::OWL_INVERSEOF_URI)
		{
			// also register the inverse property as ObjectProperty
			dispatcher()->onNewTriple($o, GAMA_Store::RDF_TYPE_URI, GAMA_Store::OWL_OBJECT_PROPERTY_URI);
		}
				
		$rdfproperty = Resource_Manager::singleton()->getResourceByUri($p);
		$rdfproperty->onStateChanged('RDF_Property');
	
		if($rdfproperty->isInStore())
		{
			try
			{
				$rdfproperty->addStatement($s, $o);
			} catch(Exception $e)
			{
		 		dispatcher()->onErrorMessage($e->getMessage());
			}
			
		} else
		{
			dispatcher()->onUnknownProperty($p);
		}
	}
	
	/**
	 * Deletes all statements.
	 * TODO: RDF_Property class should be responsible for this function
	 */
	static public function cleanupData()
	{
		$rows = GAMA_Store::singleton()
			-> sql('select uri from PROPERTY')
			-> fetchAll(PDO::FETCH_NUM);
		
		$resmgr = Resource_Manager::singleton();
		
		foreach($rows as $row)
		{
			$rdfproperty = $resmgr->getResourceByUri($row[0]);
			$rdfproperty->deletePropertyData();
		}
	}
}
?>