<?php
class BookReader_IndexController extends Omeka_Controller_Action
{
	// this function will return the answers to a query
	// with coordinates of the words matching
	public function fulltextAction()
	{
		$item_id = $this->getRequest()->getParam('item_id');
		$doc = $this->getRequest()->getParam('doc');
		$path = $this->getRequest()->getParam('path');
		$q = $this->getRequest()->getParam('q');
		$callback = $this->getRequest()->getParam('callback');
		

		// On va récupérer le fichier XML de l'item
	
		$this->getResponse()->clearBody ();
		$this->getResponse()->setHeader('Content-Type', 'text/html');
		set_current_item(get_item_by_id($item_id));
		$listing = array();

		while (loop_files_for_item())
		{
			$file = get_current_file();
			if (preg_match("/\.xml$/", $file->original_filename))
			{
				$xml_file = escapeshellarg(FILES_DIR. "/" . $file->archive_filename);
			}
			elseif ($file->hasThumbnail())
			{
				if (preg_match("/(jpg|jpeg|png)/", $file->archive_filename))
				{
					$key = $file->archive_filename;
					$listing[$key]=$file->original_filename;
				}
			}

		}
		uasort($listing, 'cmp');
		$widths = array();
		$heights = array();

		$j = 0;
		foreach ($listing as $key => $image)
		{
			$pathImg = FULLSIZE_DIR."/".$key;
			list($width, $height, $type, $attr) = getimagesize($pathImg);
			$widths[] = $width;
			$heights[] = $height;
		}

		if ($xml_file)
		{
			// On a un fichier XML, on va aller l'interroger pour voir si on a des choses dedans
			$res = shell_exec("grep -P -i '<\/?page|$q' $xml_file");
			$res = preg_replace("/<page[^>]*>\n<\/page>\n/",'',$res);

			$sortie = Array(); 
			$sortie["ia"] = $doc;
			$sortie["q"] = $q;
//			$sortie["page_count"] = 200; // Voir s'il faut vraiment le récupérer
//			$sortie["body_length"] = 140000; // Idem, voir si c'est utilisé
			$sortie["leaf0_missing"] = false; // Kezako ?
			$sortie["matches"] = Array();

			// On va parcourir toutes les lignes qui matchent
			while (preg_match('/<page number="(\d*)" [^>]*height="(\d*)" width="(\d*)">\n(.*)\n<\/page>(.*)$/siU', $res, $match))
			{
				$page_number = $match[1] - 1;
				$page_height = $match[2];
				$page_width  = $match[3];
				$zones = $match[4];
				$res = $match[5]; // On reprend pour la suite;
				$tab_lignes = preg_split('/<text /', $zones);
				foreach ($tab_lignes as $une_ligne)
				{
					if (preg_match('/top="(\d*)" left="(\d*)" width="(\d*)" height="(\d*)" font="(\d*)">(.*)<\/text>$/', $une_ligne, $match_ligne))
					{
						$zone_top = $match_ligne[1];
						$zone_left = $match_ligne[2];
						$zone_width = $match_ligne[3];
						$zone_height = $match_ligne[4];
						$zone_font = $match_ligne[5];
						$zone_text = $match_ligne[6];
						$zone_text = preg_replace("/<\/?[ib]>/", "", $zone_text);

						$zone_right = ($page_width - $zone_left - $zone_widht);
						$zone_bottom = ($page_height -$zone_top - $zone_height);

						// On crée la zone "globale"
						$tab_zone = Array();
						$tab_zone["text"] = $zone_text;

						// On va créer les boxes ...
						$zone_width_char = strlen($zone_text);
						$mot_start_char = stripos($zone_text, $q);
						$mot_width_char = strlen($q);
						$zone_text = str_ireplace($q, "{{{".$q."}}}", $zone_text);

						$mot_left =  $zone_left + ( ($mot_start_char * $zone_width) / $zone_width_char);
						$mot_right = $mot_left + ( ( ( $mot_width_char + 2) * $zone_width) / $zone_width_char );
					#	print "L : $mot_left\n";
					#	print "R : $mot_right\n";


						$mot_left = round($mot_left * $widths[$page_number] / $page_width);
						$mot_right  = round( $mot_right * $widths[$page_number] / $page_width);
					#	print "Zone texte : #$zone_text ($zone_width_char char pour $zone_width px)#\n";
						
						
						$mot_top = round($zone_top * $heights[$page_number] / $page_height);
						$mot_bottom = round($mot_top + ( $zone_height * $heights[$page_number] / $page_height  ));

						$tab_zone["par"] = Array();
						$tab_zone["par"][] = Array(
							"t" => $zone_top,
							"l" => $zone_left,
							"b" => $zone_bottom,
							"r" => $zone_right,
							"page" => $page_number,
							"boxes" => Array(
									Array(
										"r" => $mot_right,
										"l" => $mot_left,
										"b" => $mot_bottom,
										"t" => $mot_top,
										"page" => $page_number
									)
								)
							);

//						if ($page_number == 513)
						if (true)
						{
							$sortie["matches"][] = $tab_zone;
						}
					}
					elseif ($une_ligne != "")
					{
						print "####>>>>> PB ligne : #$une_ligne#\n";
					}
				}
			}
		}
		
		$tab_json = json_encode($sortie);
		$pattern = array(',"', '{', '}');
		$replacement = array(",\n\t\"", "{\n\t", "\n}");

		print $callback."(".$tab_json.")";
	}
		
	public function imageProxyAction()
	{
		$num_img = $this->getRequest()->getParam('image');
		$scale = $this->getRequest()->getParam('scale');
		if ($num_img!="000")
		{
			$num_img=preg_replace('`^[0]*`','',$num_img); 
		}
		else
		{
			$num_img="0";
		}
		$num_img = ($num_img-1);
		$id = $this->getRequest()->getParam('id');
		set_current_item(get_item_by_id($id));
		
		if ($scale < 1)
		{
			// De 0 à 1
			$files=WEB_FILES ;// répertoire des images originales
		}
		else if ($scale < 1.4)
		{
			// De 1 à 2
			$files=WEB_FULLSIZE ;// répertoire des images diff web
		}
		else if ($scale < 6)
		{
			// De 2 à 6
			$files=WEB_THUMBNAILS ;// répertoire des images diff web
		}
		else if ($scale < 16)
		{
			// De 6 à 16
			$files=WEB_THUMBNAILS ;// répertoire des vignettes
		}
		else  if ($scale < 32)
		{
			// De 16 à 32
			$files=WEB_THUMBNAILS ;// répertoire des vignettes
		}
		else
		{
			// Au dessus de 32 ?
			$files=WEB_FULLSIZE ;// répertoire des images originales
		}
		
		//création d'un tableau composé de l'ensemble des images de l'item consulté
		$listing= array();
		$i=0;
		
		while(loop_files_for_item($item)) {
			$file = get_current_file();
			if ($file->hasThumbnail())
			{
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
		if ($num_img!="000")
		{
			$num_img=preg_replace('`^[0]*`','',$num_img); 
		}
		else
		{
			$num_img="0";
		}
		$num_img = ($num_img-1);
		$id = $this->getRequest()->getParam('id');
		set_current_item(get_item_by_id($id));
		
		$files = WEB_THUMBNAILS ;// répertoire des images originales
		
		//création d'un tableau composé de l'ensemble des images de l'item consulté
		$listing= array();
		$i=0;
		
		while(loop_files_for_item($item))
		{
			$file = get_current_file();
			if ($file->hasThumbnail())
			{
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
