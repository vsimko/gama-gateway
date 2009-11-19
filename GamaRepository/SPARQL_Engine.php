<?php

class SPARQL_Engine_Exception extends Exception
{
//	function __construct($msg, $code=null)
//	{
//		parent::__construct("ERROR: $msg", $code);
//	}
}

/**
 * SPARQL has also it's namespace http://www.w3.org/2006/sparql-functions#
 * It is also possible to reuse the engine several times.
 * Every useSparql call resets the state.
 * @author Viliam Simko
 */
class SPARQL_Engine
{
	/**
	 * Indentation string used in the compiled SQL
	 * @var unknown_type
	 */
	const INDENT_STR = ' ';

	/**
	 * Some of the SPARQL functions can add extra SQL command that will be
	 * executed before the compiled SQL statment.
	 * This allows, for example, for tweaking MySQL buffers.
	 * @var string
	 */
	private $extraSql;
	
	/**
	 * Compiled SQL string
	 * @var string
	 */
	private $sql;
	
	/**
	 * array
	 *   alias:string => array
	 *     'name'	=> string
	 *     'comment' => string
	 *     'optional' => boolean
	 *   ...
	 * 
	 * @var array
	 */
	private $tables = array();
	
	/**
	 * array
	 *   varname:string => array
	 *     'bind'		=> string
	 *     'out'		=> string
	 *     'outlang'	=> string
	 *     'isout'		=> boolean
	 *     'tab'		=> string
	 *   ...
	 *
	 * @var array
	 */
	public $vars = array();
	
	/**
	 * TODO: this is a nasty hack
	 * List of sql variables used in select clause
	 * which will not be used in the XML resultset.
	 * Such variables are only needed internally. For
	 * example the levenshtein distance sorts and filters
	 * the results using the SQL HAVING clause. 
	 * @var array
	 */
	public $hiddenVars = array();
	
	/**
	 * This variable represents the SQL WHERE clause
	 * @var array
	 */
	private $cond = array();
	
	/**
	 * This variable represents the SQL HAVING clause.
	 * @var array
	 */
	public $condHaving = array();
		
	/**
	 * RDF Dataset restriction.
	 * @var string
	 */
	private $joinGraphTable = null;
	
	/**
	 * Indicates that the parser dived into an OPTIONAL statement.
	 * @var boolean
	 */
	private $insideOptional = false;
	
	/**
	 * The SQL string is used instead of parsing the entire SPARQL command.
	 * @param $sql
	 * @param $outputVars array of variable names
	 */
	public function useSql($sql, array $outputVars)
	{
		$this->sql = $sql;
		
		$this->vars = array();
		foreach($outputVars as $varName)
		{
			$this->vars[$varName]['isout'] = true;
		}
	}
	
	/**
	 * The given SPARQL string is loaded and transformed locally into SQL.
	 * Use the getSql() function the get the compiled SQL.
	 * This is where the actual mapping from SPARQL to SQL happens.
	 * @param string $sparql
	 */
	public function useSparql($sparql)
	{
		// write every SPARQL query to the debug log
		if(Config::get('sparql.logusedsparql'))
		{
			debug("SPARQL : ".str_repeat('-',80)."\n".preg_replace('/^\s+/m', '', $sparql));
		}
		
		// reset engine
		unset($this->sql);
		$this->extraSql = '';
		$this->tables = array();
		$this->vars = array();
		$this->hiddenVars = array();
		$this->cond = array();
		$this->condHaving = array();
		$this->joinGraphTable = null;
		$this->insideOptional = false;
		
		$old_er = error_reporting(E_ALL);
		
		require_once 'arc2/ARC2.php';
		$parser = ARC2::getSPARQLPlusParser();
		$parser->parse($sparql);
		
		if (!$parser->getErrors())
		{
			$r = $parser->getQueryInfos();
		} else
		{
			throw new SPARQL_Engine_Exception('Invalid query');
		}
		
		$this->handleQuery($r['query']);
		
		error_reporting($old_er);
	}
	
	/**
	 * @return string
	 */
	public function getSql()
	{
		if(empty($this->sql))
		{
			throw new SPARQL_Engine_Exception('There is no SQL yet. Try to compile it from SPARQL.');
		}
		
		return $this->sql;
	}
	
	/**
	 * Output variables found in the SPARQL.
	 * @return array
	 */
	public function getOutputVariables()
	{
		if(empty($this->vars))
		{
			throw new SPARQL_Engine_Exception('Output variables not available');
		}
		
		$outvars = array();
		foreach($this->vars as $varname => $var)
		{
			if(!empty($var['isout']) && empty($var['inaggregate']))
			{
				$outvars[] = $varname;
			}
		}
		return $outvars;
	}
		
