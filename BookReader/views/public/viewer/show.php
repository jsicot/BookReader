<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<?php 
	$id = id_booreader_item(); //retrieve item id
	set_current_item(get_item_by_id($id));
	$title = item('Dublin Core', 'Title'); //dc title
	$title.= " - ".item('Dublin Core', 'Creator');
	$title = htmlkarakter(item('Dublin Core', 'Title')); 
	$title = utf8_decode($title);
	?>	
    <title><?php echo $title; ?></title>
	 <link rel="stylesheet" href="<?php echo css('BookReader'); ?>" />
   	 <link rel="stylesheet" href="<?php echo css('BookReaderBibnum'); ?>" />
	<?php echo js('jquery-1.4.2.min'); ?>
	<?php echo js('jquery-ui-1.8.5.custom.min'); ?>
	<?php echo js('dragscrollable'); ?>
	<?php echo js('jquery.colorbox-min'); ?>
	<?php echo js('jquery.ui.ipad'); ?>
	<?php echo js('jquery.bt.min'); ?>
	<?php echo js('BookReader'); ?> 
	
</head>
<body style="background-color: #65645f;">
<div></div>      
<div id="BookReader">
	
       <br/>
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
br = new BookReader();
br.getPageWidth = function(index) {
    return this.pageW[index];
}

br.getPageHeight = function(index) {
    return this.pageH[index];
}

<?php
 	//retrieve image files from the item
	$listing= array();
	$imgNum = array();
	$SupportedFormats = array('jpeg' => 'JPEG Joint Photographic Experts Group JFIF format','jpg' => 'Joint Photographic Experts Group JFIF format','png' => 'Portable Network Graphics' );
	// Set the regular expression to match selected/supported formats.
	$supportedFormatRegEx = '/\.'.implode('|', array_keys($SupportedFormats)).'$/';
	$i=0;
		while(loop_files_for_item($item)) 
		{
			$file = get_current_file();
			if ($file->hasThumbnail()) 
			{
				if (preg_match($supportedFormatRegEx, $file->archive_filename)) {
				$key = $file->archive_filename;
				$listing[$key]=$file->original_filename;//Création du tableau avec les images de l'item			
				
				}
			}
			$i++;
		}
		//sorting by original filename;
		uasort($listing, 'cmp');
		$widths = array();
		$heights = array();
		$imgName = array();
		$j=0;
		foreach($listing as $image) 
		{
			$key = array_search($image, $listing);
			$pathImg = FULLSIZE_DIR."/".$key;
			list($width, $height, $type, $attr) = getimagesize($pathImg);
			$widths[]=$width; //array of images width
			$heights[]=$height;  //array of images height
			$imgName[]=label_pg($image); //array of images label
			$imgNum[$j]=$j;
			$j++;
		}	
?>

br.pageW =  [<?php echo implode(",",$widths);	?> ];
br.pageH =  [ <?php echo implode(",",$heights);	?>  ];
br.leafMap = [<?php echo implode(",",$imgNum);	?>  ];
br.pageNums = [<?php echo implode(",",$imgName); ?> ];
<?php echo titleLeaf(); ?>

br.numLeafs = br.pageW.length;

br.getPageNum = function(index) {
    var pageNum = this.pageNums[index];
    if (pageNum) {
        return pageNum;
    } else {
        return 'n' + index;
    }
}


// getOpenLibraryRecord
br.getOpenLibraryRecord = function(callback) {
// Try looking up by ocaid first, then by source_record
var self = this; // closure
var jsonURL = self.olHost + '/query.json?type=/type/edition&*=&ocaid=' + self.bookId;
$.ajax({
url: jsonURL,
success: function(data) {
if (data && data.length > 0) {
callback(self, data[0]);
} else {
// try sourceid
jsonURL = self.olHost + '/query.json?type=/type/edition&*=&source_records=ia:' + self.bookId;
$.ajax({
url: jsonURL,
success: function(data) {
if (data && data.length > 0) {
callback(self, data[0]);
}
},
dataType: 'jsonp'
});
}
},
dataType: 'jsonp'
});
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
    for (var index = 0; index < this.leafMap.length; index++) {
        if (this.leafMap[index] == leafNum) {
            return index;
        }
    }

    return null;
}

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
			
