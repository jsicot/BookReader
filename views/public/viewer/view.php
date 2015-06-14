<?php
    if (empty($tableUrl)) {
        echo '<html><head></head><body>';
        echo __('This item has no viewable files.');
        echo '</body></html>';
        return;
    }

    $title = metadata($item, array('Dublin Core', 'Title'));
    if ($creator = metadata($item, array('Dublin Core', 'Creator'))) {
        $title .= ' - ' . $creator;
    }
    $title = BookReader::htmlCharacter($title);

    $coverFile = $bookreader->getCoverFile();

    list($pageIndexes, $pageNumbers, $pageLabels, $imgWidths, $imgHeights) = $bookreader->imagesData();

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
    <?php if ($coverFile): ?>
    <link rel="apple-touch-icon" href="<?php echo $coverFile->getWebPath('thumbnail'); ?>" />
    <?php endif; ?>
    <link rel="shortcut icon" href="<?php echo get_option('bookreader_favicon_url'); ?>" type="image/x-icon" />
    <title><?php echo $title; ?></title>
    <!-- Stylesheets -->
    <link rel="stylesheet" href="<?php echo $sharedUrl . '/css/BookReader.css'; ?>" />
    <?php if ($custom_css = get_option('bookreader_custom_css')): ?>
    <link rel="stylesheet" href="<?php echo url($custom_css); ?>" />
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
// This file shows the minimum you need to provide to BookReader to display a book
//
// Copyright (c) 2008-2009 Internet Archive. Software license AGPL version 3.

// Create the BookReader object via spreadsheet data instead of Omeka database.