	/**
	 * 
	 * @var SPARQL_Result_Handler_Interface
	 */
	private $resultHandler;
	
	/**
	 * Methods of this object will be called on important events.
	 * @param SPARQL_Result_Handler_Interface $object
	 */
	public function setResultHandler(SPARQL_Result_Handler_Interface $object = null)
	{
		$this->resultHandler = $object;
	}
	
	/**
	 * You can pass custom query handler to the constructor, otherwise you would
	 * need to use the setResultHandler function.
	 * @param SPARQL_Result_Handler_Interface $resultHandler
	 */
	public function __construct(SPARQL_Result_Handler_Interface $resultHandler = null)
	{
		$this->setResultHandler($resultHandler);
	}
	
	/**
	 * This is the method where the SQL statement is executed in the underlying
	 * database and where the events are passed to the query handler.
	 */
	public function runQuery()
	{
		// The function should also throw an exception if the query has not yet
		// been prepared.
		$sql = $this->getSql();
		assert('!empty($sql)');
		
		$debugString = array(
			"using database:'".GAMA_Store::getDatabaseName()."' timestamp:".date('Y-m-d h:i:s'),
			"SQL:\n{$this->extraSql}{$sql}" );
		
		// ============================================
		// report beginning of the stream
		$this->resultHandler->onBeginResults($this, $this->getOutputVariables(), $debugString);
		// ============================================
		
		try
		{
			// execute the extra SQL before the compiled SQL
			if(!empty($this->extraSql))
			{
				GAMA_Store::singleton()->sql($this->extraSql);
			}
			
			// now execute the compiled SQL
			$stmt = GAMA_Store::singleton()->sql( $sql );
			
		} catch(Exception $e)
		{
			throw new SPARQL_Engine_Exception($e->getMessage()."\n\n".$this->sql);
		}
		
		// this allows for streamed processing of the results
		// the resultHandler can even ignore all of the results if needed
		while($record = $stmt->fetch(PDO::FETCH_ASSOC))
		{
			$this->resultHandler->onFoundResult($this, $record);
		}
		
		// ============================================
		// report end of the stream
		$this->resultHandler->onEndResults($this);
		// ============================================
	}
	
	public function addExtraSql($extraSql)
	{
		$this->extraSql .= $extraSql.";\n";
	}
	
	/**
	 * @param string $uri
	 * @return int
	 */
	public function uri2id($uri)
	{
		$resource = Resource_Manager::singleton()->getResourceByUri($uri);
		$resource->isInStore(true);
		return $resource->getID().' /* '.mysql_escape_string($uri).' */';
	}
	
	/**
	 * Prepares URI to be included directly to SQL.
	 * @param $uri
	 * @return string
	 */
	private function uri2sqluri($uri)
	{
		// this should prodice an exception for non-existing URIs
		$resource = Resource_Manager::singleton()->getResourceByUri($uri);
		$resource->isInStore(true);
		
		return '"'.mysql_escape_string($uri).'"';
	}
	
	/**
	 * @param string $tabname
	 * @param string $comment
	 * @return string generated table alias
	 */
	public function addTable($tabname, $comment = null, $forceIndex = null)
	{
		$tabalias = 't'.count($this->tables);
		assert('/* table alias already exists */ empty($this->tables[$tabalias])');
		
		$this->tables[$tabalias]['name']	= $tabname;
		$this->tables[$tabalias]['comment']	= $comment;
		$this->tables[$tabalias]['forceindex'] = $forceIndex;
			
		return $tabalias;
	}
	
	/**
	 * @param int $propid
	 * @param string $comment
	 * @return string generated table alias
	 */
	public function addStmtTable($propid, $comment = null, $forceIndex = null)
	{
		$tabalias = $this->addTable("S_$propid", $comment, $forceIndex);
		
		// join with graph
		if( ! empty($this->joinGraphTable))
		{
			$this->addTableJoinCondition($tabalias, 'g', "$this->joinGraphTable.id");
		}
			
		return $tabalias;
	}
	
	/**
	 * Adds a join condition to the table definition.
	 * This should compile to JOIN .. ON clause in SQL.
	 *
	 * @param string $tableAlias
	 * @param string $tableColumn
	 * @param stirng|array $joinItems
	 */
	private function addTableJoinCondition($tableAlias, $tableColumn, $joinItems)
	{
		if(is_array($joinItems))
		{
			$condition = ' in ('.implode(', ', $joinItems).')';
		} else
		{
			$condition = ' = '.$joinItems;
		}
		
		$this->tables[$tableAlias]['join'][] = $tableAlias.'.'.$tableColumn.$condition;

		// this is an experimenatal support for the OPTIONAL clause
		if($this->insideOptional)
		{
			$this->tables[$tableAlias]['optional'] = true;
		}
	}
	
