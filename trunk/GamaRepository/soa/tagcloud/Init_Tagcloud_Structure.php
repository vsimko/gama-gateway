<?php
/**
 * Initialise the database tables for the tagcloud.
 * WARNING: All the old data will be removed from tagcloud-related tables
 */
class Init_Tagcloud_Structure extends RPC_Service
{
	const TABLE_TAG_LIST = 'TAG_LIST';
	const TABLE_TAG_FREQ = 'TAG_FREQ';
	
	/**
	 * List of allowed tag types.
	 * @noparam
	 */
	static private $allowedTagTypes = array('keyword', 'work', 'artist');

	/**
	 * Read-only property.
	 * @return array
	 */
	static function getAllowedTagTypes()
	{
		return self::$allowedTagTypes;
	}
	
	/**
	 * Throws an exception if the tag type is not supported.
	 * @param $tagtype
	 */
	static function assertTagType($tagtype)
	{
		if(! in_array(trim($tagtype), self::$allowedTagTypes))
		{
			throw new Exception('Unsupported tag type: '.$tagtype);
		}
	}
	
	/**
	 * Some information about the performed operation.
	 */
	public function execute()
	{
		$store = GAMA_Store::singleton();
		
		ob_implicit_flush(1);
		
		@header('Content-type: text/plain');
		
		// =====================================================================
		echo "Dropping the ".self::TABLE_TAG_LIST." table\n";
		$store->sql('drop table if exists '.self::TABLE_TAG_LIST);
		
		echo "Creating the ".self::TABLE_TAG_LIST." table\n";
		$store->sql('
			CREATE TABLE '.self::TABLE_TAG_LIST.' (
			  tagid int NOT NULL auto_increment,
			  tagtype enum("'.implode('","', self::$allowedTagTypes).'") not null,
			  tagstr varchar(250) NOT NULL,
			  total int not null default 0,
			  PRIMARY KEY (tagid),
			  UNIQUE KEY (tagtype, tagstr),
			  KEY (total)
			) ENGINE=MyISAM CHARSET=utf8
		');

		// =====================================================================
		echo "Dropping the ".self::TABLE_TAG_FREQ." table\n";
		$store->sql('drop table if exists '.self::TABLE_TAG_FREQ);
		
		echo "Creating the ".self::TABLE_TAG_FREQ." table\n";
		$store->sql('
			CREATE TABLE '.self::TABLE_TAG_FREQ.' (
			  tagid int NOT NULL,
			  upd_when date NOT NULL,
			  upd_howmany int NOT NULL default 1,
			  PRIMARY KEY (tagid, upd_when)
			) ENGINE=MyISAM DEFAULT CHARSET=latin1
		');
		
		echo "all done successfully.";
	}
}
?>