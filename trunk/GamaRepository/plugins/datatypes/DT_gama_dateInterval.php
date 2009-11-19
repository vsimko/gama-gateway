<?php

require_once 'GAMA_Datatype.php';

/**
 * The size of the value is reatricted by the PHP's memory limit.
 */
class DT_gama_dateInterval extends GAMA_Datatype
{
	static public function getUri()
	{
		return 'http://gama-gateway.eu/schema/dateInterval';
	}
			
	public function getColumnDefinition()
	{
		return array(	'object'	=> 'binary(16) not null',
						'dfrom'		=> 'date not null',
						'dto'		=> 'date not null',
						'dstr'		=> 'varchar(32) null default null' );
	}
	
	public function getSortingIndexDefinition()
	{
		return 'dfrom,dto,dstr';
	}
	
	public function getIndexDefinition()
	{
		return array(	'index (dfrom)',
						'index (dto)',
						'index (dstr)' );
	}
	
	public function getValueDefinition($tabalias)
	{
		return "concat_ws(' ', $tabalias.dfrom, $tabalias.dto, $tabalias.dstr)";
	}
	
	public function getSortingValueDefinition($tabalias)
	{
		return array("$tabalias.dfrom", "$tabalias.dto");
	}
	
	public function getJoinDefinition($tabalias)
	{
		return "$tabalias.object";
	}

	public function getParsedLiteral(RDFS_Literal $literal)
	{
		$dval = self::parseDateInterval($literal->value);
		$dval['object'] = hash('md5', "$dval[dfrom] $dval[dto]", true);
		return $dval;
	}
	
	/**
	 * This is the method that does the actual parsing of the date interval
	 * value. The input is a string value in one of the supported formats
	 * and the result is an array with three elements
	 * 
	 * - dfrom = YYYY-MM-DD representing the beginning of the date interval
	 * - dto = YYYY-MM-DD representing the end of the date interval
	 * - dstr = representing the optional string comment
	 *  
	 * @param string $intervalValue
	 * @return array
	 */
	static public function parseDateInterval($intervalValue)
	{
		// allow date intervals in format: <from_year>-<to_year>
		// [0]	full match
		// [1]	dfrom YYYY
		// [2]	dto YYYY
		// [3]	ignored characters
		$regex = '/^(\d\d\d\d)-(\d\d\d\d|13|14|15|16|17|18|19|[2-9]\d)?( .*)?$/';
		
		if( preg_match($regex, $intervalValue, $pred) )
		{
			$d = array(
				0 => '',
				1 => @$pred[1],
				2 => @$pred[1], // avoid "now"
				3 => '',
				4 => '',
				5 => '',
				6 => '',
				7 => @$pred[2],'',
				8 => @$pred[2],'', // avoid "now"
				9 => '',
				10 => '',
				11 => '',
				12 => '');
		} else
		{
		
			// [0]	full match
			// [1]	* or full dfrom
			// [2]	dfrom YYYY
			// [3]	dfrom YYYY-MM
			// [4]	dfrom YYYY-MM-DD
			// [5]	full dto with junk at the beginning
			// [6]	spaces or ".." as delimiter 
			// [7]	* or full dto
			// [8]	dto YYYY
			// [9]	dto YYYY-MM
			// [10]	dto YYYY-MM-DD
			// [11]	dstr with junk at the beginning
			// [12]	dstr
			$regex = 
				'/^((\d\d\d\d)|(\d\d\d\d-\d\d)|(\d\d\d\d-\d\d-\d\d)|[^ \.]+)?'. // dfrom
				'('.
					'( *\.\. *| *[tT][oO] *| +)'. //delimiter
					'((\d\d\d\d)|(\d\d\d\d-\d\d)|(\d\d\d\d-\d\d-\d\d)|[^ ]+)'. // dto
				')?'.
				'( (.*))?$/'; // dstr
			
			if(! preg_match($regex, $intervalValue, $d) )
			{
				throw new GAMA_Datatype_Value_Exception('The value provided does not obey the expected date interval format');
			}
		}
		
		$dstr = @$d[12]; // arbitrary string at the end
				
		date_default_timezone_set('UTC'); // TODO: use the timezone properly
		
		try
		{
			//-------------------------------------------------------
			if (empty($d[1]))	{$dfrom = new DateTime('0000-00-00');} // minimum date supported by the DB layer (the "now" command works below)
			elseif ($d[1]=='*')	{$dfrom = new DateTime('0000-00-00');} // minimum date supported by the DB layer
			elseif (@$d[2])		{$dfrom = new DateTime($d[2].'-01-01');}
			elseif (@$d[3])		{$dfrom = new DateTime($d[3].'-01');}
			elseif (@$d[4])		{$dfrom = new DateTime($d[4]);}
			else				{$dfrom = new DateTime($d[1]);} // here works the "now" command
			//-------------------------------------------------------
			if (empty($d[7]))	{$dto = new DateTime($dfrom->format('Y').'-12-31 23:59:59');}
			elseif ($d[7]=='*')	{$dto = new DateTime('9999-12-31 23:59:59');} // maximum date supported by the DB layer
			elseif (@$d[8])		{$dto = new DateTime($d[8].'-12-31 23:59:59');}
			elseif (@$d[9])		{$dto = new DateTime($d[9].'-31 23:59:59');} //TODO: use 28,29,30 or 31 depending on month
			elseif (@$d[10])	{$dto = new DateTime($d[10].' 23:59:59');}
			else				{$dto = new DateTime($d[7].' 23:59:59');}
			//-------------------------------------------------------
		} catch (Exception $e)
		{
			throw new GAMA_Datatype_Value_Exception($e->getMessage());
		}
		
		$dfrom = $dfrom->format('Y-m-d');
		$dto = $dto->format('Y-m-d');
		
		if($dfrom > $dto)
		{
			throw new GAMA_Datatype_Value_Exception('Negative date interval detected');
		}
			
		return array(	'dfrom'		=> $dfrom,
						'dto'		=> $dto,
						'dstr'		=> $dstr );
	}
}

?>