	private function isResultVar($varname)
	{
		return @$this->vars[$varname]['isout'];
	}
	
	/**
	 * Adds new table for a given variable if necessary.
	 * @param string $varname
	 * @param string $tabname
	 * @return string
	 */
	public function updateVar2($varname, $tabname)
	{
		// this variable did not contain table reference yet
		if(empty($this->vars[$varname]['tab']))
		{
			$this->vars[$varname]['tab'] = $this->addTable($tabname, "due to '$varname' variable");
		}
		
		$this->vars[$varname]['bind']	= $this->vars[$varname]['tab'].'.id';
		$this->vars[$varname]['out']	= $this->vars[$varname]['tab'].'.uri';
		
		return $this->vars[$varname]['bind'];
	}
	
	/**
	 * @param array $query
	 */
	private function handleQuery($query)
	{
		// prepare and execute the method d
		$funcname = 'handleQuery_'.$query['type'];
		if(!method_exists($this, $funcname))
		{
			throw new SPARQL_Engine_Exception('Unsupported query: '.$query['type']);
		}
		
		return call_user_func(array($this, $funcname), $query);
	}
	
	/**
	 * @param array $query
	 */
	private function handleQuery_select($query)
	{
		// creates list of result vars in a desired order
		foreach($query['result_vars'] as $v)
		{
//			if(empty($v['aggregate']))
//			{
				$varname = empty($v['var']) ? $v['value'] : $v['var'];
				
				$this->vars[$varname]['isout'] = true;
				$this->vars[$varname]['inaggregate'] = !empty($v['aggregate']);
//			}
		}

		$this->handlePattern($query['pattern']);
		
		// output variables including aliases must be unique
		$usedAliases = array();
		
		$rvars = array();
		foreach($query['result_vars'] as $v)
		{
			if(is_array($v))
			{
				
				// the name of the output variable is decided here 
				if(empty($v['var']))
				{
					$name = $v['value'];
					$alias = $v['value'];
				} else
				{
					$name = $v['var'];
					$alias = $v['alias'] ? $v['alias'] : $v['var'];
				}
				
				// aliases must be unique
				if(isset($usedAliases[$alias]))
				{
					throw new SPARQL_Engine_Exception('Multiple definitions of the output variable: ?'.$alias.' in the SELECT clause');
				} else
				{
					$usedAliases[$alias] = true;
				}

				if(!empty($v['aggregate']))
				{
					// allow '*' inside the aggreate function instead of a variable
					$aggregateVar = $name == '*' ? '*' : $this->vars[$name]['bind'];
					
					// construct the SQL aggreage function
					if(strtolower($v['aggregate']) == 'count_distinct')
					{
						// Note: Added support for MySQL COUNT DISTINCT construct
						$this->vars[$name]['out'] = 'count(distinct '.$aggregateVar.')';
					} else
					{
						$this->vars[$name]['out'] = $v['aggregate'].'('.$aggregateVar.')';
					}
					$this->vars[ $v['alias'] ]['isout'] = true;
					
					// aggregate functions such as COUNT should produce literals not uris
					$this->vars[ $v['alias'] ]['isdatatype'] = true;
					
					// we can also use this variable in ORDER BY clause
					$this->vars[ $v['alias'] ]['orderby'] = $v['alias'];
				}
				
				if(empty($this->vars[$name]['out']))
				{
					throw new SPARQL_Engine_Exception("Output variable '?$name' not used within the query");
				}
				
				$rvars[] = "\n".self::INDENT_STR.$this->vars[$name]['out']." as $alias";
				
				if(!empty($this->vars[$name]['outlang']))
				{
					$rvars[] = "\n".self::INDENT_STR.$this->vars[$name]['outlang']." as {$alias}_lang";
				}
				
				// indicates that the variable represents a datatype
				if(isset($this->vars[$alias]['isdatatype']))
				{
					$rvars[] = "\n".self::INDENT_STR."'1' as {$alias}_dt";
					// TODO: use the actual datatype, not just "1"
				}
			}
		}
		
		// special variables used internally, not included in the XML output
		if(!empty($this->hiddenVars))
		{
			foreach($this->hiddenVars as $v)
			{
				$rvars[] = "\n".self::INDENT_STR.$v.' /* hidden */';
			}
		}
		
		// check if there are some result vars
		if(empty($rvars))
		{
			throw new SPARQL_Engine_Exception('No result variables in the query.');
		}
		
		// =======================
		// SQL STARTS HERE
		// =======================
		// there might have been some extra SQL beforehand added using the
		// addExtraSql() function
		$this->sql .= "SELECT"
			. (Config::get('sparql.querycache') ? '' : ' SQL_NO_CACHE')
			. (empty($query['distinct']) ? '' : ' DISTINCT')
			. implode(',', $rvars);
		
		// get the graphID when selecting from a specific graph
		$graphID = array();
		foreach((array) @$query['dataset'] as $dataset)
		{
			$id = GAMA_Store::singleton()->sqlFetchValue('select id from GRAPH where uri=?', $dataset['graph']);

			if($id === null)
			{
				throw new SPARQL_Engine_Exception('No such graph in the repository');
			}
				
			$graphID[] = $id;
		}
		
		// "from" clause
		// !!! must be called before the "where condition" because
		// graph restriction conditions might have been included
		if(!empty($this->tables))
		{
			$this->sql .= "\nFROM";
			$firstIteration = true;
			foreach($this->tables as $tabalias => $tabdef)
			{
				// add graph restrictions
				if($tabdef['name'][0] == 'S' && !empty($graphID))
				{
					$this->addTableJoinCondition($tabalias, 'g', $graphID );
				}
				
				// add table comment
				if(!empty($tabdef['comment']))
				{
					if(strpos($tabdef['comment'], "\n"))
					{
						// multiline comment
						$this->sql .= "\n".self::INDENT_STR.'/* '.preg_replace(
							array('/:([a-zA-Z])/','/\*\//'),
							array(': \1',''),
							$tabdef['comment'] ).' */';
					} else
					{
						//oneline comment
						$this->sql .= "\n".self::INDENT_STR.'-- '.$tabdef['comment'];
					}
				}
				
				// add table name
				// add the "JOIN" if this is not the first iteration
				$joinType = @$tabdef['optional'] ? 'left join' : 'join';
				$this->sql .= "\n".self::INDENT_STR.
					($firstIteration ? '' : $joinType.' ').
					$tabdef['name'].' as '.$tabalias;
					
				// add trailing SQL snippet after table definition
				if(!empty($tabdef['forceindex']))
				{
					//TODO: this is possible optimisation
					//$this->sql .= ' FORCE INDEX('.$tabdef['forceindex'].')';
				}

				// add ON clause...
				if(!empty($tabdef['join']))
				{
					if($firstIteration)
					{
						// move the condition to the WHERE clause
						$this->cond = array_merge($this->cond, $tabdef['join']);
					} else
					{
						// use the condition in the ON clause
						$this->sql .= ' on '. implode(' and ', $tabdef['join'] );
					}
				}
				
				$firstIteration = false;
				$this->sql .= "\n";
			}
		}

		// SQL WHERE clause
		if(!empty($this->cond))
		{
			$this->sql .=
				"\nWHERE\n".self::INDENT_STR .
				implode("\n".self::INDENT_STR.'and ', $this->cond);
		}
		
		// SQL HAVING clause
		if(!empty($this->condHaving))
		{
			$this->sql .=
				"\nHAVING\n".self::INDENT_STR .
				implode("\n".self::INDENT_STR.'and ', $this->condHaving);
		}
		
		// SQL GROUP BY clause
		if(!empty($query['group_infos']))
		{
			$groupby = array();
			foreach($query['group_infos'] as $info)
			{
				$groupby[] = $this->vars[$info['value']]['bind'];
			}
			$this->sql .= "\nGROUP BY ".implode(', ', $groupby);
		}
				
		// SQL ORDER BY clausule
		$orderbysql = array();
		if(!empty($query['order_infos']))
		{
			foreach($query['order_infos'] as $o)
			{
				if($o['type'] == 'function_call')
				{
					$orderbysql[] = $this->handleConstraint($o);
				} else
				{
					
					$orderByVarName = @$this->vars[$o['value']]['orderby'];
					if(empty($orderByVarName))
					{
						$orderByVarName = $this->vars[$o['value']]['bind'];
					}
					
					if(empty($this->vars[$o['value']]['tab']) || empty($this->tables[$this->vars[$o['value']]['tab']]['optional']))
					{
						// in case of a simple join
						$orderbysql[] = "$orderByVarName $o[direction]";
					} else
					{
						// in case of a left join (OPTIONAL clause)
						// NULL values should always appear at the bottom, therefore we use COALESCE function
						if($o['direction'] == 'asc')
						{
							$orderbysql[] = "coalesce($orderByVarName, 'zzz') asc";
						} elseif($o['direction'] == 'desc')
						{
							$orderbysql[] = "coalesce($orderByVarName, '') desc";
						} else
						{
							throw new Exception('Unknown direction in ORDER BY caluse.');
						}
					}
				}
			}
		}
		
		// HACK: levenshtein distance needs a special ORDER BY item
		if(!empty($this->hiddenVars))
		{
			foreach ($this->hiddenVars as $vname => $v)
			{
				$orderbysql[] = $vname.' ASC';
			}
		}
		
		// SQL ORDER BY clause
		if(!empty($orderbysql))
		{
			$this->sql .= "\nORDER BY ".implode(', ', $orderbysql);
		}
		
		// SQL LIMIT clause
		if(!empty($query['limit']))
		{
			$this->sql .= "\nLIMIT $query[limit]";
		}
		
		// SQL OFFSET clause
		if(!empty($query['offset']))
		{
			$this->sql .= "\nOFFSET $query[offset]";
		}
		$this->sql .= "\n";
	}
	