function spreadsheetLoaded(json) {
    imagesArray = [];

    json = json.substring(json.indexOf("(") + 1);
    json = json.substring(0, json.lastIndexOf(")"));
    json = JSON.parse(json);

    parts = json["feed"]["entry"];

    for (n in parts) {
        if (parts[n]["gsx$image"] != undefined) {
            var image =parts[n]["gsx$image"]["$t"];
            var height = null;
            var width = null;
            var num = null;
            var label = null;
            if (parts[n]["gsx$height"] != undefined) {
                height = parts[n]["gsx$height"]["$t"];
            }
            if (parts[n]["gsx$width"] != undefined) {
                width = parts[n]["gsx$width"]["$t"];
            }
            if (parts[n]["gsx$num"] != undefined) {
                num = parts[n]["gsx$num"]["$t"];
            }
            if (parts[n]["gsx$label"] != undefined) {
                label = parts[n]["gsx$label"]["$t"];
            }
            imagesArray.push({
                "image": image,
                "height": height,
                "width": width,
                "num": num,
                "label": label
            });
        }
    }
    var externalArray = [];
    var htmlstring = "";

    // Read-aloud and search need backend compenents and are not supported in
    // the demo.
    br = new BookReader();

    br.imagesBaseURL = <?php echo json_encode($imgDir); ?>;
    br.leafMap = [];
    br.server = "<?php echo $serverFullText; ?>";
    br.bookPath = "<?php echo WEB_ROOT; ?>";
    br.bookId = <?php echo $item->id; ?>;
    br.titleLeaf = <?php echo $bookreader->getTitleLeaf(); ?>;
    <?php // Sub-prefix is the sub-document in BookReader.
    // Original param is '?doc', but it has been changed to '?part'.
    ?>
    br.subPrefix = <?php echo empty($part) ? 0 : $part; ?>;

    br.numLeafs = imagesArray.length;

    // Return the width of a given page, else we assume all images are 800
    // pixels wide.
    br.getPageWidth = function(index) {
        if ((imagesArray[index]) && (imagesArray[index].width != undefined)) {
            return parseInt(imagesArray[index].width);
        } else {
            return 800;
        }
    }

    // Return the height of a given page, else we assume all images are 1200
    // pixels high.
    br.getPageHeight = function(index) {
        if ((imagesArray[index]) && (imagesArray[index].height != undefined)) {
            return parseInt(imagesArray[index].height)
        } else {
            return 1200;
        }
    }

    // For a given "accessible page index" return the page number in the book.
    //
    // For example, index 5 might correspond to "Page 1" if there is front
    // matter such as a title page and table of contents.
    //
    // TODO Bug if page num starts with a "n" (rarely used as page number).
    // This is used only to build the url to a specific page.
    br.getPageNum = function(index) {
        var pageNum = imagesArray[index].num;
        if (pageNum && pageNum != undefined) {
            return pageNum;
        }
        var pageLabel = imagesArray[index].label;
        if (pageLabel) {
            return pageLabel;
        }
        // Accessible index starts at 0 so we add 1 to make human.
        index++;
        return 'n' + index;
    }

    br.getPageLabel = function(index) {
        var pageLabel = imagesArray[index].label;
        if (pageLabel) {
            return pageLabel;
        }
        var pageNum = imagesArray[index].num;
        if (pageNum) {
            return <?php echo json_encode(__('Page')); ?> + ' ' + pageNum;
        }
        // Accessible index starts at 0 so we add 1 to make human.
        index++;
        return 'n' + index;
    }

    // This is used only to get the page num from the url of a specific page.
    // This is needed because the hash can be the number or the label.
    // Practically, it does a simple check of the page hash.
    br.getPageNumFromHash = function(pageHash) {
        // Check if this is a page number.
        for (var index = 0; index < br.numLeafs; index++) {
            if (imagesArray[index].num == pageHash) {
                return pageHash;
            }
        }
        // Check if this is a page label.
        for (var index = 0; index < br.numLeafs; index++) {
            if (imagesArray[index].label == pageHash) {
                return pageHash;
            }
        }
        // Check if this is an index.
        if (pageHash.slice(0, 1) == 'n') {
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

        for (var i = br.numLeafs - 1; i--; i >= 0) {
            var pageNum = imagesArray[i].num;
            if ( !seen[pageNum] ) {
                seen[pageNum] = true;
            } else {
                imagesArray[i].num = null;
            }
        }
    }

    // TODO To be removed, because it's not used currently.
    br.cleanupMetadata = function() {
        br.uniquifyPageNums();
    }

    // We load the images from archive.org.
    // You can modify this function to retrieve images using a different URL
    // structure.
    br.getPageURI = function(index, reduce, rotate) {
        // reduce and rotate are ignored in this simple implementation, but we
        // could e.g. look at reduce and load images from a different directory
        // or pass the information to an image server
        // var leafStr = '0000';
        // var imgStr = (index+1).toString();
        // var re = new RegExp("0{"+imgStr.length+"}$");
        // var url =
        // 'http://www.archive.org/download/BookReader/img/page'+leafStr.replace(re,
        // imgStr) + '.jpg';

        url = imagesArray[index].image;

        return url;
    }

    // Return which side, left or right, that a given page should be displayed
    // on.
    br.getPageSide = function(index) {
        if (0 == (index & 0x1)) {
            return 'R';
        } else {
            return 'L';
        }
    }

    // This function returns the left and right indices for the user-visible
    // spread that contains the given index. The return values may be
    // null if there is no facing page or the index is invalid.
    br.getSpreadIndices = function(pindex) {
        var spreadIndices = [ null, null ];
        if ('rl' == this.pageProgression) {
            // Right to Left
            if (this.getPageSide(pindex) == 'R') {
                spreadIndices[1] = pindex;
                spreadIndices[0] = pindex + 1;
            } else {
                // Given index was LHS
                spreadIndices[0] = pindex;
                spreadIndices[1] = pindex - 1;
            }
        } else {
            // Left to right
            if (this.getPageSide(pindex) == 'L') {
                spreadIndices[0] = pindex;
                spreadIndices[1] = pindex + 1;
            } else {
                // Given index was RHS
                spreadIndices[1] = pindex;
                spreadIndices[0] = pindex - 1;
            }
        }
        return spreadIndices;
    }

    br.ui = <?php echo json_encode($ui); ?>;
    // Book title and the URL used for the book title link
    br.bookTitle = json["feed"]["title"]["$t"];
    br.bookUrl = <?php echo json_encode(record_url($item)); ?>;
    br.logoURL = <?php echo json_encode(WEB_ROOT); ?>;
    br.siteName = <?php echo json_encode(option('site_title')); ?>;
    // Override the path used to find UI images
    br.imagesBaseURL = <?php echo json_encode($imgDir); ?>;

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
            '<div style="height: 140px; min-width: 80px; padding: 0; margin: 0;">',
            <?php echo json_encode(link_to_item($bookreader->itemCover())); ?>,
            '</div>'
        ].join(''));

        jInfoDiv.find('.BRfloatMeta').append([
            '<h3><?php echo html_escape(__('Other Formats')); ?></h3>',
            '<ul class="links">',
                '<?php echo $bookreader->linksToNonImages(); ?>',
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
        var url = '<?php echo absolute_url(array('id' => $item->id), 'bookreader_table'); ?>';
        // if (this.subPrefix != this.bookId) { // Only include if needed
        //    url += '/' + this.subPrefix;
        // }
        url += '?<?php echo ($part <= 1) ? '' : 'part=' . $part . '&'; ?>ui=embed';
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
            '.logo': <?php echo json_encode(__('Go to %s', option('site_title'))); ?>, // $$$ update after getting OL record
            '.zoom_in': <?php echo json_encode(__('Zoom in')); ?>,
            '.zoom_out': <?php echo json_encode(__('Zoom out')); ?>,
            '.onepg': <?php echo json_encode(__('One-page view')); ?>,
            '.twopg': <?php echo json_encode(__('Two-page view')); ?>,
            '.thumb': <?php echo json_encode(__('Thumbnail view')); ?>,
            '.print': <?php echo json_encode(__('Print this page')); ?>,
            '.embed': <?php echo json_encode(__('Embed BookReader')); ?>,
            '.link': <?php echo json_encode(__('Link to this document and page')); ?>,
            '.bookmark': <?php echo json_encode(__('Bookmark this page')); ?>,
            '.read': <?php echo json_encode(__('Read this document aloud')); ?>,
            '.share': <?php echo json_encode(__('Share this document')); ?>,
            '.info': <?php echo json_encode(__('About this document')); ?>,
            '.full': <?php echo json_encode(__('Show fullscreen')); ?>,
            '.book_left': <?php echo json_encode(__('Flip left')); ?>,
            '.book_right': <?php echo json_encode(__('Flip right')); ?>,
            '.book_up': <?php echo json_encode(__('Page up')); ?>,
            '.book_down': <?php echo json_encode(__('Page down')); ?>,
            '.play': <?php echo json_encode(__('Play')); ?>,
            '.pause': <?php echo json_encode(__('Pause')); ?>,
            '.BRdn': <?php echo json_encode(__('Show/hide nav bar')); ?>, // Would have to keep updating on state change to have just "Hide nav bar"
            '.BRup': <?php echo json_encode(__('Show/hide nav bar')); ?>,
            '.book_top': <?php echo json_encode(__('First page')); ?>,
            '.book_bottom': <?php echo json_encode(__('Last page')); ?>
        };
        if ('rl' == this.pageProgression) {
            titles['.book_leftmost'] = <?php echo json_encode(__('Last page')); ?>;
            titles['.book_rightmost'] = <?php echo json_encode(__('First page')); ?>;
        } else { // LTR
            titles['.book_leftmost'] = <?php echo json_encode(__('First page')); ?>;
            titles['.book_rightmost'] = <?php echo json_encode(__('Last page')); ?>;
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
        if (!$bookreader->hasDataForSearch()): ?>
    $('#textSrch').hide();
    $('#btnSrch').hide();
        <?php endif;
?>
    return;
}

function loadData() {
    var dataurl = <?php echo json_encode($tableUrl); ?>;
    $.ajax({
        url: dataurl,
        dataType: 'jsonP',
        jsonpCallback: "spreadsheetLoaded",
        success: function(data) {
            spreadsheetLoaded(data);
        }
    });
}

$(document).ready(function() {
    // key = window.location.search.split("key=")[0];
    // console.log(window.location + "; " + key);
    loadData();
});
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
