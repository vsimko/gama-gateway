# Introduction #
Custom RDF Metadata Parsers for GAMA Wiki.



# Details #
there are currently 4 types of tags.

**1. {{ #gama: gama:instants:main:Work:2994 | video }}**
  * this tag when parsed will play the video file ( manifestation ) for the corresponding work.
  * Flowplayer ( an Open Source (GPL 3) video player for the Web ) is used to stream the video
  * A snapshot showing the tag in action <br /> http://lh4.ggpht.com/_AvjH5NX3xEE/SjItOSYEHhI/AAAAAAAAAaY/PnQw3MyxHr0/s640/video.PNG

**2. {{ #gama: gama:instants:main:Work:2700 | full | fr }}**
  * this tag when parsed will create a table with full description like Type,Title,Artist,Information of the work.
  * this tag will look for the Language french denoted by 'fr'. If found it will display the content in french else it will display the content in default language.
  * A snapshot showing the tag in action <br />![http://lh5.ggpht.com/_AvjH5NX3xEE/SjIsTL06CsI/AAAAAAAAAaM/foCfcegfbVw/full.png](http://lh5.ggpht.com/_AvjH5NX3xEE/SjIsTL06CsI/AAAAAAAAAaM/foCfcegfbVw/full.png)

**3. {{ #gama: gama:instants:main:Work:2700 | short | fr }}**
  * this tag when parsed will create a table with Title and Artist of the work.
  * this tag will similarly look for content in french.

**3. {{ #gama: gama:instants:main:Work:2700 | name | fr }}**
  * this tag when parsed will create a table with only the name of Artist of the work.
  * the language 'fr' has no use and can be omitted from the tag.