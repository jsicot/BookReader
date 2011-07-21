<?php
class BookReader_IndexController extends Omeka_Controller_Action
{	
    public function imageProxyAction()
    {
		
	$num_img = $this->getRequest()->getParam('image');
	$scale = $this->getRequest()->getParam('scale');
	if ($num_img!="000"){
	$num_img=preg_replace('`^[0]*`','',$num_img); 
	}
	else{$num_img="0";}
	$num_img = ($num_img-1);
	$id = $this->getRequest()->getParam('id');
	set_current_item(get_item_by_id($id));

	if ($scale < 1.2) {
       $files=WEB_FILES ;// répertoire des images originales
    } else if ($scale < 2) {
       $files=WEB_FULLSIZE ;// répertoire des images diff web
    } else if ($scale < 6) {
        $files=WEB_FULLSIZE ;// répertoire des images diff web
    } else if ($scale < 16) {
        $files=WEB_THUMBNAILS ;// répertoire des vignettes
    } else  if ($scale < 32) {
        $files=WEB_THUMBNAILS ;// répertoire des vignettes
    } else {
        $files=WEB_FULLSIZE ;// répertoire des images originales
    }
	
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
	$nbimg = count($listing); 

	// DESSOUS : si $listing n'est pas un tableau, message, sinon, traitement
	if(!is_array($listing))
	{
		$html .="<br><br>\n";
		$html .= "problème :-( <br><br>\n";
		$html .= "</a>\n";
		$html .= "</div>\n";
	}
	else
	{    	
		// TRI DE LA LISTE DES FICHIERS
		sort($listing); 	
	}

	$image=$listing[$num_img];
	$db = get_db();
	$query = $db->select()->from(array($db->Files), 'archive_filename')->where('original_filename = ?', $image);
	$image = $db->fetchOne($query); 
	//$image=findImgPath ($image);
	$image = $files."/".$image;
    	$image = file_get_contents($image);  	
	$this->getResponse()->clearBody ();
	$this->getResponse()->setHeader('Content-Type', 'image/jpeg');
	$this->getResponse()->setBody($image); 
    }

	public function thumbProxyAction()
    {
		
	$num_img = $this->getRequest()->getParam('image');
	if ($num_img!="000"){
	$num_img=preg_replace('`^[0]*`','',$num_img); 
	}
	else{$num_img="0";}
	$num_img = ($num_img-1);
	$id = $this->getRequest()->getParam('id');
	set_current_item(get_item_by_id($id));

	$files=WEB_THUMBNAILS ;// répertoire des images originales

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
	$nbimg = count($listing); 

	// DESSOUS : si $listing n'est pas un tableau, message, sinon, traitement
	if(!is_array($listing))
	{
		$html .="<br><br>\n";
		$html .= "problème :-( <br><br>\n";
		$html .= "</a>\n";
		$html .= "</div>\n";
	}
	else
	{    	
		// TRI DE LA LISTE DES FICHIERS
		sort($listing); 	
	}

	$image=$listing[$num_img];
	$db = get_db();
	$query = $db->select()->from(array($db->Files), 'archive_filename')->where('original_filename = ?', $image);
	$image = $db->fetchOne($query); 
	//$image=findImgPath ($image);
	$image = $files."/".$image;
    	$image = file_get_contents($image);  	
	$this->getResponse()->clearBody ();
	$this->getResponse()->setHeader('Content-Type', 'image/jpeg');
	$this->getResponse()->setBody($image); 
    }
	

}
