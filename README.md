BookReader (plugin for Omeka)
=============================


This plugin adds [Internet Archive BookReader] into [Omeka].
The IA BookReader is used to view books from the Internet Archive online and can
also be used to view other books.
BookReader plugin for Omeka allows you to create online flip book from image
files constituting an item.

See demo of the [embedded version] of [Mines ParisTech] or in [fullscreen mode]
with highlighted results of a search in the OCR text ([Université Rennes 2]).


Installation
------------

- Upload the BookReader plugin folder into your plugins folder on the server;
- Activate it from the admin → Settings → Plugins page
- Click the Configure link to add the following
    - URL for custom CSS
    - Favicon URL for viewer (reader) pages
    - Name of to custom class (default is `BookReader_Creator_Default`)
    - Sorting mode for the viewer (omeka default order or original filename order)
    - Number of pages in Embed mode (1 or 2)
    - Embed all functions (0 for none or 1 for all)
    - The width of the inline frame (Embedded Simple Viewer)
    - The height of the inline frame (Embedded Simple Viewer)

The viewer is always available at `http://www.example.com/viewer/show/{item id}`.
Furthermore, it is automatically embedded in items/show page. This can be
disabled in the config of the plugin.

To embed the BookReader with more control, three mechanisms are provided. So,
according to your needs, you may add this code in the `items/show.php` file of
your theme or anywhere else, as long as the item is defined (as variable or as
current record 'item').

* Hook
    - With all options:

```
    <?php
    echo get_specific_plugin_hook_output('BookReader', 'public_items_show', array(
        'direct' => true,
        'view' => $this,
        'item' => $item,
        'page' => '0',
        'embed_functions' => false,
        'mode_page' => 1,
    ));
    ?>
```

* Helper
    - With no option:

```
     echo $this->getBookReader();
```

* Shortcode
    - In a field that can be shortcoded: `[bookreader]`.
    - Default in theme: `<?php echo $this->shortcodes('[bookreader]'); ?>`
    - With all options:

```
    <?php
        echo $this->shortcodes('[bookreader item=1 page=0 embed_functions=0 mode_page=1]');
    ?>
```

All options are optional. If one is not defined, the parameters set in the
config page will be used.
The image number starts from '0' with default functions.


Customing
---------

There are several ways to store data about items in Omeka, so the BookReader can
be customized via a class that extends `BookReader_Creator`.

BookReader uses several arrays to get images and infos about them. Take a
document of twelve pages as an example. In Javascript, we have these arrays:
- br.leafMap : mapping of pages, as [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11]
- br.pageNums : number of pages, as [,, "i", "ii", "iii", "iv", 1, 2, 3, 4, 5,]
- br.pageLabels : label of pages, as ["Cover", "Blank",,,,, "Page 1 (unnumbered)",,,,, "Back cover"]
- br.pageWidths : width of each image, as [500, 500, 500, 500, 500, 500, 500, 500, 500, 500, 500, 500]
- br.pageHeights : height of each image, as [800, 800, 800, 800, 800, 800, 800, 800, 800, 800, 800, 800]

With the default files of BookReader, all images of an item are displayed, so
the leafMap indexes are always a simple list of numbers like above (starting
from 0 when the first page is a right page, else from 1). Page numbers and/or
page labels can be empty, so in that case the index is used. When the user
leafs through the document, the viewer sends a request with index + 1 as image
parameter. So the controller can send the index - 1 to select image in the
ordered list of images files.

Some functions of php are used to make relation with this index and to provide
images. They are used in the beginning and via Ajax. During creation of the
viewer, php should provide mapping, numbers and labels via BookReader custom
functions (`getPageIndexes()`, `getPageNumbers()` and `getPageLabels()`). These
functions use one main method, `getLeaves()`, that provides the ordered
list of all images that should be displayed as leaves (saved by default as a
static variable in php). This list is used too to get the selected image when
needed via the index. The `getNonLeaves()` method is used to get links to other
files to share. So,The list of leaves files is a simple array as [File 1, File 2, File 3, File 4...].

