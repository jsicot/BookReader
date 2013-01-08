<?php

function htmlkarakter($string)
{
   	$string = strip_tags($string);
	$string = html_entity_decode($string, ENT_QUOTES);
	$string = utf8_encode($string);
	$string = htmlspecialchars_decode($string);
	$string = addslashes($string);

       return $string;
  
}

function BrSearchAvailable ($item)
{
	set_current_item($item);
	$xml_file = false;
	while (loop_files_for_item())
	{
		$file = get_current_file();
		if (preg_match("/\.xml$/", $file->original_filename))
		{
			$xml_file = escapeshellarg(FILES_DIR . "/" . $file->archive_filename);
		}
	}
	return $xml_file;
}

function findArchiveName ($image){
	$db = get_db();
	$query = $db->select()->from(array($db->Files), 'archive_filename')->where('original_filename = ?', $image);
	return $db->fetchOne($query);  }

function id_booreader_item()
{
	$view = __v();
	return $view->booreaderCurrItem->id;
	
}

function ui_booreader_item()
{
	$view = __v();
	return $view->booreaderCurrItem->ui;
	
}

function booreader_img_dir() 
{
	$imgDir = WEB_PLUGIN . DIRECTORY_SEPARATOR .'BookReader/views/shared/images/';
	return $imgDir ;
}

function bookreader_item_numLeafs($item)
{
	//création d'un tableau composé de l'ensemble des images de l'item consulté
	$listing= array();
	$i=0;

	while(loop_files_for_item($item)) {    	 
		$file = get_current_file();
		if ($file->hasThumbnail()) {			
			//$listing[$i]=$file->archive_filename;//Création du tableau
			$listing[$i]=item_file('Original Filename');

		}
		$i++;
	}
	//compte le nb d'imgages dans le tableau
	return $numLeafs = count($listing);
}

function findImgPath ($image){
	$db = get_db();
	$query = $db->select('original_filename')->from(array($db->Files), 'archive_filename')->where('original_filename = ?', $image);
	return $db->fetchOne($query);  }


function label_pg($txt) 
	{
		$re1='.*?';	# Non-greedy match on filler
		$re2='(page)';	# Word 1
		$re3='(\\d+)';	# Integer Number 1
		if ($c=preg_match_all ("/".$re1.$re2.$re3."/is", $txt, $matches))
		{
			$word1=$matches[1][0];
			$int1=$matches[2][0];
			$int1 = preg_replace( "/^[0]{0,6}/", "", $int1 );  
			return $int1 ;
		}
		else{return 'null';}
	
	}

function cmp($a, $b)
{
	  	if ($a == $b) {
	        return 0;
	    }
	    return ($a < $b) ? -1 : 1;
}

function item_cover($props = array(), $index = 0, $item = null)
 {
  $i=0;  
  while(loop_files_for_item($item)) {
    	 
       $file = get_current_file();
       $listimg[$i]=$file->original_filename;//Création du tableau
	$i++;          
    }
    
		foreach ($listimg as $j => $value) {
	 	
	 		$re1='.*?';	
			$re2='(titre)';
			$re3='(\\d+)';	
			$width = @$props['width'];
    			$height = @$props['height'];
    			$defaultProps = array('alt'=>html_escape(item('Dublin Core', 'Title', array(), $item)));
			$props = array_merge($defaultProps, $props);

			if ($c=preg_match_all ("/".$re1.$re2.$re3."/is", $value, $matches)){
				$value = findArchiveName ($value);
				$img ='<img src="'.WEB_THUMBNAILS. DIRECTORY_SEPARATOR . $value . '" '._tag_attributes($props) .' width="auto" height="120" />';
			
		}
		else {}
         
    }
	
    
     return $img;
 }

//fonction pour récupérer les liens des fichiers de type PDF, DOC, ODT, etc (tous sauf les images)
function item_PDF($item=null) { 

	if ($item == null)
 	{
        	$item = get_current_item();
   	 }
 	//récupération du fichier xml à traiter en fonction de l'id de l'item

	//extensions supportées
	$SupportedFormats = array(
        'pdf' => 'Portable Document Format File');

// Set the regular expression to match selected/supported formats.
        $supportedFormatRegEx = '/\.'.implode('|', array_keys($SupportedFormats)).'$/';		 
	$i = 1;
        // Iterate through the item's files.
       while(loop_files_for_item($item)) 
	{
        	$file = get_current_file();          	   

            // Embed only those files that end with the selected/supported formats.
            if (preg_match($supportedFormatRegEx, $file->archive_filename)) {

                // Set the document's absolute URL.
                // Note: file_download_uri($file) does not work here. It results 
                // in the iPaper error: "Unable to reach provided URL."
                //$documentUrl = WEB_FILES.'/'.$file->archive_filename;
		//$documentUrl = file_download_uri($file);
		$sizefile=formatfilesize($file->size);
		//$type = $file->mime_browser; 
			echo '<div style="clear:both;padding:2px;"><a href="'. file_download_uri($file). '" class="download-file">'. $file->original_filename. '</a>&nbsp; ('.$sizefile.')</div> ';
		$i++;
            }
        } 

        }

function titleLeaf($item = null)
	{
		 	$listimg= array();
			$i=1;
			//extensions supportées
			$SupportedFormats = array('jpeg' => 'JPEG Joint Photographic Experts Group JFIF format','jpg' => 'Joint Photographic Experts Group JFIF format','png' => 'Portable Network Graphics' );

			// Set the regular expression to match selected/supported formats.
			        $supportedFormatRegEx = '/\.'.implode('|', array_keys($SupportedFormats)).'$/';
			    while(loop_files_for_item($item)) {
			       $file = get_current_file();
					if ($file->hasThumbnail()) 
					{
						if (preg_match($supportedFormatRegEx, $file->archive_filename)) {
			        	$listimg[$i]=$file->original_filename;//Création du tableau
						}
					}
					$i++;
			    }
			//sorting by original filename;
			sort($listimg);
		   
			foreach ($listimg as $j => $value) {
	 			$re1='.*?';	# Non-greedy match on filler
				  $re2='(titre)';	# Word 1
				  $re3='.*?';	# Non-greedy match on filler
				  $re4='(01)';	# Any Single Digit 1

				if ($c=preg_match_all ("/".$re1.$re2.$re3.$re4."/is", $value, $matches))
				{
					$titleLeaf = "br.titleLeaf = ". $j ;
				}
    		}
     	return $titleLeaf;
 	}

function formatfilesize($size)
	{
		if ($size < 1024)
			return $size." octets";

		foreach (array (" Ko", " Mo", " Go", " To") as $unit)	{
			$size /= 1024.0;
			if ($size < 10)
				return sprintf("%.1f".$unit, $size);
			if ($size < 1024)
				return (int)$size.$unit;
		}
	}
?>