	/**
	 * @param array $query
	 */
	private function handleQuery_delete($query)
	{
		$store = GAMA_Store::singleton();
		
		if( ! empty($query['target_graphs']))
		{
			assert('count($query["target_graphs"]) == 1');
			$store->setGraph($query["target_graphs"][0]);
		}
		
		// special hack for deleting all statements from a particular graph
		if(empty($query['construct_triples']))
		{
			//================
			app_lock(LOCK_EX);
			//================
			try
			{
				// for each property delete all statements
				$results = $store->sql('select uri as propertyUri from PROPERTY')->fetchAll(PDO::FETCH_ASSOC);
				foreach($results as $row)
				{
					$propertyUri = $row['propertyUri'];
					$property = Resource_Manager::singleton()->getResourceByUri($propertyUri);
					$property->deleteStatements();
				}
				
				// delete also the graph URI from GRAPH table
				if( ! empty($query['target_graphs']))
				{
					$store->sql('delete from GRAPH where uri=?', $query["target_graphs"][0]);
				}
			} catch(Exception $e)
			{
				app_unlock();
				throw $e;
			}
			//===========
			app_unlock();
			//===========
			return;
		}
		
		// no varibales are allowed at the moment
		foreach($query['construct_triples'] as $id => $triple)
		{
			if($triple['s_type'] != 'uri')
			{
				throw new SPARQL_Engine_Exception('Statement URI expected');
			}
				
			if($triple['p_type'] != 'uri')
			{
				throw new SPARQL_Engine_Exception('Property URI expected');
			}
							
			$p = Resource_Manager::singleton()->getResourceByUri($triple['p']);
			$p->isInStore(true);

			if($triple['o_type'] == 'uri')
			{
				$p->deleteStatements($triple['s'], $triple['o']);
			} elseif($triple['o_type'] == 'literal')
			{
				$p->deleteStatements($triple['s'], new RDFS_Literal($triple['o'], @$triple['o_lang']));
			} elseif($triple['o_type'] == 'var')
			{
				$p->deleteStatements($triple['s']);
			} else
			{
				throw new SPARQL_Engine_Exception('Expected URI, literal or variable as object');
			}
		}
	}
	