In case of a non-digitalized blank or forgotten page, in order to keep the
left/right succession of leafs, the mapping and the page numbers and labels
should be the same, and the list of leaves files should be [File 1, null, File 3, File 4...].
The `transparent.png` image will be displayed if an image is missing, with the
width and the height of the first page.

In case of multiple files for the same page, for example with a pop-up or with
and without a tracing paper, the mapping can be: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 9, 12, 11].
Other arrays should be set in accordance: number of pages as [,, "i", "ii", "iii", "iv", 1, 2, 3, 4, 5, 4, 5,],
labels as ["Cover", "Blank",,,,, "Page 1 (unnumbered)",,,, "Page 5 with tracing paper", "Back cover"]
and files as [File 1, null, File 3, File 4..., File 10, File 11a, File 10, File 11b, File 12].
Any other arrangements can be used.

To avoid to calculate these data each time an item is displayed, it's
recommanded to save them either in a xml file, or in the database. It's
specially important for widths and heights data, as all of them should be got
before first display.
A batch can be launched in the admin/items/show page if needed. The function
`saveData()` should be customized to your needs. Of course, other functions
should check if these data are available and use them. This function can be used
too the first time a viewer is displayed for an item.


Using the BookReader Plugin
---------------------------

- Create an item
- Add some image files to this item
- Add eventually PDF file to this item (PDF file should be consist of the same
images uploaded in previous step)


Optional plugins
----------------

The extract ocr and pdfToc plugins are highly recommended.

- [Extract ocr] allows fulltext searching inside a flip book. To enable it in
BookReader, you need to overwrite Bookreader/libraries/BookReader/Creator/Default.php
using Bookreader/libraries/BookReader/Creator/ExtractOCR.php or to set the path
in configuration panel of the extension.
- [PDF Toc] retrieves table of contents from pdf file associated to an item.


Troubleshooting
---------------

See online issues on the [plugin issues] page on GitHub.


License
-------

This plugin is published under [GNU/GPL].

This program is free software; you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation; either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT
ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
details.

You should have received a copy of the GNU General Public License along with
this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.


Contact
-------

See developer documentation on [Internet Archive BookReader] and [source of IA BookReader]
on GitHub.

Current maintainers:
* [Julien Sicot]
* [Daniel Berthereau]

First version has been built by Julien Sicot for [Université Rennes 2].
The upgrade for Omeka 2.0 has been built for [Mines ParisTech].


Copyright
---------

The source code of [Internet Archive BookReader] is licensed under AGPL v3, as
described in the LICENSE file.

* Copyright Internet Archive, 2008-2009

BookReader Omeka plugin:

* Copyright Julien Sicot, 2011-2013
* Copyright Daniel Berthereau, 2013-2015 (upgrade for Omeka 2.0)


[Omeka]: https://omeka.org
[Internet Archive BookReader]: http://openlibrary.org/dev/docs/bookreader
[source of IA BookReader]: https://github.com/openlibrary/bookreader
[embedded version]: https://patrimoine.mines-paristech.fr/document/Brochant_MS_39
[fullscreen mode]: http://bibnum.univ-rennes2.fr/viewer/show/566#page/5/mode/1up
[Extract ocr]: https://github.com/symac/Plugin-Extractocr
[PDF Toc]: https://github.com/symac/Plugin-PdfToc
[plugin issues]: https://github.com/jsicot/BookReader/issues
[GNU/GPL]: https://www.gnu.org/licenses/gpl-3.0.html
[Daniel Berthereau]: https://github.com/Daniel-KM
[Julien Sicot]: https://github.com/jsicot
[Université Rennes 2]: http://bibnum.univ-rennes2.fr
[Mines ParisTech]: http://bib.mines-paristech.fr
