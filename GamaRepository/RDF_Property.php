<?php

// following definiton is required because the GAMA_Datatype knows how to
// setup the autoloading of datatype classes.
require_once 'GAMA_Datatype.php';

class RDF_Property extends Base_State
{
	/**
	 * @return boolean
	 */
	public function isPropType($pt)
	{
		return $this->proptype == $pt;
	}

	/**
	 * @return RDF_Property
	 */
	public function getInverseMaster()
	{
		return $this->inverseMaster;
	}

	/**
	 * @return RDF_Property
	 */
	public function getInverseSlave()
	{
		return $this->inverseSlave;
	}

	/**
	 * Whether there is an inverse property defined.
	 * @return boolean
	 */
	public function isHavingInverse()
	{
		return ! empty($this->inverseProperty);
	}
	
	/**
	 * Whether the property is an inverse-master.
	 * There must be an inverse property defined prior to calling this function.
	 * @return boolean
	 */
	public function isThisInverseMaster()
	{
		assert($this->isHavingInverse());
		return $this->handler === $this->inverseMaster;
	}

	/**
	 * Create an SQL variable pointing to the object column
	 * inside the given statemenet-table.
	 * @param string $tabalias
	 * @return string
	 */
	public function stmtObject($tabalias)
	{
		return "$tabalias.object";
	}

	/**
	 * Create an SQL variable pointing to the subject column
	 * inside the given statemenet-table.
	 * @param string $tabalias
	 * @return string
	 */
	public function stmtSubject($tabalias)
	{
		return "$tabalias.subject";
	}

	/**
	 * Create an SQL variable pointing to the language column
	 * inside the given statemenet-table.
	 * @param string $tabalias
	 * @return string
	 */
	public function stmtLang($tabalias)
	{
		if($this->isPropType(GAMA_Store::TYPE_DATATYPE_PROPERTY))
		{
			$datatypeInstance = GAMA_Datatype::getDatatypeByUri($this->datatype);
			return $datatypeInstance->getLangJoinDefinition($tabalias);
		} else
		{
			throw new Exception('Only allowed in DatatypeProperty');
		}
	}

	/**
	 * @see GAMA_Datatype::getValueDefinition
	 * @param string $tabalias
	 * @return string
	 */
	public function stmtValue($tabalias)
	{
		if($this->isPropType(GAMA_Store::TYPE_DATATYPE_PROPERTY))
		{
			$datatypeInstance = GAMA_Datatype::getDatatypeByUri($this->datatype);
			return $datatypeInstance->getValueDefinition($tabalias);
		} else
		{
			throw new Exception('Only allowed in DatatypeProperty');
		}
	}
	
	/**
	 * @see GAMA_Datatype::getSortingValueDefinition
	 * @param string $tabalias
	 * @return string
	 */
	public function stmtOrderByValue($tabalias)
	{
		if($this->isPropType(GAMA_Store::TYPE_DATATYPE_PROPERTY))
		{
			$datatypeInstance = GAMA_Datatype::getDatatypeByUri($this->datatype);
			return $datatypeInstance->getSortingValueDefinition($tabalias);
		} else
		{
			throw new Exception('Only allowed in DatatypeProperty');
		}
	}

	/**
	 * Textual representation for debugging.
	 * @return string
	 */
	public function __toString()
	{
		static $alreadyWriting = 0;
		assert('!$alreadyWriting++');

		$ptmap = array(
		GAMA_Store::TYPE_DATATYPE_PROPERTY		=> 'Datatype Property',
		GAMA_Store::TYPE_EQUIVALENCE_PROPERTY	=> 'Equivalence (Symmetric + Transitive) Property',
		GAMA_Store::TYPE_OBJECT_PROPERTY		=> 'Object Property',
		GAMA_Store::TYPE_SYMMETRIC_PROPERTY		=> 'Symmetric Property',
		GAMA_Store::TYPE_TRANSITIVE_PROPERTY	=> 'Transitive Property',
		);

		$out = isset($ptmap[$this->proptype])
		? $ptmap[$this->proptype]
		: 'Property';

		$out .= " '{$this->getUri()}'";

		if($this->isHavingInverse())
		{
			assert('!empty($this->proptype) && $this->isSupportedInverseProperty()');
			$out .=	($this->isThisInverseMaster() ? ' (MASTER)' : ' (SLAVE)').
					" is inverse of '".$this->inverseProperty->getUri()."'".
			($this->isThisInverseMaster() ? ' (SLAVE)' : ' (MASTER)');
		}

		if(! $this->isInStore())
		$out .= ' does not exist';

		$alreadyWriting--;
		return $out;
	}