	/**
	 * @param array $pattern
	 */
	private function handlePattern($pattern)
	{
		switch($pattern['type'])
		{
			case 'group':
				foreach($pattern['patterns'] as $p)
				{
					$this->handlePattern($p);
				}
				break;
				
			case 'triples':
				foreach($pattern['patterns'] as $p)
				{
					$this->handleTriple($p);
				}
				break;
			
			case 'filter':
				$cond = $this->handleConstraint($pattern['constraint']);
				if(!empty($cond))
				{
					if($this->insideOptional)
					{
						$lastKey = end(array_keys($this->tables));
						$this->tables[$lastKey]['join'][] = $cond;
						//$this->tables[$lastKey]['optional'] = true;
					} else
					{
						$this->cond[] = $cond;
					}
				}
				break;
				
			case 'graph':
				$this->handleGraph($pattern);
				break;
				
			case 'optional':
				$lastInsideOptional = $this->insideOptional; 
				$this->insideOptional = true;
				foreach($pattern['patterns'] as $p)
				{
					$this->handlePattern($p);
				}
				$this->insideOptional = $lastInsideOptional;
				break;
				
			default:
				throw new SPARQL_Engine_Exception('Unsupported pattern: '.$pattern['type']);
		}
	}
	
	/**
	 * @param array $pattern
	 */
	private function handleGraph(array $pattern)
	{
		if(empty($pattern['var']['value']))
		{
			// is URI = generate an internal variable
			$gvar = uniqid('gvar_');
		} else
		{
			// is variable
			$gvar = $pattern['var']['value'];
		}

		
		// this should handle nesting
		$old = $this->joinGraphTable;
		
			// TODO: what if the GRAPH variable is not an output variable
			$x = $this->updateVar2($gvar, 'GRAPH');
			$this->joinGraphTable = $this->vars[$gvar]['tab'];
			
			if(empty($pattern['var']['value']))
			{
				// use the condition inside the GRAPH table and filter the URI
				$this->tables[$this->vars[$gvar]['tab']]['join'][] =
					$this->vars[$gvar]['out'].' = "'.mysql_escape_string($pattern['uri']).'"';
			}
			
			foreach($pattern['patterns'] as $p)
			{
				$this->handlePattern($p);
			}
			
		$this->joinGraphTable = $old;
	}
	
