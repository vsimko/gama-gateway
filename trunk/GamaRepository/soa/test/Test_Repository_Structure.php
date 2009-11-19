<?php	
/**
 * Provides more information about the consistency of data in the GAMA repository.
 */
class Test_Repository_Structure extends RPC_Service
{
	/**
	 * Textual description of tests performed.
	 */
	public function execute()
	{
		ob_implicit_flush(1);
		@header('Content-type: text/plain');
		
		$rpcclient = $this->getRpcClient();
		
		// =====================================================================
		echo "Number of works (total): ";
		$numTotalWorks = $rpcclient->{'query/Single_Value_From_Sparql'}('
			PREFIX gama: <http://gama-gateway.eu/schema/>
			SELECT count_distinct(?work) as ?num {
			  ?work a gama:Work.
			}
		');
		echo "$numTotalWorks\n";
		
		// =====================================================================
		echo "Is the gama:fulltext_works property ready: ";
		try
		{
			$numCachedWorks = $rpcclient->{'query/Single_Value_From_Sparql'}('
				PREFIX gama: <http://gama-gateway.eu/schema/>
				PREFIX cache: <http://gama-gateway.eu/cache/>
				SELECT count_distinct(?work) as ?num {
				  ?work cache:fulltext_works ?cache.
				}
			');
			
			echo $numCachedWorks >= $numTotalWorks ? "YES\n" : "NO\n";
		} catch(Exception $e)
		{
			echo "NO\n";
		}
		
		// =====================================================================
		echo "Number of works with missing description or with at least one empty description: ";
		echo $rpcclient->{'query/Single_Value_From_Sparql'}('
			PREFIX gama: <http://gama-gateway.eu/schema/>
			SELECT count_distinct(?work) as ?num {
			  ?work a gama:Work.
			  OPTIONAL {
			    ?work gama:work_description ?d.
			  }
			  FILTER gama:isEmpty(?d)
			}
		')."\n";
		
		// =====================================================================
		echo "Number of works with missing titles or with at least one empty title: ";
		echo $rpcclient->{'query/Single_Value_From_Sparql'}('
			PREFIX gama: <http://gama-gateway.eu/schema/>
			SELECT count_distinct(?work) as ?num {
			  ?work a gama:Work.
			  OPTIONAL {
			    ?work gama:work_title ?t.
			  }
			  FILTER gama:isEmpty(?t)
			}
		')."\n";
		
		// =====================================================================
		echo "Number of works with empty (but existing) titles: ";
		echo $rpcclient->{'query/Single_Value_From_Sparql'}('
			PREFIX gama: <http://gama-gateway.eu/schema/>
			SELECT count_distinct(?work) as ?num {
			  ?work a gama:Work.
			  ?work gama:work_title ?t.
			  FILTER (?t = "")
			}
		')."\n";
		
		// =====================================================================
		echo "Number of works without creation date: ";
		echo $rpcclient->{'query/Single_Value_From_Sparql'}('
			PREFIX gama: <http://gama-gateway.eu/schema/>
			SELECT count_distinct(?work) as ?num {
			  ?work a gama:Work.
			  OPTIONAL {
			    ?work gama:work_created ?c.
			  }
			  FILTER gama:isEmpty(?c)
			}
		')."\n";
		
		// =====================================================================
		echo "Number of works without creator: ";
		echo $rpcclient->{'query/Single_Value_From_Sparql'}('
			PREFIX gama: <http://gama-gateway.eu/schema/>
			SELECT count_distinct(?work) as ?num {
			  ?work a gama:Work.
			  OPTIONAL {
			    ?work gama:has_creator ?c.
			  }
			  FILTER gama:isEmpty(?c)
			}
		')."\n";
		
		// =====================================================================
		echo "Number of works with main manifestation: ";
		echo $rpcclient->{'query/Single_Value_From_Sparql'}('
			PREFIX gama: <http://gama-gateway.eu/schema/>
			SELECT count_distinct(?w) as ?num {
			  ?w a gama:Work.
			  ?w gama:has_manifestation ?m.
			  ?m gama:idx_main "1".
			}
		')."\n";
		
		// =====================================================================
		echo "Number of manifestations referenced from works: ";
		echo $rpcclient->{'query/Single_Value_From_Sparql'}('
			PREFIX gama: <http://gama-gateway.eu/schema/>
			SELECT count_distinct(?manif) as ?num {
			  _:w gama:has_manifestation ?manif
			}
		')." (should be equal to total manifestations)\n";
				
		// =====================================================================
		echo "Number of manifestations (total): ";
		echo $rpcclient->{'query/Single_Value_From_Sparql'}('
			PREFIX gama: <http://gama-gateway.eu/schema/>
			SELECT count_distinct(?manif) as ?num {
			  ?manif a gama:Manifestation.
			}
		')."\n";
		
		// =====================================================================
		echo "Number of manifestations without titles or with at least one empty title: ";
		echo $rpcclient->{'query/Single_Value_From_Sparql'}('
			PREFIX gama: <http://gama-gateway.eu/schema/>
			SELECT count_distinct(?manif) as ?num {
			  ?manif a gama:Manifestation.
			  OPTIONAL {
			    ?manif gama:manif_title ?x.
			  }
			  FILTER gama:isEmpty(?x)
			}
		')."\n";
		
		// =====================================================================
		echo "Number of manifestations without manif_url or at least one empty url: ";
		echo $rpcclient->{'query/Single_Value_From_Sparql'}('
			PREFIX gama: <http://gama-gateway.eu/schema/>
			SELECT count_distinct(?manif) as ?num {
			  ?manif a gama:Manifestation.
			  OPTIONAL {
			    ?manif gama:manif_url ?x.
			  }
			  FILTER gama:isEmpty(?x)
			}
		')."\n";
		
		// =====================================================================
		echo "Main manifestations without idx_stream_avail 1 or 2: ";
		echo $rpcclient->{'query/Single_Value_From_Sparql'}('
			PREFIX gama: <http://gama-gateway.eu/schema/>
			SELECT count_distinct(?m) as ?num {
			  ?m a gama:Manifestation.
			  ?m gama:idx_main "1".
			  OPTIONAL {
			    ?m gama:idx_stream_avail ?s.
			    FILTER (gama:isEmpty(?s) || (?s != "1" && ?s !="2"))
			  }
			}
		')." - TODO\n";
		
		// =====================================================================
		echo "Main manifestations without WorkType: ";
		echo $rpcclient->{'query/Single_Value_From_Sparql'}('
			PREFIX gama: <http://gama-gateway.eu/schema/>
			SELECT count_distinct(?m) as ?num
			{
			 ?m gama:idx_main "1".
			 ?m gama:manifestation_of ?w.
			 OPTIONAL {
			  ?w gama:work_type ?t.
			 }
			 FILTER gama:isEmpty(?t).
			}
		')."\n";
			
		// =====================================================================
		echo "Main manifestations without similarity match: TODO\n";
		
		// =====================================================================
		echo "Number of persons (total): ";
		echo $rpcclient->{'query/Single_Value_From_Sparql'}('
			PREFIX gama: <http://gama-gateway.eu/schema/>
			SELECT count_distinct(?p) as ?num {
			  ?p a gama:Person.
			}
		')."\n";
		
		// =====================================================================
		echo "Number of persons without names or with at least one empty name: ";
		echo $rpcclient->{'query/Single_Value_From_Sparql'}('
			PREFIX gama: <http://gama-gateway.eu/schema/>
			SELECT count_distinct(?p) as ?num {
			  ?p a gama:Person.
			  OPTIONAL {
			    ?p gama:person_name ?n.
			  }
			  FILTER gama:isEmpty(?n)
			}
		')."\n";

		// =====================================================================
		echo "Number of persons without life_span: ";
		echo $rpcclient->{'query/Single_Value_From_Sparql'}('
			PREFIX gama: <http://gama-gateway.eu/schema/>
			SELECT count_distinct(?p) as ?num {
			  ?p a gama:Person.
			  OPTIONAL {
			    ?p gama:life_span ?x.
			  }
			  FILTER gama:isEmpty(?x)
			}
		')."\n";
		
		// =====================================================================
		echo "Number of artists without works (without is_creator, is_producer, is_contributor TODO more): ";
		echo $rpcclient->{'query/Single_Value_From_Sparql'}('
			PREFIX gama: <http://gama-gateway.eu/schema/>
			SELECT count_distinct(?p) as ?num {
			  ?p a gama:Person.
			  OPTIONAL {
			    ?p gama:is_creator ?w1.
			    ?p gama:is_producer ?w2.
			    ?p gama:is_contributor ?w3.
			  }
			  FILTER gama:isEmpty(?w1)
			  FILTER gama:isEmpty(?w2)
			  FILTER gama:isEmpty(?w3)
			}
		')."\n";
		
		// =====================================================================
		echo "Number of manifestations with gama:manif_drmid: ";
		echo $rpcclient->{'query/Single_Value_From_Sparql'}('
			PREFIX gama: <http://gama-gateway.eu/schema/>
			SELECT count_distinct(?m) as ?num {
			  ?m a gama:Manifestation.
			  ?m gama:manif_drmid ?drmid.
			}
		')."\n";
		
		// =====================================================================
		echo "Number of statements in the graph http://gama-gateway.eu/harmonisation/ : ";
		echo $this->countStatementsFromGraph('http://gama-gateway.eu/harmonisation/');
		echo "\n";
		
		// =====================================================================
		echo "all reports done.\n";
	}
	
	/**
	 * @param $graphName
	 * @return integer
	 */
	private function countStatementsFromGraph($graphName)
	{
		$store = GAMA_Store::singleton();

		// this will also create the empty graph if it didn't exist before 
		$store->setGraph($graphName);
		
		// we need the list of all properties 
		$listProperties = $store->sql('
			select
				propid as propertyId,
				uri as propertyUri
			from PROPERTY
			')->fetchAll(PDO::FETCH_ASSOC);
		
		// we count statements for every property in the repository that belong to our graph
		$stmtCount = 0;
		foreach($listProperties as $property)
		{
			$propertyId = $property['propertyId'];
			$propertyUri = $property['propertyUri'];
			
			$stmt = $store->sql("
				SELECT count(*) as count
				FROM  S_$propertyId
				where g=?
				", $store->getGraphID() );
			
			while( $row = $stmt->fetch(PDO::FETCH_ASSOC) )
			{
				$stmtCount += $row['count'];
			}
		}
		
		return $stmtCount;
	}
}
?>