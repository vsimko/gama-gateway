<?php
/**
 * Inserts a RDF statement into the repository.
 * TODO: unstable service
 */
class Insert_Statement extends RPC_Service
{
	/**
	 * URI of the subject.
	 * @datatype uri
	 * @required
	 */
	static $PARAM_SUBJECT = 'subject';

	/**
	 * URI of the predicate (property).
	 * @datatype uri
	 * @required
	 */
	static $PARAM_PREDICATE = 'predicate';

	/**
	 * URI of the object or a literal value.
	 * @datatype uri
	 * @required
	 */
	static $PARAM_OBJECT = 'object';

	/**
	 * URI of the named graph.
	 * @datatype uri
	 * @optional
	 */
	static $PARAM_GRAPH = 'graph';
	
	/**
	 * TODO define the result format
	 */
	public function execute()
	{
		$subjectURI = $this->getParam(self::$PARAM_SUBJECT);
		$predicateURI = $this->getParam(self::$PARAM_PREDICATE);
		$objectURI = $this->getParam(self::$PARAM_OBJECT);
		$graphURI = $this->getParam(self::$PARAM_GRAPH, self::OPTIONAL);
		
		if(!empty($graphURI))
		{
			echo "Graph: $graphURI\n";
		}
		
		echo "Statement will be inserted here: $subjectURI, $predicateURI, $objectURI\n";
	}
}

?>