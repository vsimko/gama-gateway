# Introduction #

It is impossible for everybody to be familiar with all URI conventions and namespaces used in the GAMA project. For example, DB-Adapters produce different kinds of URIs. Even the GAMA Metadata Schema uses multiple namespaces.

### URI ###
Each statement stored in the repository consists of 3 parts: **subject**, **predicate**, **object**.

Each statement provides an information about the relationship between resources or literal values. A resource is always identified by a world-wide unique identifier called URI.

Example: `http://gama-gateway.eu/schema/Work` is a unique identifier of the Work class from the GAMA Metadata Schema.

### Namespace ###
Namespaces is a mechanism which simplifies the usage of URIs.

Example of usage in SPARQL: instead of URIs
  * `http://gama-gateway.eu/schema/Work`
  * `http://gama-gateway.eu/schema/Manifestation`
  * `http://gama-gateway.eu/schema/Person`

one could write: `PREFIX gama: <http://gama-gateway.eu/schema/>`
  * `gama:Work`
  * `gama:Manifestation`
  * `gama:Person`

For more information about XML namespaces see http://www.w3.org/TR/REC-xml-names/

### Base URI ###
During the insert operation inside the RDF repository, all statements are associated with a specific graph name. In case of an RDF/XML file, the graph name is defined by the `xml:base attribute` of the `rdf:RDF` root XML element. The concept of separate graphs provides an easy yet powerful mechanism for identification and manipulation of statements. A graph with all its statements can easily be deleted using the SPARQL DELETE statement.

In other words, Base URI allows for grouping statements and easy manipulation.


# List of namespaces #


---

## `http://gama-gateway.eu/schema/` ##
The namespace is used for all resources that are part of the GAMA Metadata Schema i.e. properties, classes and also some specific function of the RDF repository. For more information see the generated documentation at http://research.ciant.cz/gama/schemadoc/

**Examples:**
  * `http://gama-gateway.eu/schema/Work` class
  * `http://gama-gateway.eu/schema/work_title` property
  * `http://gama-gateway.eu/schema/dateIntervalBegin` function in the SPARQL



---

## `http://gama-gateway.eu/cache/` ##
This namespace is used within the ontology which defines the caching properties. The GAMA Metadata Schema described in the Deliverable 2.3 has further been extended on the basis of the new caching mechanism designed by CIANT. The main goal is to enhance the overall query performance of GAMA portal.

**Example:**
  * `http://gama-gateway.eu/cahe/fulltext_works` property


---

## `http://gama-gateway.eu/schema/WorkType/` ##



---

## `http://gama-gateway.eu/schema/Genre/` ##

... and more to come