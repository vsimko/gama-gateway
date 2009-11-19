#!/bin/sh


# ==============================================================================
# Before we start:
# ==============================================================================
# apt-get install libmysql++-dev libmodplug-dev

LIBDIR=/usr/lib/
ARCH=`uname -m`
mkdir -p "$ARCH"

function build_target()
{
    TARGET="$1"
    g++ -fPIC -I/usr/include/mysql -I/usr/include/libmodplug -O -pipe \
    -o "$ARCH/$TARGET.so" -shared -lmysqlclient "$TARGET.cc"

    cp "$ARCH/$TARGET.so" "$LIBDIR"
}

# ==============================================================================
# Aggregate version of the levenshtein distance
# See: http://www.teamarbyte.de/levenshtein.html
# ==============================================================================
build_target 'levaggreg'

# DROP FUNCTION IF EXISTS lv;
# CREATE AGGREGATE FUNCTION lv RETURNS STRING SONAME "levaggreg.so";
# SELECT lv(Fieldname, 'Searchstring') FROM Table;


# ==============================================================================
# Josh Drew’s Levensode (levenshtein distance without transpositions)
# See: http://joshdrew.com/
# See: http://blog.lolyco.com/sean/2008/08/27/damerau-levenshtein-algorithm-levenshtein-with-transpositions/
# ==============================================================================
build_target 'levenshtein'

# DROP FUNCTION IF EXISTS levenshtein;
# CREATE FUNCTION levenshtein RETURNS INT SONAME "levenshtein.so";
# SELECT field, levenshtein(field, 'Searchword') as D FROM table where D < 5;


# ==============================================================================
# Basic Damerau-Levenshtein distance with limit to save computation time
# on very dissimilar strings (levenshtein with transpositions),
# See: http://blog.lolyco.com/sean/2008/08/27/damerau-levenshtein-algorithm-levenshtein-with-transpositions/
# NOTE: renamed from "davlem" to "davlevlim" in our implementation
# ==============================================================================
build_target 'damlevlim'

# DROP FUNCTION IF EXISTS damlevlim;
# CREATE FUNCTION damlevlim RETURNS INT SONAME "damlevlim.so";
# SELECT field, damlevlim(field, 'Searchword', 5) as D FROM table;


# ==============================================================================
# Damerau-Levenshtein distance, with limit and performance hack to pre-load row
# and column starts. Executes in less than 90% of time of damlevlim in tests.
# Time saving greater for longer strings, but not much. 256 ( - 1) is maximum
# length of string arguments (currently DOES NOT TEST for argument length!).
# The pre-loaded table is 64K in size, because that should be enough for anybody :D
# ==============================================================================
build_target 'damlevlim256'

# DROP FUNCTION IF EXISTS damlevlim256;
# CREATE FUNCTION damlevlim256 RETURNS INT SONAME "damlevlim256.so";
# SELECT field, damlevlim256(field, 'Searchword', 5) as D FROM table;


# ==============================================================================
# damlevlimnocase (case insensitive damlevlim)
# ==============================================================================
build_target 'damlevlimnocase'

# DROP FUNCTION IF EXISTS damlevlimnocase;
# CREATE FUNCTION damlevlimnocase RETURNS INT SONAME "damlevlimnocase.so";
# SELECT field, damlevlimnocase(field, 'Searchword', 5) as D FROM table;


# ==============================================================================
# levnocase (case insensitive levenshtein)
# ==============================================================================
build_target 'levnocase'

# DROP FUNCTION IF EXISTS levnocase;
# CREATE FUNCTION levnocase RETURNS INT SONAME "levnocase.so";
# SELECT field, levnocase(field, 'Searchword') as D FROM table where D < 5;
