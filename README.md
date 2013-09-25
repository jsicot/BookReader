BookReader (plugin for Omeka)
=============================


Summary
-----------

This plugin adds [Internet Archive BookReader] into [Omeka].
The IA BookReader is used to view books from the Internet Archive online and can
also be used to view other books.
BookReader plugin for Omeka allows you to create online flip book from image
files constituting an item.

See demo of the [embedded version] or use in [fullscreen mode].


Installation
------------

- Upload the BookReader plugin folder into your plugins folder on the server;
- Activate it from the admin → Settings → Plugins page
- Click the Configure link to add the following
    - Logo URL for toolbar viewer (reader)
    - Favicon URL for viewer (reader) pages
    - Hex color for toolbar viewer (reader)
    - Enable embed viewer in item/show page
    - Sorting mode for the viewer (omeka default order or original filename order)
    - Number of pages in Embed mode (1 or 2)
    - Embed all functions (0 for none or 1 for all)
    - The width of the inline frame (Embedded Simple Viewer)
    - The height of the inline frame (Embedded Simple Viewer)

The viewer is always available at `http://www.example.com/viewer/show/{item id}`.
If you want to embed it, add this code in the item/show.php file of your theme:

```
    <?php
    fire_plugin_hook('book_reader_item_show', array(
        'view' => $this,
        'item' => $item,
        'page' => '0',
        'embed_functions' => false,
    ));
    ?>
```

If an option is not set, the parameters in the config page will be used.
Image number starts from '0' with default functions.


Using the BookReader Plugin
---------------------------

- Create an item
- Add some image files to this item
- Add eventually PDF file to this item (PDF file should be consist of the same
images uploaded in previous step)


Optional plugins
----------------

The extract ocr and pdfToc plugins are highly recommended.

- [Extract ocr] allows fulltext searching inside a flip book
- [PDF Toc] retrieves table of contents from pdf file associated to an item


Troubleshooting
---------------

See online [BookReader issues].


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
* Julien Sicot (see [jsicot]) (original plugin)
* Daniel Berthereau (see [Daniel-KM]) (upgrade for Omeka 2.0)

First version has been built by Julien Sicot for [Université Rennes 2].
The upgrade for Omeka 2.0 has been built for [Mines ParisTech].


Copyright
---------

The source code of [Internet Archive BookReader] is licensed under AGPL v3, as
described in the LICENSE file.

* Copyright Internet Archive, 2008-2009

BookReader Omeka plugin:

* Copyright Julien Sicot, 2011-2012
* Copyright Daniel Berthereau, 2013 (upgrade for Omeka 2.0)


[Omeka]: https://omeka.org "Omeka.org"
[Internet Archive BookReader]: http://openlibrary.org/dev/docs/bookreader
[source of IA BookReader]: http://github.com/openlibrary/bookreader
[embedded version]: http://bibnum.univ-rennes2.fr/items/show/566
[fullscreen mode]: http://bibnum.univ-rennes2.fr/viewer/show/566
[Extract ocr]: https://github.com/symac/Plugin-Extractocr
[PDF Toc]: https://github.com/symac/Plugin-PdfToc
[BookReader issues]: https://github.com/jsicot/BookReader/Issues "GitHub BookReader"
[GNU/GPL]: https://www.gnu.org/licenses/gpl-3.0.html "GNU/GPL v3"
[Daniel-KM]: https://github.com/Daniel-KM "Daniel Berthereau"
[jsicot]: https://github.com/jsicot "Julien Sicot"
[Université Rennes 2]: http://bibnum.univ-rennes2.fr
[Mines ParisTech]: http://bib.mines-paristech.fr "Mines ParisTech / ENSMP"
