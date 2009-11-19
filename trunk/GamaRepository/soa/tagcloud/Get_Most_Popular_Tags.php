<?php
/**
 * The frontend needs to display the most popular tags of various kinds.
 * <b>Note:</b> Try to avoid the date intervals for better performance.
 */
class Get_Most_Popular_Tags extends RPC_Service
{
	/**
	 * The following types are supported at the moment:
	 * <b>keyword, work, artist</b>
	 * Notes:
	 * - You can also use multiple types delimited by comma.
	 * - Leave empty to get all types.
	 * @datatype string
	 * @optional
	 */
	static $PARAM_TAG_TYPE = 'type';
		
	/**
	 * How many tags should be returned.
	 * - Leave empty or use "default" wildcard to get 10 results.
	 * - Use "all" or "*" wildcards to get all results. 
	 * @datatype integer
	 * @optional
	 */
	static $PARAM_LIMIT = 'limit';
	
	/**
	 * Beginning of the date interval (ISO format YYYY-MM-DD)
	 * @datatype string
	 * @optional
	 */
	static $PARAM_FROM = 'from';
	
	/**
	 * End of the date interval (ISO format YYYY-MM-DD)
	 * @datatype string
	 * @optional
	 */
	static $PARAM_TO = 'to';

	const DATEFORMAT_ALIGN_BEGIN = true;
	const DATEFORMAT_ALIGN_END = false;
	
	/**
	 * List of tags in the JSON format.
	 * Array of the following structures:
	 * <table>
	 *   <tr><th>type</th><td>type of the tag (string)</td></tr>
	 *   <tr><th>subject</th><td>the tag value (string)</td></tr>
	 *   <tr><th>count</th><td>number of occurrences of the tag (integer)</td></tr>
	 * </table>
	 */
	public function execute()
	{
		$tagtypes = $this->getParam(self::$PARAM_TAG_TYPE, self::OPTIONAL);
		
		if(empty($tagtypes))
		{
			// default value
			$tagtypes = Init_Tagcloud_Structure::getAllowedTagTypes();
		} else
		{
			// split the tagtype input parameter by commas to get the list of tagtypes
			$tagtypes = explode(',', $tagtypes);
		}
		
		assert('is_array($tagtypes)');
		
		// check validity of every tagtype
		foreach($tagtypes as $t)
		{
			Init_Tagcloud_Structure::assertTagType($t);
		}
		
		// get the limit paramter
		$limit = $this->getParam(self::$PARAM_LIMIT, self::OPTIONAL);
		switch(strtoupper($limit))
		{
			case '*':
			case 'ALL':
				$limit = '';
				break;
			
			case '':
			case 'DEFAULT':
				$limit = 10;
				break;
		}
		
		// get the dates paramters
		$dateFrom = $this->getParam(self::$PARAM_FROM, self::OPTIONAL);
		$dateTo = $this->getParam(self::$PARAM_TO, self::OPTIONAL);
		
		// use faster or slower version
		if(empty($dateFrom) and empty($dateTo))
		{
			// this is faster version
			$stmt = $this->prepareStmtFromTotal($tagtypes, $limit);
		} else
		{
			// this is slower version because of the date interval restriction
			$stmt = $this->prepareStmtFromDateInterval($tagtypes, $dateFrom, $dateTo, $limit);
		}
		
		// prepare the output as a flat array of tags
		$output = array();
		while($row = $stmt->fetch(PDO::FETCH_ASSOC))
		{
			$output[] = $row;
		}
		
		// here comes the result of this call
		@header('Content-type: text/plain');
		echo GAMA_Utils::jsonPrettyEncode($output);
	}
	
	/**
	 * Prepares the database statement using the cached total value.
	 * @param $tagtypes
	 * @param $limit
	 * @return PDOStatement
	 */
	private function prepareStmtFromTotal(array $tagtypes, $limit)
	{
		return GAMA_Store::singleton()->sql('
			select
				tagtype as type,
				tagstr as subject,
				total as count
			from TAG_LIST
			where tagtype in ('.GAMA_Utils::encodeArrayToSql($tagtypes).')
			order by total desc
			'.(is_numeric($limit) ? "limit $limit" : '')
			);
	}
	
	/**
	 * Prepares the database statement using the date interval.
	 * This method is rather slow comparing to the prepareStmtFromTotal
	 * because it uses GROUP BY in the query.
	 * @param $tagtypes
	 * @param $dateFrom
	 * @param $dateTo
	 * @param $limit
	 * @return PDOStatement
	 */
	private function prepareStmtFromDateInterval(array $tagtypes, $dateFrom, $dateTo, $limit)
	{
		$dateFrom = $this->getFormattedDate($dateFrom, self::DATEFORMAT_ALIGN_BEGIN);
		$dateTo = $this->getFormattedDate($dateTo, self::DATEFORMAT_ALIGN_END);
		
		return  GAMA_Store::singleton()->sql('
			select
				t.tagtype as type,
				t.tagstr as subject,
				sum(f.upd_howmany) as count
			from
				TAG_LIST t
				join TAG_FREQ f on t.tagid = f.tagid
			where
				t.tagtype in ('.GAMA_Utils::encodeArrayToSql($tagtypes).')
				and f.upd_when between ? and ?
			group by f.tagid
			order by count desc
			'.(is_numeric($limit) ? "limit $limit" : ''),
			$dateFrom, $dateTo);
	}
	
	/**
	 * Parses the date and returns YYYY-MM-DD aligned either to the beginning
	 * or end of the year.
	 * @param $str date to parse
	 * @param $alignToBegin
	 * @return string
	 */
	private function getFormattedDate($str, $alignToBegin = true)
	{
		if(empty($str))
		{
			return $alignToBegin ? '0000-00-00' : '9999-12-31';
		}
		
		if(preg_match('/^(....)(-(..)-(..))?$/', $str, $m))
		{
			if(empty($m[3])) // missing month and day
			{
				return $m[1] . ($alignToBegin ? '-01-01' : '-12-31');
			}
			
			// YYYY-MM-DD provided
			$d = new DateTime($str);
			return $d->format('Y-m-d');
		} else
		{
			throw new Exception("Unsupported date format: $str");
		}
	}
}
?>