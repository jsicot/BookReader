<?php
    //retrieve item id
    $id = BookReader::bookreaderCurrItemId();
    $item = get_record_by_id('item', $id);
    set_current_record('item', $item);

    $title = metadata('item', array('Dublin Core', 'Title'));
    if ($creator = metadata('item', array('Dublin Core', 'Creator'))) {
        $title .= ' - ' . $creator;
    }
    $title = BookReader::htmlCharacter($title);
    $coverFile = BookReader::getCoverFile($item);

    list($imgNums, $imgLabels, $imgWidths, $imgHeights) = BookReader::imagesData();

    $ui = BookReader::bookreaderCurrItemUI();

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
  <!--   <link rel="apple-touch-icon" href="<?php //echo WEB_FILES . '/thumbnails/' . $coverFile->getDerivativeFilename(); ?>" /> -->
    <link rel="shortcut icon" href="<?php echo get_option('bookreader_favicon_url'); ?>" type="image/x-icon" />
    <title><?php echo $title; ?></title>
    <!-- Stylesheets -->
    <link rel="stylesheet" href="<?php echo $sharedUrl . '/css/BookReader.css'; ?>" />
    <link rel="stylesheet" href="<?php echo get_option('bookreader_custom_css'); ?>" />
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
// Copyright(c)2008-2009 Internet Archive. Software license AGPL version 3.

// Create the BookReader object

        imagesArray = [];
function spreadsheetLoaded(json){

        imagesArray = [];

        json = json.substring(json.indexOf("(")+1);
        json = json.substring(0,json.lastIndexOf(")"));
        
        json=JSON.parse(json);
        parts = json["feed"]["entry"];

        for (n in parts ){
        
           if (parts[n]["gsx$image"]!=undefined){

                imagesArray.push({"image":parts[n]["gsx$image"]["$t"],"height":parts[n]["gsx$height"]["$t"],"width":parts[n]["gsx$width"]["$t"]});
                }
        }
        var externalArray = [];
        var htmlstring  = "";
        
        
        // Let's go!
        //br.init();

        // read-aloud and search need backend compenents and are not supported in
        // the demo
br = new BookReader();
        br.imagesBaseURL = '../BookReader/images/';

        // Return the width of a given page. Here we assume all images are 800
        // pixels wide
        br.getPageWidth = function(index) {
                if ((imagesArray[index])&&(imagesArray[index].width!=undefined)) {
                return parseInt(imagesArray[index].width);
                }
                else{
                return 800;
                }
        }

        // Return the height of a given page. Here we assume all images are 1200
        // pixels high
        br.getPageHeight = function(index) {
                if ((imagesArray[index])&&(imagesArray[index].height!=undefined)){
                return parseInt(imagesArray[index].height)
                }
                else{
                return 1200;
                }
        }

        // We load the images from archive.org -- you can modify this function to
        // retrieve images
        // using a different URL structure
        br.getPageURI = function(index, reduce, rotate) {
                // reduce and rotate are ignored in this simple implementation, but we
                // could e.g. look at reduce and load images from a different directory
                // or pass the information to an image server
                // var leafStr = '000';
                // var imgStr = (index+1).toString();
                // var re = new RegExp("0{"+imgStr.length+"}$");
                // var url =
                // 'http://www.archive.org/download/BookReader/img/page'+leafStr.replace(re,
                // imgStr) + '.jpg';
                
                url = imagesArray[index].image;

                return url;
        }

        // Return which side, left or right, that a given page should be displayed
        // on
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

        // For a given "accessible page index" return the page number in the book.
        //
        // For example, index 5 might correspond to "Page 1" if there is front
        // matter such
        // as a title page and table of contents.
        br.getPageNum = function(index) {
                return index + 1;
        }

        // Total number of leafs
        br.numLeafs = imagesArray.length;

        // Book title and the URL used for the book title link
        br.bookTitle = json["feed"]["title"]["$t"];
        br.bookUrl = "proba" //BookReaderConfig.bookUrl;

        // Override the path used to find UI images
        // br.imagesBaseURL = '../BookReader/images/';

        br.getEmbedCode = function(frameWidth, frameHeight, viewParams) {
                return "Embed code not supported in bookreader demo.";
        }
        br.init();
        $('#BRtoolbar').find('.read').hide();
        $('#BRreturn').html($('#BRreturn').text());
        //$('#textSrch').hide();
        //$('#btnSrch').hide();
        return;
}                
function loadData(){
        var dataurl = 'https://spreadsheets.google.com/feeds/list/'+'0Ag7PrlWT3aWadDdVODJLVUs0a1AtUVlUWlhnXzdwcGc'+'/od6/public/values?alt=json-in-script&callback=spreadsheetLoaded';
        $.ajax({
          url: dataurl,
          dataType: 'jsonP',
          jsonpCallback: "spreadsheetLoaded",
            success: function(data){
                        spreadsheetLoaded(data);
            }
        });}
$(document).ready(function() {
key = window.location.search.split("key=")[0];
console.log(window.location + "; " + key);
loadData();

        
});


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

