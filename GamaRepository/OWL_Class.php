<?php

class OWL_Class extends Base_State
{
	public function createInStore()
	{
		if($this->isInStore())
		{
			return;
		}
		
		// ========================
		app_lock(LOCK_EX);
		// ========================
		try
		{
			$store = GAMA_Store::singleton();
			$found = $store->sqlFetchValue(
				'select id from RESOURCE where uri=?', $this->getUri());
			
			if(empty($found))
			{
				$this->rdftype = GAMA_Store::OWL_CLASS_URI;
				
				// get ID of the owl:Class resource
				$typeId = Resource_Manager::singleton()->getResourceByUri(GAMA_Store::OWL_CLASS_URI)->getID();
				$this->id = $store->getNewResourceID();				
				$store->sql('insert into RESOURCE (id, type, uri) values (?, ?,?)',
					$this->id, $typeId, $this->uri);
				
				debug("Class added '$this->uri'");
			}
		} catch(Exception $e)
		{
			app_unlock();
			throw $e;
		}
			
		//===============
		app_unlock();
		//===============
	}
	
	/**
	 * Permanently delete the resource from the repository.
	 */
	public function deleteFromStore()
	{
		$this->isInStore(true);

		$store = GAMA_Store::singleton();

		// ===============
		app_lock(LOCK_EX);
		// ===============
		try
		{
			// make sure there are no instances of this class
			$count = $store->sqlFetchValue(
				'select count(*) from RESOURCE where type=?', $this->id);
			
			if($count > 0)
			{
				throw new Exception('Delete related instances first');
			}

			// make sure there are no properties using this class
			$count = $store->sqlFetchValue('
				select count(*) from PROPERTY
				where dom=? or rng=?
				', $this->id, $this->id );
			
			if($count > 0)
			{
				throw new Exception('Delete related properties first');
			}
			
			// now we can delete the class
			$store->sql('delete from RESOURCE where id=?', $this->id);

			// we should also update our cache
			$this->reloadFromStore();
			
		} catch(Exception $e)
		{
			app_unlock();
			throw $e;
		}
			
		// ==========
		app_unlock();
		// ==========
		
		debug("Resource deleted: $this->uri");
	}
	
	/**
	 * At the moment, we only use a single type per resource.
	 * Each resource has a unique URI but a group of resources may become
	 * equivalent using the owl:sameAs property. If that is the case,
	 * the URIs use same local ID.
	 * TODO: should use multiple types per resource
	 * @param string $uri The URI of the resource we want to add to this class.
	 * @param bool $replaceType TODO: this parameter is needed only if we use single type per resource
	 */
	public function addIndividual($uri, $replaceType = false)
	{
		if(empty($uri))
		{
			throw new Exception('Could not add class individual with empty URI');
		}
		
		$store = GAMA_Store::singleton();
		
		// ================
		app_lock(LOCK_EX);
		// ================
		
		try
		{
			$id = $store->sqlFetchValue('select id from RESOURCE where uri=?', $uri);
			if(empty($id))
			{
				// avoid inserting blacklisted URIs
				GAMA_Blacklist_Hash::singleton()->scanBlacklists($uri);
				
				// generate ID of the new resource
				$newIndividualID = $store->getNewResourceID();
				
				$store->sql('
					insert ignore into RESOURCE
					(id, type, uri) values (:id, :typeid, :uri)',
					array(	'id'		=> $newIndividualID,
							'typeid'	=> $this->id,
							'uri'		=> $uri ));
			}
			
			// TODO: only a tempory workaround, GAMA should use multiple types pre resource
			elseif($replaceType)
			{
				$store->sql('
					update RESOURCE
					set type = :typeid
					where id = :id',
					array(	'id'		=> $id,
							'typeid'	=> $this->id ));
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
}
?>