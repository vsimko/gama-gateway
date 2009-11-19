<?php
/**
 * Loads similarities from XML file in a specific format.
 * TODO: the format will be replaced by standard RDF/XML in future
 */
class Load_Similarities_From_File extends Load_Data_From_File
{
	/**
	 * This will supply GAMA_Similarity_Handler instead of GAMA_Data_Loader
	 * @return unknown_type
	 */
	protected function createTripleHandler()
	{
		return new GAMA_Similarity_Handler;
	}
}
?>