br.cleanupMetadata = function() {
    br.uniquifyPageNums();
}

// We load the images from archive.org -- you can modify this function to retrieve images
// using a different URL structure
br.getPageURI = function(index, reduce, rotate) {
    // reduce and rotate are ignored in this simple implementation, but we
    // could e.g. look at reduce and load images from a different directory
    // or pass the information to an image server
    var leafStr = '000';            
    var imgStr = (index+1).toString();
    var re = new RegExp("0{"+imgStr.length+"}$");
    var url = '<?php echo WEB_ROOT; ?>/book-reader/index/image-proxy/?image='+leafStr.replace(re, imgStr) + '&id=<?php echo $id; ?>&scale='+reduce ;
    return url;
}




// Return which side, left or right, that a given page should be displayed on
br.getPageSide = function(index) {
    if (0 == (index & 0x1)) {
        return 'R';
    } else {
        return 'L';
    }
}

// This function returns the left and right indices for the user-visible
// spread that contains the given index.  The return values may be
// null if there is no facing page or the index is invalid.
br.getSpreadIndices = function(pindex) {   
    var spreadIndices = [null, null]; 
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

br.ui = '<?php echo ui_booreader_item(); ?>';

// Book title and the URL used for the book title link
br.bookTitle= '<?php echo $title; ?>';
br.bookUrl  = "<?php echo item_uri(); ?>";
br.logoURL = "<?php echo WEB_ROOT; ?>";
br.siteName = "<?php echo settings('site_title');?>";

// Override the path used to find UI images
br.imagesBaseURL = '<?php echo booreader_img_dir(); ?>';


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
                    '<div style="height: 140px; min-width: 80px; padding: 0; margin: 0;"><?php echo  link_to_item(item_cover()); ?></div>'].join('')
    );

    jInfoDiv.find('.BRfloatMeta').append([
                    '<h3>Other Formats</h3>',
                    '<ul class="links">',
                        '<li><?php echo item_PDF($item); ?></li>',
						//'<span>|</span></li>',
          
                    '</ul>',
                    '<p class="moreInfo"><a href="'+ this.bookUrl + '">More information</a>  </p>'].join('\n'));
                    
    jInfoDiv.find('.BRfloatFoot').append([
                '<span>|</span>',                
                '<a href="mailto:<?php echo settings('administrator_email');?>" class="problem">Report a problem</a>',
    ].join('\n'));
                
    if (domain == 'archive.org') {
        jInfoDiv.find('.BRfloatMeta p.moreInfo span').css(
            {'background': 'url(http://www.archive.org/favicon.ico) no-repeat', 'width': 22, 'height': 18 }
        );
    }
    
    jInfoDiv.find('.BRfloatTitle a').attr({'href': this.bookUrl, 'alt': this.bookTitle}).text(this.bookTitle);
    var bookPath = (window.location + '').replace('#','%23');
    jInfoDiv.find('a.problem').attr('href','mailto:<?php echo settings('administrator_email');?>?subject=' + bookPath);

}

// getEmbedURL
//________
// Returns a URL for an embedded version of the current book
br.getEmbedURL = function(viewParams) {
    // We could generate a URL hash fragment here but for now we just leave at defaults
    //var url = 'http://' + window.location.host + '/stream/'+this.bookId;
	var bookId = <?php echo id_booreader_item(); ?>;
	var url = '<?php echo WEB_ROOT; ?>/viewer/show/<?php echo $id; ?>';
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

// Let's go!
br.init();

// read-aloud and search need backend compenents and are not supported in the demo
$('#BRtoolbar').find('.read').hide();
$('#textSrch').hide();
$('#btnSrch').hide();
</script>

</body>
</html>
