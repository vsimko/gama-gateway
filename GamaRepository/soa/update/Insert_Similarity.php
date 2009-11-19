<?php
/**
 * Inserts a single similarity into the repository.
 * TODO: unstable service
 */
class Insert_Similarity extends RPC_Service
{
	/**
	 * URI of the main manifestation
	 * @datatype uri
	 * @required
	 */
	static $PARAM_MAIN_URI = 'mainuri';
	
	/**
	 * URI of the subordinate manifestation identified as similar
	 * @datatype uri
	 * @required
	 */
	static $PARAM_SIMILAR_URI = 'similaruri';
	
	/**
	 * Enter description here...
	 * @datatype integer 0..255
	 * @required
	 */
	static $PARAM_SHOT_ID = 'shotid';
	
	/**
	 * Weight of the similarity.
	 * @datatype integer 0..255
	 * @required
	 */
	static $PARAM_WEIGHT = 'weight';
	
	/**
	 * ??? todo
	 * @datatype integer 0..255
	 * @required
	 */
	static $PARAM_BEST_MATCH = 'bestmatch';
	
	/**
	 * The result is "1" if the insertion was successful.
	 */
	public function execute()
	{
		$mainURI = $this->getParam(self::$PARAM_MAIN_URI);
		$similarURI = $this->getParam(self::$PARAM_SIMILAR_URI);
		$shotId = $this->getParam(self::$PARAM_SHOT_ID);
		$weight = $this->getParam(self::$PARAM_WEIGHT);
		$bestMatch = $this->getParam(self::$PARAM_BEST_MATCH);
		
		echo "NEW FRAME: $mainURI, $similarURI, $shotId, $weight, $bestMatch\n";
	}
}
?>