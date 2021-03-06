Created by Viliam Simko 2009-08-05

- All files should be uploaded into the "Update" directory preserving the following structure
	- dba/				- RDF/XML files generated by db-adapters (works, persons, manifestations ...)
	- harmonisation/	- RDF/XML files generated by the harmonisation component
	- indexing/			- RDF/XML files generated by the indexing engine - ocr, keywords, manifestations (except similarities)
	- schema/			- all metadata schemas (base schema, caches, schema translations ...)
	- similarities/		- XML files of similarities generated by the indexing engine
	- vocabulary/		- RDF/XML files with controlled vocabularies (work types, terms, countries ...)
	- CHANGELOG.TXT

So far we identified 4 components producing/consuming metadata.
- DB-Adapters
- Indexing engine
- Harmonisation tool
- RDF repository

The Update Protocol consists of these steps:
============================================

1. The update process is started by DB-Adapters. Before uploading the XML data,
   DB-Adapters will trigger the "trigger_before_dba.php" script.
   
   - on success: DB-Adapter can upload XML data
   - on failure: DB-Adapter should wait because there is another update lifecycle still pending.

2. After all XML data have been uploaded, DB-Adapter will trigger
   the "trigger_after_dba.php" script which indicates that the Indexing engine
   and the Harmonisation tool can use the new XML data. Indexing engine
   and Harmonisation tool can work in parallel.

3.1. By triggering "trigger_after_idx.php", the Indexing engine indicates that
     it has generated some XML files.

3.2. By triggering "trigger_after_harmonisation.php", the harmonisation tool
     indicates that it has uploaded XML (harmonisation export). 

4. Repository waits for the Indexing engine and Harmonisation tool, then
   ingests all XML data and rebuilds the internal database.
   After the rebuild, it will trigger the last script "trigger_after_rebuild.php"
   When the rebuild is finished, the content of the "Update" directory is copied
   to the "Backup" directory with the timestamp as the filename.

4.1 If the indexing engine or the harmonistaion tool are not responding for more
    than one day, the repository performs a forced rebuild and then calls
    the "trigger_forced_rebuild.php" script.
    
