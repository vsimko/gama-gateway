<?php
/**
 * Transforms a Work URI to an image.
 * The image is taken from the main manifestation.
 * @author Viliam Simko
 */
class Get_Image_Of_Work extends Get_Video_Of_Work
{
	/**
	 * Leave empty to get imgid=0.
	 * @datatype 
	 * @optional
	 */
	static $PARAM_IMGID	= 'imgid';

	/**
	 * Return URL of the large image or just the icon.
	 * @datatype boolean
	 * @optional
	 */
	static $PARAM_LARGE = 'large'; 
	
	const MAX_IMG_ID = 11;
	const MIN_IMG_ID = 0;
	
	/**
	 * Every image URL on a separate line. 
	 */
	function execute()
	{
		$workUri = $this->getParam(self::$PARAM_WORK_URI, self::REQUIRED);
		$imgId = $this->getParam(self::$PARAM_IMGID, self::OPTIONAL);
		if(!is_numeric($imgId) || $imgId < self::MIN_IMG_ID || $imgId > self::MAX_IMG_ID)
		{
			$imgId='0';
		}
		
		$useLarge = $this->getParam(self::$PARAM_LARGE, self::OPTIONAL);
		
		$baseurl = preg_replace('/\/$/','',Config::get('repo.mediabaseurl'));
		
		foreach($this->getMainManifUris($workUri) as $manifUri)
		{
			echo "$baseurl/$manifUri/$imgId.".($useLarge ? 'large' : 'icon').'.jpg'."\n";
		}
	}
}
?>