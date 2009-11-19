<?php

/**
 * Stores a given tag for the tagcould functionality.
 * The frontend needs to display the most popular tags of various kinds.
 */
class Store_Tag extends RPC_Service
{
	/**
	 * The following types are supported at the moment:
	 * <b>keyword, work, artist</b>
	 * 
	 * @datatype string
	 * @required
	 */
	static $PARAM_TAG_TYPE = 'type';
	
	/**
	 * The value to store.
	 * Multiple values can be delimited by newlines or "##".
	 * @datatype string
	 * @required
	 */
	static $PARAM_TAG_VALUE = 'value';
		
	/**
	 * Nothing to be returned
	 */
	public function execute()
	{
		$tagtype = $this->getParam(self::$PARAM_TAG_TYPE);	
		Init_Tagcloud_Structure::assertTagType($tagtype);
		
		$multivalue = $this->getParam(self::$PARAM_TAG_VALUE);
		
		$store = GAMA_Store::singleton();
		
		@header('Content-type: text/plain');
		
		foreach(preg_split('/\n+|##/', $multivalue) as $tagvalue)
		{			
			// fetch the ID of the tag, store the tag if needed
			$tagid = $store->sqlFetchValue(
				'select tagid from TAG_LIST where tagtype=? and tagstr=?',
				$tagtype, $tagvalue);

			// ===============
			app_lock(LOCK_EX);
			// ===============
			if(empty($tagid))
			{
				try
				{
					$store->sql('insert ignore into TAG_LIST (tagtype, tagstr) values (?,?)', $tagtype, $tagvalue);
					$tagid = $store->sqlFetchValue(
						'select tagid from TAG_LIST where tagtype=? and tagstr=?',
						$tagtype, $tagvalue);
				} catch(Exception $e)
				{
					app_unlock();
					throw $e;
				}
			}
			
			$store->sql('
				insert into TAG_FREQ (tagid,upd_when,upd_howmany) values (?,curdate(),1)
				on duplicate key update upd_howmany=upd_howmany+values(upd_howmany);
				', $tagid);
			
			// store total value for faster access
			$store->sql('update TAG_LIST SET total=total+1 where tagid=?', $tagid);
			
			
			// ==========
			app_unlock();
			// ==========
			
			$howmany = $store->sqlFetchValue('select upd_howmany from TAG_FREQ where upd_when=curdate() and tagid=?', $tagid);
			
			echo "$tagtype '$tagvalue' stored using the id '$tagid' today $howmany times\n";
		}
	}
}
?>