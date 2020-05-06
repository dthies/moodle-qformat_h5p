# H5P Quiz Format #

This plugin imports various h5p content types into the Moodle question
types

Certain h5p content types are able to be translated into questions
using standard Moodle question types. Not all h5p content types will
be supported since they do not all have analogous Moode question types
with similar functionality. There will be some unavoidable differences
in behaviour.

To install copy this directory to question/format/h5p in Moodle directory
structure. Login as admin to complete plugin installation.  Then select
this format during question bank import or export.

To import H5P content load a Quiz (Question Set) .h5p file or a Column
content type file which contains some of the supported question content
types as the import file and import. Indvidual questions will be extract
from the Quiz.

Currently supports import of following H5P content types

* Column - extracts supported subcontent 
* Quiz (Question Set) - extracts supported subcontent 
* Multichoice Question 
* True/False Question 
* Drag and Drop - Converts to ddmarker with which has labels, but not drag images
* Fill in the Blank Question
* Drag the Text Question

## License ##

2020 Daniel Thies <dethies@gmail.com>

This program is free software: you can redistribute it and/or modify it
under the terms of the GNU General Public License as published by the
Free Software Foundation, either version 3 of the License, or (at your
option) any later version.

This program is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License
for more details.

You should have received a copy of the GNU General Public License along
with this program.  If not, see <http://www.gnu.org/licenses/>.
