<?php
/**
 * Transforms a Work URI to a Video Stream URL.
 * @author Viliam Simko
 */
class Get_Video_Of_Work extends RPC_Service
{
	/**
	 * URI of the Work
	 * @datatype uri
	 * @required
	 */
	static $PARAM_WORK_URI	= 'uri';
	
	/**
	 * Video URL per line (usually only a single line).
	 */
	function execute()
	{
		$workUri = $this->getParam(self::$PARAM_WORK_URI, self::REQUIRED);
		
		foreach($this->getMainManifUris($workUri) as $manifUri)
		{
			$baseurl = preg_replace('/\/$/','',Config::get('repo.mediabaseurl'));
			echo "$baseurl/$manifUri/preview.mp4\n";
		}
	}
	
	/**
	 * Transforms work URI to main manifestation URIs.
	 * @return array
	 */
	protected function getMainManifUris($workUri)
	{
		header('Content-type: text/plain');
		$json = $this->getRpcClient()->{'query/Aggregated_Sparql'}('
			PREFIX gama: <http://gama-gateway.eu/schema/>
			AGGREGATE ? => ?m
			select ?m {
				'.GAMA_Utils::escapeSparqlUri($workUri).' gama:has_manifestation ?m.
				?m gama:idx_main "1".
				?m gama:idx_stream_avail ?stream.
				FILTER (?stream != "0")
			}
		');
		
		$manifestations = json_decode($json, true);
		
		if(! is_array($manifestations))
		{
			throw new Exception('Could not find any video streams for given work URI: '.$workUri);
		}
		
		return $manifestations;
	}
}
?>