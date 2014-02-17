<?php
    $item = get_record_by_id('item', BookReader::currentItemId());
    set_current_record('item', $item);

    $title = metadata('item', array('Dublin Core', 'Title'));
    if ($creator = metadata('item', array('Dublin Core', 'Creator'))) {
        $title .= ' - ' . $creator;
    }
    $title = BookReader::htmlCharacter($title);
    $coverFile = BookReader::getCoverFile($item);

    list($pageIndexes, $pageNumbers, $pageLabels, $imgWidths, $imgHeights) = BookReader::imagesData($item);

    $ui = BookReader::currentItemUI();

    $server = preg_replace('#^https?://#', '', WEB_ROOT);
    $serverFullText = $server . '/book-reader/index/fulltext';
    $sharedUrl = WEB_PLUGIN . '/BookReader/views/shared';
    $imgDir = WEB_PLUGIN . '/BookReader/views/shared/images/';
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html <?php echo ($ui == 'embed') ? 'id="embedded" ' : ''; ?>lang="fr">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, maximum-scale=1.0" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="black" />
    <base target="_parent" />
    <link rel="apple-touch-icon" href="<?php echo $coverFile->getWebPath('thumbnail'); ?>" />
    <link rel="shortcut icon" href="<?php echo get_option('bookreader_favicon_url'); ?>" type="image/x-icon" />
    <title><?php echo $title; ?></title>
    <!-- Stylesheets -->
    <link rel="stylesheet" href="<?php echo $sharedUrl . '/css/BookReader.css'; ?>" />
    <?php if ($custom_css = get_option('bookreader_custom_css')): ?>
    <link rel="stylesheet" href="<?php echo $custom_css; ?>" />
    <?php endif; ?>
    <!-- JavaScripts -->
    <script type="text/javascript" src="<?php echo $sharedUrl . '/javascripts/jquery-1.4.2.min.js'; ?>" charset="utf-8"></script>
    <script type="text/javascript" src="<?php echo $sharedUrl . '/javascripts/jquery-ui-1.8.5.custom.min.js'; ?>" charset="utf-8"></script>
    <script type="text/javascript" src="<?php echo $sharedUrl . '/javascripts/dragscrollable.js'; ?>" charset="utf-8"></script>
    <script type="text/javascript" src="<?php echo $sharedUrl . '/javascripts/jquery.colorbox-min.js'; ?>" charset="utf-8"></script>
    <script type="text/javascript" src="<?php echo $sharedUrl . '/javascripts/jquery.ui.ipad.js'; ?>" charset="utf-8"></script>
    <script type="text/javascript" src="<?php echo $sharedUrl . '/javascripts/jquery.bt.min.js'; ?>" charset="utf-8"></script>
    <script type="text/javascript" src="<?php echo $sharedUrl . '/javascripts/BookReader.js'; ?>" charset="utf-8"></script>
    <script type="text/javascript" src="<?php echo $sharedUrl . '/javascripts/ToCmenu.js'; ?>" charset="utf-8"></script>
