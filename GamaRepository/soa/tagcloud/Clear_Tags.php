<?php
/**
 * Clears occurrences of all tags of a specific type from a given day.
 * With the following constraints at the moment:
 * <ul>
 * <li>Only a single day is supported</li>
 * <li>Date intervals are NOT supported</li>
 * </ul>
 */
class Clear_Tags extends RPC_Service
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
	 * The date YYYY-MM-DD
	 * @datatype string
	 * @required
	 */
	static $PARAM_DATE_STRING = 'date';
	
	/**
	 * Nothing to be returned, just an information that the keyword was deleted.
	 */
	public function execute()
	{
		$tagtype = $this->getParam(self::$PARAM_TAG_TYPE);
		Init_Tagcloud_Structure::assertTagType($tagtype);
		
		$dateString = $this->getParam(self::$PARAM_DATE_STRING, self::REQUIRED);
		
		$date = new DateTime($dateString);
		$dateString = $date->format('Y-m-d');
		
		$store = GAMA_Store::singleton();

		// ===============
		app_lock(LOCK_EX);
		// ===============
		
		// update the cached totals
		$store->sql('
			update
				TAG_LIST as l
				join (
					select tagid, sum(upd_howmany) as howmany
					from TAG_FREQ
					where upd_when = ?
					group by tagid
				) as f on f.tagid=l.tagid
			set l.total = l.total - f.howmany
			where l.tagtype = ?
		', $dateString, $tagtype);
		
		$store->sql('delete from TAG_FREQ where upd_when=?', $dateString);
		
		// ==========
		app_unlock();
		// ==========
		
		@header('Content-type: text/plain');
		echo "$tagtype deleted from the date: $dateString\n";
	}
}
?>