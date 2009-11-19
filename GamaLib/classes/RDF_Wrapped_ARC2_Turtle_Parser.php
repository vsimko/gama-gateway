<?php

require_once '../ARC2/ARC2.php';

/**
 * This is only a transitional class wrapping the ARC2_TurtleParser
 * until the native implementation is available.
 * 
 * The grammar of N-Triples is defined here:
 * http://www.w3.org/2001/sw/RDFCore/ntriples/#sec-grammar
 * 
 */
class RDF_Wrapped_ARC2_Turtle_Parser extends RDF_Parser
{
	protected function parseImpl()
	{
		// arc2 does not like STRICT error reporting
		$old = error_reporting(E_ALL);
		
		$arc2parser = ARC2::getTurtleParser();
		$arc2parser->parse($this->location);
		
		$errors = $arc2parser->getErrors();
		if(! empty($errors))
			throw new Exception('Error occured:'.htmlspecialchars(implode("\n", $errors)));
		
		// using ARC2 is 4x slower because ARC2 parses everyting into a huge array.
		// we traverse that array and report each triple
		foreach($arc2parser->getTriples() as $t)
		{
			assert('/* only triples are allowed */ $t["type"] == "triple"');
			dispatcher($this)->onNewTriple(
				$t['s'],
				$t['p'],
				$t['o_type'] == 'uri' ? $t['o'] : new RDFS_Literal($t['o'], $t['o_lang'])
				);
		}
		
		foreach($arc2parser->nsp as $uri => $prefix)
			dispatcher($this)->onNewNamespace($prefix, $uri);

		// back to previous error_reporting mode
		error_reporting($old);
			
	}
}
?>