	/**
	 * @see Base_State::reloadFromStore()
	 */
	public function reloadFromStore()
	{
		// ===============
		app_lock(LOCK_SH);
		// ===============
		try
		{
			$row0 = GAMA_Store::singleton()->sql('
					select p.*,
						ip.uri as inverse_uri,
						dc.uri as domain_uri,
						rc.uri as range_uri
					from PROPERTY p
					left join PROPERTY ip	on ip.inverse = p.propid or p.inverse = ip.propid
					left join RESOURCE dc	on dc.id = p.dom
					left join RESOURCE rc	on rc.id = p.rng
					where p.uri=?', $this->getUri() )->fetch(PDO::FETCH_ASSOC);
		} catch(Exception $e)
		{
			app_unlock();
			throw $e;
		}
		// ==========
		app_unlock();
		// ==========

		// if property not found in the store we have to reset internal structures
		if(empty($row0))
		{
			// Could not unset() because it would remove the variable completely
			// and the __get() function would complain about missing variable
			$this->id = null;
			$this->domainClass = null;
			$this->rangeClass = null;
			$this->proptype = null;
			$this->datatype = null;
			$this->inverseProperty = null;
			$this->inverseMaster = null;
			$this->inverseSlave = null;
		} else
		{
			$this->id = $row0['propid'];
			$this->proptype = $row0['proptype'];
			$this->datatype = $row0['datatype'];
				
			$resmgr = Resource_Manager::singleton();
				
			if($row0['domain_uri'])
			{
				$this->domainClass = $resmgr->getResourceByUri($row0['domain_uri']);
			}

			if($row0['range_uri'])
			{
				$this->rangeClass = $resmgr->getResourceByUri($row0['range_uri']);
			}
				
			// also load inverse property if applicable
			if($row0['inverse_uri'])
			{
				$this->inverseProperty = $resmgr->getPreparedResourceByUri($row0['inverse_uri'], 'RDF_Property');

				// TODO: somehow handle this error inside the store
				assert('$this->isSupportedInverseProperty()');
				assert('$this->inverseProperty->isSupportedInverseProperty()');

				// who is the master and who is the slave
				if($row0['inverse'] == 0)
				{
					// in this case the current property is the master
					$this->inverseMaster = $this->handler;
					$this->inverseSlave = $this->inverseProperty;
				} else
				{
					// in this case the current property is the slave
					$this->inverseMaster = $this->inverseProperty;
					$this->inverseSlave = $this->handler;
					
					// the domain class will be  the master's range class
					if($this->inverseMaster->isSetContextItem('rangeClass'))
					{
						$this->domainClass = $this->inverseMaster->getContextItem('rangeClass');
					}
					
					// the range class will be  the master's domain class
					if($this->inverseMaster->isSetContextItem('domainClass'))
					{
						$this->rangeClass =  $this->inverseMaster->getContextItem('domainClass');
					}
				}

			} else
			$this->inverseProperty = null;
		}
	}

	public function createInStore()
	{
		if($this->isInStore())
		{
			return;
		}

		$store = GAMA_Store::singleton();

		// ===============
		app_lock(LOCK_EX);
		// ===============
		try
		{
			$this->id = $store->sqlFetchValue(
				'select id from RESOURCE where uri=?', $this->getUri());
			
			if(empty($this->id))
			{
				$this->rdftype = GAMA_Store::RDF_PROPERTY_URI;

				// get ID of the rdf:Property
				$typeId = Resource_Manager::singleton()
					-> getResourceByUri(GAMA_Store::RDF_PROPERTY_URI)
					-> getID();
					
				$this->id = $store->getNewResourceID();
					
				$store->sql('insert into RESOURCE (id, type, uri) values (?,?,?)',
					$this->id, $typeId, $this->uri);
					
			}
			
			$found = $store->sqlFetchValue(
				'select propid from PROPERTY where uri=?', $this->getUri());
				
			if(empty($found))
			{
				// the property does not exist so we create it
				$store->sql('insert into PROPERTY (propid, uri) values (?, ?)',
					$this->getID(), $this->getUri() );
	
				$store->sql("
				drop table if exists {$this->getStmtTab()};
				create table {$this->getStmtTab()}
				(
					-- foreign key to named graphs
					g smallint unsigned not null default ".GAMA_Store::DEFAULT_GRAPH_ID.",
					index(g),
					
					-- subject
					subject integer unsigned not null,
		
					-- object depends on property type
					object integer unsigned not null,
					
					PRIMARY KEY (subject, object, g)
					
				) engine=MyISAM comment='{$this->getUri()}' default charset binary;
				");
				debug("Property added '{$this->getUri()}'");
			}
		} catch(Exception $e)
		{
			app_unlock();
			throw $e;
		}
		// ==========
		app_unlock();
		// ==========
	}

//TODO: still not finished
//	abstract public function isSupportedInverseProperty();
	/**
	 * Tests whether this property can be defined as inverse.
	 * @return boolean TRUE, if can be defined as inverse property.
	 */
	public function isSupportedInverseProperty()
	{
		return empty($this->proptype) // TODO: why ????????????????????????????
		|| $this->proptype == GAMA_Store::TYPE_OBJECT_PROPERTY
		|| $this->proptype == GAMA_Store::TYPE_TRANSITIVE_PROPERTY;
	}

	/**
	 * Removes the inverse from the property.
	 */
	public function unsetInverseProperty()
	{
		assert('$this->inverseProperty');
		assert('$this->inverseMaster');
		assert('$this->inverseSlave');

		// ===============
		app_lock(LOCK_EX);
		// ===============
		try
		{
			$store = GAMA_Store::singleton();
			
			$store->sql('update PROPERTY set inverse=0 where propid=?',
				$this->inverseSlave->getID() );
	
			$store->sql("
				insert into {$this->inverseSlave->getStmtTab()}
				(g, subject, object)
				select g, object, subject
				from {$this->inverseMaster->getStmtTab()}");
				
			$this->inverseProperty->reloadFromStore();
			$this->reloadFromStore();
		} catch(Exception $e)
		{
			app_unlock();
			throw $e;
		}
		// ==========
		app_unlock();
		// ==========
	}

	/**
	 * Which property should be inverse of this property.
	 *
	 * @param Resource $p
	 */
	public function setInverseProperty(Resource $otherProperty)
	{
		assert('!empty($otherProperty)');

		$store = GAMA_Store::singleton();

		// ===============
		app_lock(LOCK_EX);
		// ===============
		try
		{
			// make sure we have latest data from store
			$this->reloadFromStore();
			$otherProperty->reloadFromStore();
				
			// both properties must be in the repository
			$this->isInStore(true);
			$otherProperty->isInStore(true);
	
			// both properties should be ready to become inversion
			assert('$this->isSupportedInverseProperty()');
			assert('$otherProperty->isSupportedInverseProperty()');
	
			// done, if this inversion already exists
			if($this->inverseProperty === $otherProperty)
			{
				throw new Exception("This inversion has already been defined.");
			}
			
			// there must not be another inverse property defined
			// TODO: rewrite using exceptions
			assert('!$this->isHavingInverse()');
			assert('!$otherProperty->isHavingInverse()');
	
			// both properties are ObjectProperties from now
			$this->setPropertyType(GAMA_Store::TYPE_OBJECT_PROPERTY);
			$otherProperty->setPropertyType(GAMA_Store::TYPE_OBJECT_PROPERTY);
				
			// which property has got a real domain
			if($this->domainClass && $this->domainClass->isInStore())
			{
				$master = $this->handler;
				$slave = $otherProperty;
			} else
			{
				$master = $otherProperty;
				$slave = $this->handler;
			}
				
			if( !$master->isSetContextItem('domainClass') &&
				$slave->isSetContextItem('rangeClass') )
			{
				$master->setDomain( $slave->getContextItem('rangeClass') );
			}

			$mdom = $master->isSetContextItem('domainClass')
				? $master->getContextItem('domainClass')->getID() : 0;

			$mrng = $master->isSetContextItem('rangeClass')
				? $master->getContextItem('rangeClass')->getID() : 0;
	
			// update slave
			$store->sql('
				update PROPERTY set
					inverse = :inverse,
					dom = :dom,
					rng = :rng
				where propid = :propid
				', array(	'inverse'	=> $master->getID(),
							'dom'		=> $mrng,
							'rng'		=> $mdom,
							'propid'	=> $slave->getID() ));
				
			// move data from slave to master
			$store->sql("
				insert ignore into {$master->getStmtTab()} (g, subject, object)
				select g, object, subject from {$slave->getStmtTab()}");
	
			// delete data from slave because they will be handled by master
			$store->sql("delete from {$slave->getStmtTab()}");
	
			$this->reloadFromStore();
			$otherProperty->reloadFromStore();
		} catch(Exception $e)
		{
			app_unlock();
			throw $e;
		}
		// ==========
		app_unlock();
		// ==========

		debug("Defined inverse property {$otherProperty->getUri()} as inverse of {$this->getUri()}");
	}

	/**
	 * Changes property domain.
	 * @param Resource $c
	 */
	public function setDomain(Resource $domainResource)
	{
		$store = GAMA_Store::singleton();

		// ===============
		app_lock(LOCK_EX);
		// ===============
		try
		{
			$this->reloadFromStore();
	
			$this->isInStore(true);
			$domainResource->isInStore(true);
				
			if(isset($this->domainClass) && $this->domainClass === $domainResource)
			{
				throw new Exception("Same domain -> no change needed");
			}
			
			$this->deletePropertyData();
				
			$store->sql(
				'update PROPERTY set dom=? where propid=?',
				$domainResource->getID(), $this->getID() );
				
			if($this->isHavingInverse())
			{
				$store->sql(
					'update PROPERTY set rng=? where propid=?',
					$domainResource->getID(), $this->inverseProperty->getID() );
					
				$this->inverseProperty->reloadFromStore();
			}
	
			$this->reloadFromStore();
				
			// symmetric and equivalence has same domain and range
			if(	$this->isPropType(GAMA_Store::TYPE_SYMMETRIC_PROPERTY) ||
			    $this->isPropType(GAMA_Store::TYPE_EQUIVALENCE_PROPERTY))
			{
				debug("Should also change range - SYMMETRIC or EQUIVALENCE");
				if(isset($this->domainClass))
				{
					$this->setRange($this->domainClass);
				}
			}
		} catch(Exception $e)
		{
			app_unlock();
			throw $e;
		}
		// ===========
		app_unlock();
		// ===========

		debug("Domain changed");
		return true;
	}

	/**
	 * @param Resource $rangeResource
	 */
	public function setRange(Resource $rangeResource)
	{
		$store = GAMA_Store::singleton();

		// stop recursion: setRange..setDomain..setRange..STOP
		if(isset($this->rangeClass) && $this->rangeClass === $rangeResource)
		{
			debug('Same range - no change needed');
			return;
		}

		switch($this->proptype)
		{
			case GAMA_Store::TYPE_DATATYPE_PROPERTY:
				$datatypeUri = $rangeResource->getUri();
				if(! GAMA_Datatype::isSupportedDatatype($datatypeUri) )
				{
					throw new Exception('Datatype not supported');
				}
				
				assert('empty($this->inverseProperty)');
				assert('empty($this->rangeClass)');
				
				$this->setPropertyType(GAMA_Store::TYPE_DATATYPE_PROPERTY, $datatypeUri );
				break;
				
			case null:
			case GAMA_Store::TYPE_OBJECT_PROPERTY:
			case GAMA_Store::TYPE_TRANSITIVE_PROPERTY:
				assert('!empty($rangeResource)');
				assert('empty($this->datatype)');
				$rangeResource->isInStore(true);
				
				if($this->isHavingInverse())
				{
					$this->inverseProperty->setDomain($rangeResource);
				}
				else
				{
					$store->sql(
						'update PROPERTY set rng=? where propid=?',
						$rangeResource->getID(), $this->getID() );

					$this->deletePropertyData();
					$this->reloadFromStore();
				}

				break;
					
			case GAMA_Store::TYPE_EQUIVALENCE_PROPERTY:
			case GAMA_Store::TYPE_SYMMETRIC_PROPERTY:
				$rangeResource->isInStore(true);
				assert('/* Resource required */ $rangeResource instanceof Resource');
				assert('empty($this->datatype)');
				assert('empty($this->inverseProperty)');
				assert('empty($this->rangeClass)');

				$store->sql(	'update PROPERTY set rng=? where propid=?',
				$rangeResource->getID(), $this->getID() );

				$this->deletePropertyData();
				$this->reloadFromStore();

				$this->setDomain($rangeResource);
				break;

			default:
				assert('/* this property type is not supported */');
		}
	}

	/**
	 * Create the name of database table containing statemenets of this property. 
	 * @return string
	 */
	function getStmtTab()
	{
		return 'S_'.$this->getID();
	}

	/**
	 * Rebuilds columns and indexes according to the property type.
	 */
	public function updatePropertyStorageModel()
	{
		if(empty($this->datatype))
		{
			// this is a property without a datatype such as owl:ObjectProperty 
			$columnDef = array( 'object' => 'integer unsigned not null' );
			$indexDef = array( 'index (object)' );
		} else
		{
			// this is the owl:DatatypeProperty with some datatype
			$datatypeInstance = GAMA_Datatype::getDatatypeByUri($this->datatype);
			
			$columnDef = $datatypeInstance->getColumnDefinition();
			$indexDef =  $datatypeInstance->getIndexDefinition();
			
			// implicit definitions
			//========================
			// language column for all datatypes
			
			$columnDef['lang'] = 'char(2) charset ascii not null default ""';
			$indexDef[] = 'index (lang)';
			
			// sorting index for all datatypes
			// NOTE: can be disabled in the datatype if set null
			$sortIdxDef = $datatypeInstance->getSortingIndexDefinition();
			if(!empty($sortIdxDef))
			{
				$indexDef[] = 'index sortidx('.$sortIdxDef.')';
			}
		}
		
		assert(is_array($columnDef));
		assert(is_array($indexDef));

		$store = GAMA_Store::singleton();
		
		// ===============
		app_lock(LOCK_EX);
		// ===============
		try
		{
			// get all property-specific indexes
			$propertySpecificIndexes = $store->sqlFetchColumn( 'index_name', '
				SELECT DISTINCT index_name
				FROM information_schema.STATISTICS
				WHERE	table_schema = ?
						and table_name = ?
						and not Column_name in ("g", "subject")
						and index_name <> "PRIMARY"
			', GAMA_Store::singleton()->getDatabaseName(), $this->getStmtTab() );
			
			// drop all property-specific indexes
			foreach($propertySpecificIndexes as $indexName)
			{
				debug('Dropping property-specific index: '.$indexName);
				$store->sql("alter table {$this->getStmtTab()} drop index $indexName");
			}
	
			// get all property-specific columns
			$oldColNames = $store->sqlFetchColumn('column_name', '
					SELECT column_name
					FROM INFORMATION_SCHEMA.COLUMNS
					WHERE table_schema = ?
						and table_name = ?
						and not column_name in ("g", "subject")
			', GAMA_Store::singleton()->getDatabaseName(), $this->getStmtTab() );
			
			debug("Found old property-specific columns:");
			debug($oldColNames);
			
			$newColNames = array_keys($columnDef);
			
			// drop columns not used in the new property-type
			$columnsToDrop = array_diff($oldColNames, $newColNames);			
			foreach($columnsToDrop  as $columnName)
			{
				debug('Dropping redundant column: '.$columnName);
				$store->sql("alter table {$this->getStmtTab()} drop column $columnName");
			}
			
			// update the storage type of property-specific columns
			foreach($columnDef as $columnName => $sqlSnippet)
			{
				// old columns will be modified, new columns will be added
				$operator = in_array($columnName, $oldColNames) ? 'modify' : 'add';
				
				// construct the SQL defining the new column
				$store->sql("alter table {$this->getStmtTab()} $operator $columnName $sqlSnippet");
			}
			
			// define new indexes because we dropped all previous ones
			foreach($indexDef as $sqlSnippet)
			{
				$store->sql("alter table {$this->getStmtTab()} add $sqlSnippet");
			}
			
		} catch(Exception $e)
		{
			app_unlock();
			throw $e;
		}
		// ==========
		app_unlock();
		// ==========
	}
	
	/**
	 * @param string $proptype
	 * @param string $datatype supported XSD datatypes
	 * @return boolean TRUE, if type has changed
	 */
	public function setPropertyType($proptype, $datatype = null)
	{
		assert('/* unsupported property type */ in_array($proptype, array(
			GAMA_Store::TYPE_DATATYPE_PROPERTY,
			GAMA_Store::TYPE_EQUIVALENCE_PROPERTY,
			GAMA_Store::TYPE_OBJECT_PROPERTY,
			GAMA_Store::TYPE_TRANSITIVE_PROPERTY,
			GAMA_Store::TYPE_SYMMETRIC_PROPERTY,
			))');

		if($proptype == $this->proptype && $datatype == $this->datatype)
		{
			return false; // same type
		}

		// ===============
		app_lock(LOCK_EX);
		// ===============
		try
		{
			$store = GAMA_Store::singleton();
			
			$store->sql('
				update PROPERTY set proptype = :ptype, datatype = :dtype
				where propid = :propid',
				array(	'ptype'		=> $proptype,
						'dtype'		=> $datatype,
						'propid'	=> $this->getID() ));
	
			$this->proptype = $proptype;
			$this->datatype = $datatype;
				
			switch($proptype)
			{
				// --------------------------------
				case GAMA_Store::TYPE_EQUIVALENCE_PROPERTY:
				case GAMA_Store::TYPE_SYMMETRIC_PROPERTY:
	
					if(empty($this->domainClass) && $this->rangeClass)
					{
						$this->setDomain($this->rangeClass);
					} elseif(empty($this->rangeClass) && $this->domainClass)
					{
						$this->setRange($this->domainClass);
					}
	
					assert('$this->domainClass === $this->rangeClass');
					// continues with transitive and object property
				case GAMA_Store::TYPE_TRANSITIVE_PROPERTY:
				case GAMA_Store::TYPE_OBJECT_PROPERTY:
					assert('/* datatype not allowed for ObjectProperty */ empty($datatype)');
					$this->updatePropertyStorageModel();
					break;
	
				// --------------------------------
				case GAMA_Store::TYPE_DATATYPE_PROPERTY:
					assert('/* remove the inverse property first */ empty($this->inverseProperty)');
					assert('/* must specify the datatype */ !empty($datatype)');
					
					if(! GAMA_Datatype::isSupportedDatatype($datatype))
					{
						throw new Exception('This datatype is not supported');
					}
					
					$this->updatePropertyStorageModel();
					break;
	
				// --------------------------------
				default:
					assert('/* this property type is not supported */');
			}
		} catch(Exception $e)
		{
			app_unlock();
			throw $e;
		}
		// ==========
		app_unlock();
		// ==========
	}

	/**
	 * @return boolean
	 */
	public function deleteFromStore()
	{
		$this->isInStore(true);

		//==============
		app_lock();
		//==============
		try
		{
			$store = GAMA_Store::singleton();
			
			if($this->isHavingInverse() && $this->isThisInverseMaster())
			{
				$store->sql("drop table {$this->inverseSlave->getStmtTab()}");
				$store->sql('delete from PROPERTY where propid = ?', $this->inverseSlave->getID());
	
				//rename columns object->subject and subject->object
				$store->sql("alter table {$this->getStmtTab()} change subject object int unsigned not null, change object subject int unsigned not null");
	
				$store->sql('
						update PROPERTY set uri=:uri, dom=:dom, rng=:rng 
						where propid=:propid',
				array(	'uri'	=> $this->inverseSlave->getUri(),
								'dom'	=> empty($this->rangeClass) ? 0 : $this->domainClass->getID(),
								'rng'	=> empty($this->rangeClass) ? 0 : $this->rangeClass->getID(),
								'propid'=> $this->getID() ));
	
				$this->inverseSlave->reloadFromStore();
			} else
			{
				$store->sql("drop table {$this->getStmtTab()}");
				$store->sql('delete from PROPERTY where propid=?', $this->getID());
	
				if($this->isHavingInverse())
				{
					Resource_Manager::singleton()->removeResourceFromCache( $this->inverseMaster->getUri() );
					$this->inverseMaster->reloadFromStore();
				}
			}
				
			$this->reloadFromStore();
			
		} catch(Exception $e)
		{
			app_unlock();
			throw $e;
		}
		//===============
		app_unlock();
		//===============
		debug("Property deleted: {$this->getUri()}");
	}

	/**
	 * @param string $subjectUri
	 * @param string|RDFS_Literal $objectUriOrLiteral
	 */
	public function addStatement($subjectUri, $objectUriOrLiteral)
	{
		$this->domainClass->addIndividual($subjectUri);

		// ================
		app_lock(LOCK_EX);
		// ================
		try
		{
			if( $this->isPropType(GAMA_Store::TYPE_DATATYPE_PROPERTY) )
			{
				$this->addStmtDatatypeProperty($subjectUri, $objectUriOrLiteral);
			} elseif( $this->isPropType(GAMA_Store::TYPE_OBJECT_PROPERTY) ||
			          $this->isPropType(GAMA_Store::TYPE_TRANSITIVE_PROPERTY) )
			{
				$this->addStmtObjectProperty($subjectUri, $objectUriOrLiteral);
			} elseif( $this->isPropType(GAMA_Store::TYPE_SYMMETRIC_PROPERTY) )
			{
				$this->addStmtSymmetricProperty($subjectUri, $objectUriOrLiteral);
			} elseif( $this->isPropType(GAMA_Store::TYPE_EQUIVALENCE_PROPERTY) )
			{
				$this->addStmtEquivalenceProperty($subjectUri, $objectUriOrLiteral);
			} else
			{
				throw new Exception('Unsupported property type');
			}
		} catch(Exception $e)
		{
			app_unlock();
			throw $e;
		}
		// ===========
		app_unlock();
		// ===========
	}

	/**
	 * @param string $subjectUri
	 * @param string|RDFS_Literal $objectLiteral
	 */
	private function addStmtDatatypeProperty($subjectUri, $objectLiteral)
	{
		// convert the value to RDFS_Literal
		if( ! $objectLiteral instanceof RDFS_Literal)
		{
			$objectLiteral = new RDFS_Literal($objectLiteral);
		}
		
		assert('/* must be RDFS_Literal because getParsedLiteral requires it */ $objectLiteral instanceof RDFS_Literal');
		
		$store = GAMA_Store::singleton();

		$subjectResource = Resource_Manager::singleton()->getResourceByUri($subjectUri);
		$subjectId = $subjectResource->getID();
		
		$datatypeInstance = GAMA_Datatype::getDatatypeByUri($this->datatype);

		// parse the object into an array of database columns
		$pval = $datatypeInstance->getParsedLiteral($objectLiteral);
		$pval['g'] = $store->getGraphID();
		$pval['subject'] = $subjectId;

		// construct the sql command
		$sqlcols = implode(',', array_keys($pval));
		$sqlvals = implode(',', array_fill(0, count($pval), '?') );

		// insert the statement into the database
		// silently continue if the statement has already been inserted

// DEBUG: this updates lang to "xx" in case a duplicate entry is detected
//		$store->sql(
//			"insert into {$this->getStmtTab()} ($sqlcols) values ($sqlvals) on duplicate key update lang='xx'",
//			array_values($pval) );

		$store->sql(
			"insert ignore into {$this->getStmtTab()} ($sqlcols) values ($sqlvals)",
			array_values($pval) );
	}
	
	/**
	 * @param string $subjectUri
	 * @param string $objectUri
	 */
	private function addStmtObjectProperty($subjectUri, $objectUri)
	{
		// if there is an inverse property, the owl:ObjectProperty will store
		// the value in the master-of-the-inverse property
		if(!$this->isHavingInverse() || $this->isThisInverseMaster())
		{
			$store = GAMA_Store::singleton();
			$resourceManager = Resource_Manager::singleton();
			
			// add the object to the repository
			$this->rangeClass->addIndividual($objectUri);
			
			// we need the objectId
			$objectResource = $resourceManager->getResourceByUri($objectUri);
			$objectId = $objectResource->getID();
	
			// we need the subjectId
			$subjectResource = $resourceManager->getResourceByUri($subjectUri);
			$subjectId = $subjectResource->getID();
			
			// insert the statement into the database
			// silently continue if the statement has already been inserted 
			$store->sql(
				"insert ignore into {$this->getStmtTab()} (g, subject, object) values (?,?,?)",
				$store->getGraphID(), $subjectId, $objectId);
		} else
		{
			$this->inverseProperty->addStatement($objectUri, $subjectUri);
		}
	}

	/**
	 * @param string $subjectUri
	 * @param string $objectUri
	 */
	private function addStmtSymmetricProperty($subjectUri, $objectUri)
	{
		assert('/* subject must be a string (URI) */ is_string($subjectUri)');
		assert('/* object must be a string (URI) */ is_string($objectUri)');
		
		assert('/* symmetric property requires domain equal to range */ $this->domainClass === $this->rangeClass');

		$store = GAMA_Store::singleton();

		// symmetric property stores only triple for (subject, object) where subject <= object
		// that's why the "ORDER BY uri" clause
		$stmt = $store->sql('
			select id from RESOURCE
			where uri in (?,?) order by uri', $subjectUri, $objectUri);

		$smallerId = $stmt->fetchColumn(); // represents the smaller URI
		$greaterId = $stmt->fetchColumn(); // represents the greater URI
		unset($stmt); // the PDOStatement destructor should close the connection

		// only one direction is stored:  UNIQUE((subject,object)|(object,subject))
		// even if it's OR expression, this should be fast in mysql (try explain select...)
		$count = $store->sqlFetchValue("
			select count(*) from {$this->getStmtTab()}
			where (subject = :smaller and object = :greater)
			or (subject = :greater and object = :smaller)
		", array(	'smaller' => $smallerId,
					'greater' => $greaterId ));
			
		if(!$count)
		{
			$store->sql("
				insert into {$this->getStmtTab()} (g, subject, object)
				values (?, ?,?)",
				$store->getGraphID(), $smallerId, $greaterId );
		}
	}

	/**
	 * @param string $subjectUri
	 * @param string $objectUri
	 */
	private function addStmtEquivalenceProperty($subjectUri, $objectUri)
	{
		assert('/* subject must be a string (URI) */ is_string($subjectUri)');
		assert('/* object must be a string (URI) */ is_string($objectUri)');
		
		assert('$this->domainClass === $this->rangeClass');

		// prepare references to singletons to use locally
		$store = GAMA_Store::singleton();
		$resourceManager = Resource_Manager::singleton();
		
		// make sure that both - subject and object - are stored as resources
		// inside the repository. By default create them as an instance of
		// rdfs:Resource
		$rdfsResourceClass = $resourceManager->getResourceByUri(GAMA_Store::RDFS_RESOURCE_URI);
		$rdfsResourceClass->addIndividual($subjectUri);
		$rdfsResourceClass->addIndividual($objectUri);
		
		// domain is always real ID of an individual
		// range is id of the "equivalence  class" (as in the theory of sets)
		$firstResource = $resourceManager->getResourceByUri($subjectUri);
		$secondResource = $resourceManager->getResourceByUri($objectUri);
		
		$id1 = $firstResource->getID();
		$id2 = $secondResource->getID();
		
		// a resource always belongs to EXACTLY ONE "equivalence class"
		// (therefore the limit clause)
		// but there can be multiple statements from different graphs
		// however the statements should be exactly the same
		// (therefore the groupby clause)
		$results = $store->sql("
			select object from {$this->getStmtTab()}
			where subject in (?,?)
			group by subject limit 2", $id1, $id2 )->fetchAll(PDO::FETCH_NUM);
		
		$ec1 = @$results[0][0];
		$ec2 = @$results[1][0];

//		debug("ID1: $id1  EC1:$ec1");
//		debug("ID2: $id2  EC2:$ec2");

		// TODO: perhaps rewrite the smaller set
		if(empty($ec1) && empty($ec2))
		{
			$store->sql("
				insert into {$this->getStmtTab()} (g, subject, object)
				values (?,?,?), (?,?,?)",
				$store->getGraphID(), $id1, $id1,
				$store->getGraphID(), $id2, $id1 );
		} else
		{
			if($ec1 == $ec2)
			{
				debug('already same equivalency class');
			} elseif(empty($ec1))
			{
				$store->sql("
					insert into {$this->getStmtTab()} (g, subject, object)
					values (?,?,?)", $store->getGraphID(), $id1, $ec2 );
			} elseif(empty($ec2))
			{
				$store->sql("
					insert into {$this->getStmtTab()} (g, subject, object)
					values (?,?,?)", $store->getGraphID(), $id2, $ec1);
			} else
			{
				$store->sql("update {$this->getStmtTab()} set object=? where object=?", $ec1, $ec2);
			}
		}
	}
	
	/**
	 * TODO: replace by deleteStatements() method.
	 * The problem is that the deleteStatements() function restricts to a particular graph  
	 */
	public function deletePropertyData()
	{
		$this->isInStore(true);

		//========================
		app_lock(LOCK_EX);
		//========================
		try
		{
			$store = GAMA_Store::singleton();
			
			if(!$this->isHavingInverse())
			{
				$store->sql("delete from {$this->getStmtTab()}");
			} else
			{
				$store->sql("delete from {$this->inverseMaster->getStmtTab()}");
			}
		}catch(Exception $e)
		{
			app_unlock();
			throw $e;
		}
		//===============
		app_unlock();
		//===============
	}
	
	/**
	 * @param string $subject
	 * @param string|RDFS_Literal $object
	 */
	public function deleteStatements($subject=null, $object=null)
	{
		//================================
		app_lock(LOCK_EX);
		//================================
		try
		{
			if( $this->isPropType(GAMA_Store::TYPE_DATATYPE_PROPERTY) )
			{
				$this->deleteStmtDatatypeProperty($subject, $object);
			} elseif( $this->isPropType(GAMA_Store::TYPE_OBJECT_PROPERTY) ||
			          $this->isPropType(GAMA_Store::TYPE_TRANSITIVE_PROPERTY) )
			{
				$this->deleteStmtObjectProperty($subject, $object);
			} elseif( $this->isPropType(GAMA_Store::TYPE_SYMMETRIC_PROPERTY) )
			{
				$this->deleteStmtSymmetricProperty($subject, $object);
			} elseif( $this->isPropType(GAMA_Store::TYPE_EQUIVALENCE_PROPERTY) )
			{
				$this->deleteStmtEquivalenceProperty($subject, $object);
			} else
			{
				throw new Exception('Unsupported property type in delete operation');
			}
		} catch(Exception $e)
		{
			app_unlock();
			throw $e;
		}
		//================================
		app_unlock();
		//================================
	}
	
	/**
	 * Delete statements of a property using a filter. 
	 * @param integer $tableId
	 * @param array $selectionFilter
	 */
	static private function deleteStmtByFilter($tableId, $selectionFilter = array())
	{
		$store = GAMA_Store::singleton();

		// the format is "sql column name" => "value"
		$selectionFilter['g'] = $store->getGraphID();
		
		// construct and execute the SQL statement
		$sql = "DELETE FROM $tableId";
		
		// add the SQL WHERE clause
		if(!empty($selectionFilter))
		{
			foreach(array_keys($selectionFilter) as $variableName)
			{
				$whereClause[] = "$variableName = :$variableName";
			}
			
			$sql .= ' WHERE '. implode(' and ', $whereClause);
		}
		
		$store->sql( $sql, $selectionFilter );
	}
	
	/**
	 * @param string $subjectUri
	 * @param RDFS_Literal $object
	 */
	private function deleteStmtDatatypeProperty($subjectUri = null, RDFS_Literal $objectLiteral = null)
	{
		assert('$subjectUri === null || is_string($subjectUri)');
		assert('$objectLiteral === null || $objectLiteral instanceof RDFS_Literal');

		$selectionFilter = array();
		
		if(!empty($subjectUri))
		{
			$subjectResource = Resource_Manager::singleton()->getResourceByUri($subjectUri); 			
			$selectionFilter['subject'] = $subjectResource->getID();
			
			if(!empty($objectLiteral))
			{
				// empty object deletes all statements of particular subject
				$datatypeInstance = GAMA_Datatype::getDatatypeByUri($this->datatype);
				$pval = $datatypeInstance->getParsedLiteral($objectLiteral);
				assert(isset($pval['object']));
				
				$selectionFilter['object'] = $pval['object'];
			}
		}
		
		self::deleteStmtByFilter( $this->getStmtTab(), $selectionFilter );
	}
	
	/**
	 * @param string $subjectUri
	 * @param string $objectUri
	 */
	private function deleteStmtObjectProperty($subjectUri = null, $objectUri = null)
	{
		assert('$subjectUri === null || is_string($subjectUri)');
		assert('$objectUri === null || is_string($objectUri)');
		
		// ------------------------------------
		// if slave -> use inverse master
		// ------------------------------------
		if($this->isHavingInverse() && !$this->isThisInverseMaster())
		{
			// swap subject and object 
			$this->getInverseMaster()->deleteStatements($objectUri, $subjectUri);
			return;
		}
		
		// ------------------------------------
		// if master
		// ------------------------------------
		$selectionFilter = array();
		
		if(!empty($subjectUri))
		{
			$subjectResource = Resource_Manager::singleton()->getResourceByUri($subjectUri); 
			$subjectId = $subjectResource->getID();
			$selectionFilter['subject'] = $subjectId;
		}
			
		if(!empty($objectUri))
		{
			$objectResource = Resource_Manager::singleton()->getResourceByUri($objectUri);
			$objectId = $objectResource->getID();
			$selectionFilter['object'] = $objectId;
		}
		
		self::deleteStmtByFilter( $this->getStmtTab(), $selectionFilter );
	}
	
	/**
	 * @param string $subjectUri
	 * @param string $objectUri
	 */
	private function deleteStmtSymmetricProperty($subjectUri = null, $objectUri = null)
	{
		$selectionFilter = array();
		$inverseSelectionFilter = array();
		
		if(!empty($subjectUri))
		{
			$subjectResource = Resource_Manager::singleton()->getResourceByUri($subjectUri); 
			$subjectId = $subjectResource->getID();

			$selectionFilter['subject'] = $subjectId;
			$inverseSelectionFilter['object'] = $subjectId;
		}
			
		if(!empty($objectUri))
		{
			$objectResource = Resource_Manager::singleton()->getResourceByUri($objectUri);
			$objectId = $objectResource->getID();

			$selectionFilter['object'] = $objectId;
			$inverseSelectionFilter['subject'] = $objectId;
		}
		
		self::deleteStmtByFilter( $this->getStmtTab(), $selectionFilter );
		self::deleteStmtByFilter( $this->getStmtTab(), $inverseSelectionFilter );
	}

	/**
	 * @param string $subjectUri
	 * @param string $objectUri
	 */
	private function deleteStmtEquivalenceProperty($subjectUri = null, $objectUri = null)
	{
		$selectionFilter = array();
		
		if(!empty($objectUri))
		{
			// equivalence spans through multiple graphs
			$eqid = GAMA_Store::singleton()->sqlFetchValue(
				"select object from {$this->getStmtTab()} where subject=?",
				$objectUri );
				
			if(empty($eqid))
			{
				return;
			}
			$selectionFilter['object'] = $eqid;
		}
		
		if(!empty($subjectUri))
		{
			$selectionFilter['subject'] = $subjectUri;
		}
		
		self::deleteStmtByFilter($this->getStmtTab(), $selectionFilter );
	}
}
?>