	/**
	 * @param array $t the triple
	 */
	private function handleTriple(array $t)
	{
		// ------- some tests due to the limitations of the repository  --------
		// varialbes in properties
		if($t['p_type'] != 'uri')
		{
			throw new SPARQL_Engine_Exception(
				'Variable in place of a property not supported' );
		}
		
		// becuase variables in properties are not allowed, one of the
		// subject and object must be a variable
		if($t['s_type'] == 'uri' &&  $t['o_type'] == 'uri')
		{
			throw new SPARQL_Engine_Exception(
				'Subject and object cannot be URIs at once' );
		}
		
		// subjects cannot be literals
		if($t['s_type'] == 'literal')
		{
			throw new SPARQL_Engine_Exception(
				'Subject cannot be a literal' );
		}
		// ---------------------------------------------------------------------
		
		$property = Resource_Manager::singleton()->getResourceByUri( $t['p'] );
		
		if($property->getUri() == 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type')
		{
			if($t['o_type'] == 'var' || $t['o_type'] == 'bnode')
			{
				$this->updateVar2($t['o'], 'RESOURCE');
				$tabo = $this->vars[$t['o']]['tab'].'.id';
			} else
			{
				$tabo = $this->uri2id($t['o']);
			}
			
			if($t['s_type'] == 'var' || $t['s_type'] == 'bnode')
			{
				$this->updateVar2($t['s'], 'RESOURCE');
				$this->addTableJoinCondition($this->vars[$t['s']]['tab'], 'type', $tabo );
			} else
			{
				$tab = $this->addTable('RESOURCE', 'for type');
				//BEFORE SAMEAS: $this->addTableJoinCondition($tab, 'id', $this->uri2id($t['s']));
				$this->addTableJoinCondition($tab, 'uri', $this->uri2sqluri($t['s']));
				$this->addTableJoinCondition($tab, 'type', $tabo );
			}
		} else
		{
			$property->isInStore(true);
			if( $property->isPropType(GAMA_Store::TYPE_EQUIVALENCE_PROPERTY) )
			{
				$tab2 = $this->addStmtTable($property->getID(), 'EQ2:'.$property->getUri());
				$tab1 = $this->addStmtTable($property->getID(), 'EQ1:'.$property->getUri());
				//$this->cond[] = $property->stmtObject($tab1).' = '.$property->stmtObject($tab2);
				$this->addTableJoinCondition($tab1, 'object', "$tab2.object");
	
				$this->joinStatement($t['s'], $t['s_type'], $property->stmtSubject($tab1)); // subject
				$this->joinStatement($t['o'], $t['o_type'], $property->stmtSubject($tab2)); // object
			
			} elseif($property->isPropType(GAMA_Store::TYPE_DATATYPE_PROPERTY))
			{
				$tab = $this->addStmtTable($property->getID(), $property->getUri(), 'sortidx');
				
				$this->vars[$t['o']]['outlang'] = $property->stmtLang($tab);
				
				$this->joinStatement($t['s'], $t['s_type'], $property->stmtSubject($tab));
				$this->joinDatatypeStatement(
					$t['o'],
					$t['o_type'],
					$property->stmtValue($tab),
					$tab,
					$property->stmtOrderByValue($tab)
					);
				
				// this handles the situation where the objects is: "literal"@lang
				if( $t['o_type'] == 'literal' && !in_array($t['o_lang'], array('','any')) )
				{
					if($property->stmtLang($tab) == null)
					{
						throw new SPARQL_Engine_Exception("The property '{$property->getUri()}' does not support automatic language binding");
					}
					$this->cond[] = $property->stmtLang($tab).' = "'.mysql_escape_string($t['o_lang']).'"';
					//$this->addTableJoinCondition($tab, 'lang', '"'.mysql_escape_string($t['o_lang']).'"');
				}
				
			} else
			{
				$swap = $property->isPropType(
					GAMA_Store::TYPE_SYMMETRIC_PROPERTY) && $t['o'] < $t['s'];
			
				$property->isInStore(true);
				if( $property->isHavingInverse() && !$property->isThisInverseMaster() )
				{
					$property = $property->getInverseMaster();
					$swap = true;
				}
				
				$tab = $this->addStmtTable($property->getID(), $property->getUri());
				
				// master subject			
				$this->joinStatement($t['s'], $t['s_type'],
					$swap ? $property->stmtObject($tab) : $property->stmtSubject($tab) );
					
				// master object
				$this->joinStatement($t['o'], $t['o_type'],
					$swap ? $property->stmtSubject($tab) : $property->stmtObject($tab) );
			}
		}
	}
	
	/**
	 * @param string $x name of the variable or a value
	 * @param string $type type of the $x (whether it is literal or variable)
	 * @param string $join_with reference in the sql
	 */
	public function joinStatement($x, $type, $join_with)
	{
		if($type == 'uri')
		{
			$this->cond[] = $join_with.' = '.$this->uri2id($x);
		} elseif($type == 'var' || $type == 'bnode') // ?var or _:var
		{
			// just save join_with to log
			$this->vars[$x]['joinlog'][] = $join_with;
			
			// new variable definition
			if(empty($this->vars[$x]['bind']) && $this->isResultVar($x))
			{
				$tab = $this->addTable('RESOURCE', "outvar:$x");
				$this->vars[$x]['tab']	= $tab;
				$this->vars[$x]['bind']	= $tab.'.id';
				$this->vars[$x]['out']	= $tab.'.uri';
			}
			
			$j = @$this->vars[$x]['bind'];
			
			if(empty($j))
			{
				$this->vars[$x]['bind'] = $join_with;
			} else
			{
				// $join_with should always be smaller than $j
				if(
					intval(preg_replace('/[^0-9]/', '', $j)) <
					intval(preg_replace('/[^0-9]/', '', $join_with) )
				)
				{
					//swap $j and $join_with
					$tmp = $j;
					$j = $join_with;
					$join_with = $tmp;
				}
				
				list($tableAlias, $columnName) = explode('.', $j);
				$this->addTableJoinCondition($tableAlias, $columnName, $join_with);
			}
		} else
		{
			throw new SPARQL_Engine_Exception('Unsupported type');
		}
	}
	
	/**
	 * @param string $x name of the variable or a value
	 * @param string $type type of the $x (whether it is literal or variable)
	 * @param string $join_with reference in the sql
	 * @param string $stmt_table which table represents the statement
	 * @param string $dt_order_by_in
	 */
	public function joinDatatypeStatement($x, $type, $join_with, $stmt_table, $dt_orderby_in)
	{
		// ?var or _:var
		if($type == 'var' || $type == 'bnode')
		{
			if(empty($this->vars[$x]['bind']))
			{
				// HACK: use "orderby" instead of "bind" in ORDERBY clause in case of DatatypeProperty
				$this->vars[$x]['orderby'] = $dt_orderby_in;

				$this->vars[$x]['bind']	= $join_with;
				$this->vars[$x]['out']	= $join_with;
				$this->vars[$x]['tab']	= $stmt_table;
				$this->vars[$x]['isdatatype'] = true; // datatype properties have literals not uris
			} else
			{
				//newjoin
				list($tableAlias, $columnName) = explode('.', $this->vars[$x]['bind']);
				$this->addTableJoinCondition($tableAlias, $columnName, $join_with);
			}
		} elseif($type == 'literal')
		{
			// also taking into account the OPTIONAL statement 
			$cond = $join_with.'="'.mysql_escape_string($x).'"';
			if($this->insideOptional)
			{
				$this->tables[$stmt_table]['join'][] = $cond;
			} else
			{
				$this->cond[] = $cond;
			}
		} else
		{
			throw new SPARQL_Engine_Exception('Unsupported type');
		}
	}

	/**
	 * Factory method.
	 * @param $p
	 * @return SPARQL_Item
	 */
	public function factoryItem($p)
	{
		switch($p['type'])
		{
			case 'var':
			case 'bnode':	return new SPARQL_Item_Var($p, $this);
			case 'uri':		return new SPARQL_Item_Uri($p, $this);
			case 'literal':	return new SPARQL_Item_Literal($p, $this);
		}
		throw new SPARQL_Engine_Exception('Unsupported parameter type:'.$p['type']);
		assert('/* not reached */');
	}
	
	/**
	 * @param array $constr
	 * @return string
	 */
	public function handleConstraint(&$constr)
	{
		switch($constr['type'])
		{
			// -----------------------------------------------------------------
			case 'built_in_call':
				$constr['uri'] = 'http://www.w3.org/2006/sparql-functions#'.$constr['call'];
			case 'function':
			case 'function_call':
				$classname = GAMA_Utils::normaliseUri( $constr['uri'] );

				if(! class_exists($classname, true))
				{
					throw new Exception(
						'Our SPARQL implementation does not support this function: '.
						"$constr[uri] (classname $classname)");
				}
				
				// use add_include_path() function in the config file to point to the directory
				// with sparql functions. Usually, this is plugins/sparqlfunc.
				$func = new $classname($this, $constr['args']);
				return $func->execute();
			
				
			// -----------------------------------------------------------------
			case 'expression':
				return $this->handleExpression(	$constr['sub_type'],
												@$constr['operator'],
												$constr['patterns'] );

			// -----------------------------------------------------------------
			case 'var':
				if(empty($this->vars[$constr['value']]['bind']))
				{
					return array('varname' => $constr['value']);
				}
				return $this->vars[$constr['value']]['bind'];
				
			// -----------------------------------------------------------------
			case 'uri':
				return $constr; // solve later
				//return $this->uri2id($constr['uri']);
				
			// -----------------------------------------------------------------
			case 'literal':
				return '"'.mysql_escape_string($constr['value']).'"';
				
			// -----------------------------------------------------------------
			default:
				throw new SPARQL_Engine_Exception('Unsupported constraint:'.$constr['type']);
		}
	}
	
	/**
	 * @param string $etype
	 * @param string $operator
	 * @param array $patterns
	 * @return string
	 */
	private function handleExpression($etype, $operator, $patterns)
	{
		$buffer = array();
		foreach($patterns as $pattern)
		{
			$buffer[] = $this->handleConstraint($pattern);
			$buffer = array_filter($buffer);
		}
		
		// there must be at least two operands in the buffer
		if(!isset($buffer[1]))
		{
			throw new SPARQL_Engine_Exception(
				"Missing second operand in the expression:\n".
				print_r($patterns, true)
			);
		}
		
		switch($etype)
		{
			case 'or':			return '('.implode(" or\n ", $buffer).')';
			case 'and':			return '('.implode(" and\n ", $buffer).')';
			case 'relational':
				if($operator == '!=')
				{
					// HACK: the negation should match also if the value is NULL
					return "($buffer[0] != $buffer[1] or $buffer[0] is null)";
				}
				elseif($operator == '=')
				{
					if(is_array($buffer[0]))
					{
						// TODO: just a hack to disallow uris 
						if(empty($buffer[0]['varname']))
						{
							throw new SPARQL_Engine_Exception(
								"URIs not allowed on the left side of the '$operator' operator in: "
								."FILTER ( &lt;{$patterns[0]['uri']}&gt; {$operator} ?{$patterns[1]['value']} )"
								);
						}
						
						$varname = $buffer[0]['varname'];
						$this->vars[$varname]['bind'] = $buffer[1]; 
						$this->vars[$varname]['out'] = $buffer[1];
						$this->vars[$varname]['orderby'] = $buffer[1];
						$this->vars[$varname]['isdatatype'] = true;
	
						return null; //'true /* variable assignment */';
					}
					
					// nasty HACK for sameAs functionality
					if(is_array($buffer[1]) && $buffer[1]['type'] == 'uri')
					{
						// table name
						$tabname = preg_replace('/\..*/', '', $buffer[0]);
						
						// if not an output variable, we have to add RESOURCE table
						$varname = $patterns[0]['value'];
						if(empty($this->vars[$varname]['tab']))
						{
							$this->updateVar2($varname, 'RESOURCE');
							$this->joinStatement($varname, 'var', $buffer[0]);
							return $this->handleExpression($etype, $operator, $patterns);
						} else
						{
							return $tabname.'.uri = '.$this->uri2sqluri($buffer[1]['uri']);
						}
					}
				}
				
				return "$buffer[0] $operator $buffer[1]";
				
			default: throw new SPARQL_Engine_Exception('Unsupported expression');
		}
	}
}
?>