</head>
<body>
    <div></div>
    <div id="BookReader">
        <br />
        <noscript>
            <p>
                The BookReader requires JavaScript to be enabled. Please check that your browser supports JavaScript and that it is enabled in the browser settings.
            </p>
        </noscript>
    </div>

    <script type="text/javascript">
    //
    // This file shows the minimum you need to provide to BookReader to display a book
    //
    // Copyright (c) 2008-2009 Internet Archive. Software license AGPL version 3.

    // Create the BookReader object
    br = new BookReader();

    br.leafMap = [<?php echo implode(',', $pageIndexes); ?>];
    br.pageNums = [<?php echo implode(',', $pageNumbers); ?>];
    br.pageLabels = [<?php echo implode(',', $pageLabels); ?>];
    br.pageWidths = [<?php echo implode(',', $imgWidths); ?>];
    br.pageHeights = [<?php echo implode(',', $imgHeights); ?>];
    br.server = "<?php echo $serverFullText; ?>";
    br.bookPath = "<?php echo WEB_ROOT; ?>";
    br.bookId = <?php echo $item->id; ?>;
    br.subPrefix = <?php echo $item->id; ?>;
    br.titleLeaf = <?php echo BookReader::getTitleLeaf($item); ?>;

    br.numLeafs = br.leafMap.length;

    // If there is no width, it will be the width of the verso of the current
    // leaf, else width of first or last page.
    br.getPageWidth = function(index) {
        var width = this.pageWidths[index];
        if (width == undefined) {
            if ('rl' != this.pageProgression) {
                if (this.getPageSide(index) == 'R') {
                    width = this.pageWidths[index + 1];
                } else {
                    width = this.pageWidths[index - 1];
                }
            } else {
                if (this.getPageSide(index) == 'L') {
                    width = this.pageWidths[index + 1];
                } else {
                    width = this.pageWidths[index - 1];
                }
            }
            if (width == undefined) {
                if (Math.min(Math.max(index, 0), this.numLeafs - 1)) {
                    width = this.pageWidths[0];
                } else {
                    width = this.pageWidths[this.numLeafs - 1];
                }
            }
        }
        return width;
    }

    br.getPageHeight = function(index) {
        var height = this.pageHeights[index];
        if (height == undefined) {
            if ('rl' != this.pageProgression) {
                if (this.getPageSide(index) == 'R') {
                    height = this.pageHeights[index + 1];
                } else {
                    height = this.pageHeights[index - 1];
                }
            }
            else {
                if (this.getPageSide(index) == 'L') {
                    height = this.pageHeights[index + 1];
                } else {
                    height = this.pageHeights[index - 1];
                }
            }
            if (height == undefined) {
                if (Math.min(Math.max(index, 0), this.numLeafs - 1)) {
                    height = this.pageHeights[0];
                } else {
                    height = this.pageHeights[this.numLeafs - 1];
                }
            }
        }
        return height;
    }

    // TODO Bug if page num starts with a "n" (rarely used as page number).
    // This is used only to build the url to a specific page.
    br.getPageNum = function(index) {
        var pageNum = this.pageNums[index];
        if (pageNum) {
            return pageNum;
        } else {
            var pageLabel = this.pageLabels[index];
            if (pageLabel) {
                return pageLabel;
            } else {
                // Accessible index starts at 0 so we add 1 to make human.
                index++;
                return 'n' + index;
            }
        }
    }

    br.getPageLabel = function(index) {
        var pageLabel = this.pageLabels[index];
        if (pageLabel) {
            return pageLabel;
        } else {
            var pageNum = this.pageNums[index];
            if (pageNum) {
                return '<?php echo html_escape(__('Page')); ?> ' + pageNum;
            } else {
                // Accessible index starts at 0 so we add 1 to make human.
                index++;
                return 'n' + index;
            }
        }
    }

    // This is used only to get the page num from the url of a specific page.
    // This is needed because the hash can be the number or the label.
    // Practically, it does a simple check of the page hash.
    br.getPageNumFromHash = function(pageHash) {
        // Check if this is a page number.
        for (var index = 0; index < this.pageNums.length; index++) {
            if (this.pageNums[index] == pageHash) {
                return pageHash;
            }
        }
        // Check if this is a page label.
        for (var index = 0; index < this.pageLabels.length; index++) {
            if (this.pageLabels[index] == pageHash) {
                return pageHash;
            }
        }
        // Check if this is an index.
        if (pageHash.slice(0,1) == 'n') {
            var pageIndex = pageHash.slice(1, pageHash.length);
            // Index starts at 0 so we make it internal.
            pageIndex = parseInt(pageIndex) - 1;
            if (this.getPageNum(pageIndex) == pageHash) {
                return pageHash;
            }
        }
        return undefined;
    }

    // Single images in the Internet Archive scandata.xml metadata are (somewhat incorrectly)
    // given a "leaf" number.  Some of these images from the scanning process should not
    // be displayed in the BookReader (for example colour calibration cards).  Since some
    // of the scanned images will not be displayed in the BookReader (those marked with
    // addToAccessFormats false in the scandata.xml) leaf numbers and BookReader page
    // indexes are generally not the same.  This function returns the BookReader page
    // index given a scanned leaf number.
    //
    // This function is used, for example, to map between search results (that use the
    // leaf numbers) and the displayed pages in the BookReader.
    br.leafNumToIndex = function(leafNum) {
        for (var index = 0; index < this.numLeafs; index++) {
            if (this.leafMap[index] == leafNum) {
                return index;
            }
        }

        return null;
    }

    // TODO To be removed, because it's not used currently.
    // Remove the page number assertions for all but the highest index page with
    // a given assertion.  Ensures there is only a single page "{pagenum}"
    // e.g. the last page asserted as page 5 retains that assertion.
    br.uniquifyPageNums = function() {
        var seen = {};

        for (var i = br.pageNums.length - 1; i--; i >= 0) {
            var pageNum = br.pageNums[i];
            if ( !seen[pageNum] ) {
                seen[pageNum] = true;
            } else {
                br.pageNums[i] = null;
            }
        }
    }

    // TODO To be removed, because it's not used currently.
    br.cleanupMetadata = function() {
        br.uniquifyPageNums();
    }

    // We load the images from archive.org -- you can modify this function to retrieve images
    // using a different URL structure
    br.getPageURI = function(index, reduce, rotate) {
        // reduce and rotate are ignored in this simple implementation, but we
        // could e.g. look at reduce and load images from a different directory
        // or pass the information to an image server
        var leafStr = '0000';
        var imgStr = (index+1).toString();
        var re = new RegExp("0{"+imgStr.length+"}$");
        var url = '<?php echo WEB_ROOT; ?>/book-reader/index/image-proxy?image='+leafStr.replace(re, imgStr)+'&id=<?php echo $item->id; ?>&scale='+reduce;
        return url;
    }

    // Return which side, left or right, that a given page should be displayed on.
    br.getPageSide = function(index) {
        var leafNum = this.leafMap[index];
        if (0 == (leafNum & 0x1)) {
            // Even leaf number is a right page (zero-based arrays).
            return 'R';
        } else {
            // Odd leaf number is a left page.
            return 'L';
        }
    }

    // This function returns the left and right indices for the user-visible
    // spread that contains the given index. The return values may be
    // null if there is no facing page or the index is invalid.
    br.getSpreadIndices = function(pindex) {
        var spreadIndices = [null, null];
        if (pindex < 0) pindex = 0;
        if (pindex >= this.numLeafs) pindex = this.numLeafs - 1;
        if ('rl' != this.pageProgression) {
            // Left to right
            if (this.getPageSide(pindex) == 'L') {
                spreadIndices[0] = pindex;
                if (pindex + 1 >= this.numLeafs) {
                    spreadIndices[1] = null;
                } else {
                    spreadIndices[1] = pindex + 1;
                }
            } else {
                // Given index was right hand side.
                if (pindex - 1 < 0) {
                    spreadIndices[0] = null;
                } else {
                    spreadIndices[0] = pindex - 1;
                }
                spreadIndices[1] = pindex;
            }
        } else {
            // Right to Left
            if (this.getPageSide(pindex) == 'R') {
                if (pindex + 1 >= this.numLeafs) {
                    spreadIndices[0] = null;
                } else {
                    spreadIndices[0] = pindex + 1;
                }
                spreadIndices[1] = pindex;
            } else {
                // Given index was left hand side.
                spreadIndices[0] = pindex;
                if (pindex - 1 < 0) {
                    spreadIndices[1] = null;
                } else {
                    spreadIndices[1] = pindex - 1;
                }
            }
        }
        return spreadIndices;
    }

    br.ui = '<?php echo BookReader::currentItemUI(); ?>';
    // Book title and the URL used for the book title link
    br.bookTitle= '<?php echo $title; ?>';
    br.bookUrl  = "<?php echo record_url($item); ?>";
    br.logoURL = "<?php echo WEB_ROOT; ?>";
    br.siteName = "<?php echo option('site_title');?>";
    // Override the path used to find UI images
    br.imagesBaseURL = '<?php echo $imgDir; ?>';

    br.buildInfoDiv = function(jInfoDiv) {
        // $$$ it might make more sense to have a URL on openlibrary.org that returns this info

        var escapedTitle = BookReader.util.escapeHTML(this.bookTitle);
        var domainRe = /(\w+\.(com|org))/;
        var domainMatch = domainRe.exec(this.bookUrl);
        var domain = this.bookUrl;
        if (domainMatch) {
            domain = domainMatch[1];
        }

        // $$$ cover looks weird before it loads
        jInfoDiv.find('.BRfloatCover').append([
            '<div style="height: 140px; min-width: 80px; padding: 0; margin: 0;"><?php echo link_to_item(BookReader::itemCover()); ?></div>'
        ].join(''));

        jInfoDiv.find('.BRfloatMeta').append([
            '<h3><?php echo html_escape(__('Other Formats')); ?></h3>',
            '<ul class="links">',
                '<?php echo BookReader::linksToNonImages($item); ?>',
            '</ul>',
            '<p class="moreInfo">',
                '<a href="'+ this.bookUrl + '"><?php echo html_escape(__('More information')); ?></a>',
            '</p>'
        ].join('\n'));

        jInfoDiv.find('.BRfloatFoot').append([
            '<span>|</span>',
            '<a href="mailto:<?php echo option('administrator_email');?>" class="problem"><?php echo html_escape(__('Report a problem')); ?></a>'
        ].join('\n'));

        if (domain == 'archive.org') {
            jInfoDiv.find('.BRfloatMeta p.moreInfo span').css(
                {'background': 'url(http://www.archive.org/favicon.ico) no-repeat', 'width': 22, 'height': 18 }
            );
        }

        jInfoDiv.find('.BRfloatTitle a').attr({'href': this.bookUrl, 'alt': this.bookTitle}).text(this.bookTitle);
        var bookPath = (window.location + '').replace('#','%23');
        jInfoDiv.find('a.problem').attr('href','mailto:<?php echo option('administrator_email');?>?subject=' + bookPath);
    }

    // getEmbedURL
    //________
    // Returns a URL for an embedded version of the current book
    br.getEmbedURL = function(viewParams) {
        // We could generate a URL hash fragment here but for now we just leave at defaults
        //var url = 'http://' + window.location.host + '/stream/'+this.bookId;
        var bookId = <?php echo $item->id; ?>;
        var url = '<?php echo WEB_ROOT; ?>/viewer/show/<?php echo $item->id; ?>';
        if (this.subPrefix != this.bookId) { // Only include if needed
            url += '/' + this.subPrefix;
        }
        url += '?ui=embed';
        if (typeof(viewParams) != 'undefined') {
            url += '#' + this.fragmentFromParams(viewParams);
        }
        return url;
    }

    // getEmbedCode
    //________
    // Returns the embed code HTML fragment suitable for copy and paste
    br.getEmbedCode = function(frameWidth, frameHeight, viewParams) {
        return "<iframe src='" + this.getEmbedURL(viewParams) + "' width='" + frameWidth + "' height='" + frameHeight + "' frameborder='0' ></iframe>";
    }

    br.initUIStrings = function() {
        var titles = {
            '.logo': '<?php echo __('Go to %s', option('site_title')); ?>', // $$$ update after getting OL record
            '.zoom_in': '<?php echo __('Zoom in'); ?>',
            '.zoom_out': '<?php echo __('Zoom out'); ?>',
            '.onepg': '<?php echo __('One-page view'); ?>',
            '.twopg': '<?php echo __('Two-page view'); ?>',
            '.thumb': '<?php echo __('Thumbnail view'); ?>',
            '.print': '<?php echo __('Print this page'); ?>',
            '.embed': '<?php echo __('Embed BookReader'); ?>',
            '.link': '<?php echo __('Link to this document and page'); ?>',
            '.bookmark': '<?php echo __('Bookmark this page'); ?>',
            '.read': '<?php echo __('Read this document aloud'); ?>',
            '.share': '<?php echo __('Share this document'); ?>',
            '.info': '<?php echo __('About this document'); ?>',
            '.full': '<?php echo __('Show fullscreen'); ?>',
            '.book_left': '<?php echo __('Flip left'); ?>',
            '.book_right': '<?php echo __('Flip right'); ?>',
            '.book_up': '<?php echo __('Page up'); ?>',
            '.book_down': '<?php echo __('Page down'); ?>',
            '.play': '<?php echo __('Play'); ?>',
            '.pause': '<?php echo __('Pause'); ?>',
            '.BRdn': '<?php echo __('Show/hide nav bar'); ?>', // Would have to keep updating on state change to have just "Hide nav bar"
            '.BRup': '<?php echo __('Show/hide nav bar'); ?>',
            '.book_top': '<?php echo __('First page'); ?>',
            '.book_bottom': '<?php echo __('Last page'); ?>'
        };
        if ('rl' == this.pageProgression) {
            titles['.book_leftmost'] = '<?php echo __('Last page'); ?>';
            titles['.book_rightmost'] = '<?php echo __('First page'); ?>';
        } else { // LTR
            titles['.book_leftmost'] = '<?php echo __('First page'); ?>';
            titles['.book_rightmost'] = '<?php echo __('Last page'); ?>';
        }

        for (var icon in titles) {
            if (titles.hasOwnProperty(icon)) {
                $('#BookReader').find(icon).attr('title', titles[icon]);
            }
        }
    }

    br.i18n = function(msg) {
        var msgs = {
            'View':<?php echo json_encode(__('View')); ?>,
            'Search results will appear below...':<?php echo json_encode(__('Search results will appear below...')); ?>,
            'No matches were found.':<?php echo json_encode(__('No matches were found.')); ?>,
            "This book hasn't been indexed for searching yet. We've just started indexing it, so search should be available soon. Please try again later. Thanks!":<?php echo json_encode(__("This book hasn't been indexed for searching yet. We've just started indexing it, so search should be available soon. Please try again later. Thanks!")); ?>,
            'Embed Bookreader':<?php echo json_encode(__('Embed Bookreader')); ?>,
            'The bookreader uses iframes for embedding. It will not work on web hosts that block iframes. The embed feature has been tested on blogspot.com blogs as well as self-hosted Wordpress blogs. This feature will NOT work on wordpress.com blogs.':<?php echo json_encode(__('The bookreader uses iframes for embedding. It will not work on web hosts that block iframes. The embed feature has been tested on blogspot.com blogs as well as self-hosted Wordpress blogs. This feature will NOT work on wordpress.com blogs.')); ?>,
            'Close':<?php echo json_encode(__('Close')); ?>,
            'Add a bookmark':<?php echo json_encode(__('Add a bookmark')); ?>,
            'You can add a bookmark to any page in any book. If you elect to make your bookmark public, other readers will be able to see it.':<?php echo json_encode(__('You can add a bookmark to any page in any book. If you elect to make your bookmark public, other readers will be able to see it.')); ?>,
            'You must be logged in to your <a href="">Open Library account</a> to add bookmarks.':<?php echo json_encode(__('You must be logged in to your <a href="">Open Library account</a> to add bookmarks.')); ?>,
            'Make this bookmark public.':<?php echo json_encode(__('Make this bookmark public.')); ?>,
            'Keep this bookmark private.':<?php echo json_encode(__('Keep this bookmark private.')); ?>,
            'Add a bookmark':<?php echo json_encode(__('Add a bookmark')); ?>,
            'Search result':<?php echo json_encode(__('Search result')); ?>,
            'Search inside':<?php echo json_encode(__('Search inside')); ?>,
            'GO':<?php echo json_encode(__('GO')); ?>,
            "Go to this book's page on Open Library":<?php echo json_encode(__("Go to this book's page on Open Library")); ?>,
            'Loading audio...':<?php echo json_encode(__('Loading audio...')); ?>,
            'Could not load soundManager2, possibly due to FlashBlock. Audio playback is disabled':<?php echo json_encode(__('Could not load soundManager2, possibly due to FlashBlock. Audio playback is disabled')); ?>,
            'About this book':<?php echo json_encode(__('About this book')); ?>,
            'About the BookReader':<?php echo json_encode(__('About the BookReader')); ?>,
            'Copy and paste one of these options to share this book elsewhere.':<?php echo json_encode(__('Copy and paste one of these options to share this book elsewhere.')); ?>,
            'Link to this page view:':<?php echo json_encode(__('Link to this page view:')); ?>,
            'Link to the book:':<?php echo json_encode(__('Link to the book:')); ?>,
            'Embed a mini Book Reader:':<?php echo json_encode(__('Embed a mini Book Reader:')); ?>,
            '1 page':<?php echo json_encode(__('1 page')); ?>,
            '2 pages':<?php echo json_encode(__('2 pages')); ?>,
            'Open to this page?':<?php echo json_encode(__('Open to this page?')); ?>,
            'NOTE:':<?php echo json_encode(__('NOTE:')); ?>,
            "We've tested EMBED on blogspot.com blogs as well as self-hosted Wordpress blogs. This feature will NOT work on wordpress.com blogs.":<?php echo json_encode(__("We've tested EMBED on blogspot.com blogs as well as self-hosted Wordpress blogs. This feature will NOT work on wordpress.com blogs.")); ?>,
            'Finished':<?php echo json_encode(__('Finished')); ?>
        };
        return msgs[msg];
    }

    // Let's go!
    br.init();
    $('#BRtoolbar').find('.read').hide();
    $('#BRreturn').html($('#BRreturn').text());
<?php
        // Si jamais la recherche n'est pas disponible (pas de fichier XML), on
        // va masquer les éléments permettant de la lancer (SMA 201210)
        if (!BookReader::hasDataForSearch()): ?>
    $('#textSrch').hide();
    $('#btnSrch').hide();
        <?php endif; ?>
    </script>

     <?php
     // Table of Contents if exist, plugin PdfToc required.
     echo fire_plugin_hook('toc_for_bookreader', array(
         'view' => $this,
         'item' => $item,
     ));
    ?>

    </body>
</html>
