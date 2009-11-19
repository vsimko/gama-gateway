<?php

/**
 * Please, connect to the appropriate RDF-parser using the dispatcher.
 */
class GAMA_Schema_Loader
{
	/**
	 * @category dispatcher
	 * @param string $s
	 * @param string $p
	 * @param string|RDFS_Literal $o
	 */
	public function onNewTriple($s, $p, $o)
	{
		$resmgr = Resource_Manager::singleton();
		
		switch($p)
		{
			case GAMA_Store::RDFS_SUBCLASSOF_URI:
			case GAMA_Store::OWL_VERSIONINFO_URI:
			case GAMA_Store::RDFS_COMMENT_URI:
			case GAMA_Store::RDFS_LABEL_URI:
				// ignored
				break;
				
			case GAMA_Store::RDF_TYPE_URI:
				
				if($o == GAMA_Store::OWL_TRANSITIVE_PROPERTY_URI)
				{
					$p = $resmgr->getPreparedResourceByUri($s, 'RDF_Property');
					
					if( $p->isPropType(GAMA_Store::TYPE_SYMMETRIC_PROPERTY) )
						$p->setPropertyType(GAMA_Store::TYPE_EQUIVALENCE_PROPERTY);
					elseif( $p->isPropType(GAMA_Store::TYPE_DATATYPE_PROPERTY) )
						throw new Exception('Usupported combination owl:DatatypeProperty and owl:TransitiveProperty');
					else
						$p->setPropertyType(GAMA_Store::TYPE_TRANSITIVE_PROPERTY);
				}
				
				elseif($o == GAMA_Store::OWL_SYMMETRIC_PROPERTY_URI)
				{
					$p = $resmgr->getPreparedResourceByUri($s, 'RDF_Property');
					
					if( $p->isPropType(GAMA_Store::TYPE_TRANSITIVE_PROPERTY) )
						$p->setPropertyType(GAMA_Store::TYPE_EQUIVALENCE_PROPERTY);
					elseif( $p->isPropType(GAMA_Store::TYPE_DATATYPE_PROPERTY) )
						throw new Exception('Usupported combination owl:DatatypeProperty and owl:SymmetricProperty');
					else
						$p->setPropertyType(GAMA_Store::TYPE_SYMMETRIC_PROPERTY);
				}
				
				elseif($o == GAMA_Store::OWL_DATATYPE_PROPERTY_URI)
				{
					$p = $resmgr->getPreparedResourceByUri($s, 'RDF_Property');
					$p->setPropertyType(GAMA_Store::TYPE_DATATYPE_PROPERTY, DT_xsd_string::getUri() );
				}
				
				elseif($o == GAMA_Store::OWL_OBJECT_PROPERTY_URI)
				{
					$p = $resmgr->getPreparedResourceByUri($s, 'RDF_Property');
					$p->setPropertyType(GAMA_Store::TYPE_OBJECT_PROPERTY);
				}
				
				elseif($o == GAMA_Store::OWL_CLASS_URI)
				{
					$resmgr->getPreparedResourceByUri($s, 'OWL_Class');
				}
				break;
			
			case GAMA_Store::RDFS_DOMAIN_URI:
				$c = $resmgr->getPreparedResourceByUri($o, 'OWL_Class');
				$p = $resmgr->getPreparedResourceByUri($s, 'RDF_Property');
				
				try
				{
					$p->setDomain($c);
				} catch (Exception $e)
				{
			 		dispatcher()->onErrorMessage($e->getMessage());
				}
				break;
				
			case GAMA_Store::RDFS_RANGE_URI:
				$p = $resmgr->getPreparedResourceByUri($s, 'RDF_Property');
				
				if(GAMA_Datatype::isSupportedDatatype($o))
				{
					$p->setPropertyType(GAMA_Store::TYPE_DATATYPE_PROPERTY, $o);
				} else
				{
					if($p->isPropType(GAMA_Store::TYPE_DATATYPE_PROPERTY))
					{
						$p->setPropertyType(GAMA_Store::TYPE_OBJECT_PROPERTY);
					}
					
					$c = $resmgr->getPreparedResourceByUri($o, 'OWL_Class');
					
					try
					{
						$p->setRange($c);
					} catch (Exception $e)
					{
				 		dispatcher()->onErrorMessage($e->getMessage());
					}
				}
				break;

			case GAMA_Store::OWL_INVERSEOF_URI:
				$p1 = $resmgr->getPreparedResourceByUri($s, 'RDF_Property');
				$p2 = $resmgr->getPreparedResourceByUri($o, 'RDF_Property');
				try
				{
					$p1->setInverseProperty($p2);
				} catch (Exception $e)
				{
			 		dispatcher()->onErrorMessage($e->getMessage());
				}
				break;
		}
	}
}
?>