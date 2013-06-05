<?php
/**
 * The index controller class.
 *
 * @package BookReader
 */
class BookReader_IndexController extends Omeka_Controller_AbstractActionController
{
    /**
     * This function returns the answer to a query with coordinates of the
     * matching words.
     */
    public function fulltextAction()
    {
        $item_id = $this->getRequest()->getParam('item_id');
        $doc = $this->getRequest()->getParam('doc');
        $path = $this->getRequest()->getParam('path');
        $q = $this->getRequest()->getParam('q');
        $q = utf8_encode($q);
        $callback = $this->getRequest()->getParam('callback');

        // On va récupérer le fichier XML de l'item
        $this->getResponse()->clearBody();
        $this->getResponse()->setHeader('Content-Type', 'text/html');
        $item = get_record_by_id('item', $item_id);

        $list = array();
        set_loop_records('files', $item->getFiles());
        foreach (loop('files') as $file) {
            if (strtolower(pathinfo($file->original_filename, PATHINFO_EXTENSION)) == 'xml') {
                $xml_file = escapeshellarg(FILES_DIR . DIRECTORY_SEPARATOR . $file->filename);
            }
            elseif ($file->hasThumbnail()) {
                if (preg_match('/(jpg|jpeg|png|gif)/', $file->filename)) {
                    $list[$file->filename] = $file->original_filename;
                }
            }
        }
        // Sorting by original filename if needed, or keep original attached order.
        // uasort($list, 'cmp');

        $widths = array();
        $heights = array();
        foreach ($list as $key => $image) {
            $pathImg = FILES_DIR . DIRECTORY_SEPARATOR . 'fullsize' . DIRECTORY_SEPARATOR . $key;
            list($width, $height, $type, $attr) = getimagesize($pathImg);
            $widths[] = $width;
            $heights[] = $height;
        }

        // On a un fichier XML, on va aller l'interroger pour voir si on a des choses dedans
        if ($xml_file) {
            $res = shell_exec("grep -P -i '<\/?page|$q' $xml_file");
            $res = preg_replace("/<page[^>]*>\n<\/page>\n/",'',$res);

            $sortie = array();
            $sortie['ia'] = $doc;
            $sortie['q'] = $q;
//          $sortie['page_count'] = 200; // Voir s'il faut vraiment le récupérer
//          $sortie['body_length'] = 140000; // Idem, voir si c'est utilisé
            $sortie['leaf0_missing'] = false; // Kezako ?
            $sortie['matches'] = array();

            // On va parcourir toutes les lignes qui matchent
            while (preg_match('/<page number="(\d*)" [^>]*height="(\d*)" width="(\d*)">\n(.*)\n<\/page>(.*)$/siU', $res, $match)) {
                $page_number = $match[1] - 1;
                $page_height = $match[2];
                $page_width  = $match[3];
                $zones = $match[4];
                $res = $match[5]; // On reprend pour la suite;

                $tab_lignes = preg_split('/<text /', $zones);
                foreach ($tab_lignes as $une_ligne) {
                    if (preg_match('/top="(\d*)" left="(\d*)" width="(\d*)" height="(\d*)" font="(\d*)">(.*)<\/text>$/', $une_ligne, $match_ligne)) {
                        $zone_top = $match_ligne[1];
                        $zone_left = $match_ligne[2];
                        $zone_width = $match_ligne[3];
                        $zone_height = $match_ligne[4];
                        $zone_font = $match_ligne[5];
                        $zone_text = $match_ligne[6];
                        $zone_text = preg_replace("/<\/?[ib]>/", "", $zone_text);

                        $zone_right = ($page_width - $zone_left - $zone_widht);
                        $zone_bottom = ($page_height - $zone_top - $zone_height);

                        // On crée la zone "globale"
                        $tab_zone = array();
                        $tab_zone['text'] = $zone_text;

                        // On va créer les boxes ...
                        $zone_width_char = strlen($zone_text);
                        $mot_start_char = stripos($zone_text, $q);
                        $mot_width_char = strlen($q);
                        $zone_text = str_ireplace($q, '{{{' . $q . '}}}', $zone_text);

                        $mot_left =  $zone_left + ( ($mot_start_char * $zone_width) / $zone_width_char);
                        $mot_right = $mot_left + ( ( ( $mot_width_char + 2) * $zone_width) / $zone_width_char );
                    #   print 'L : ' . $mot_left . PHP_EOL;
                    #   print 'R : ' . $mot_right . PHP_EOL;

                        $mot_left = round($mot_left * $widths[$page_number] / $page_width);
                        $mot_right  = round( $mot_right * $widths[$page_number] / $page_width);
                    #   print "Zone texte : #$zone_text ($zone_width_char char pour $zone_width px)#" . PHP_EOL;

                        $mot_top = round($zone_top * $heights[$page_number] / $page_height);
                        $mot_bottom = round($mot_top + ( $zone_height * $heights[$page_number] / $page_height  ));

                        $tab_zone['par'] = array();
                        $tab_zone['par'][] = array(
                            't' => $zone_top,
                            'r' => $zone_right,
                            'b' => $zone_bottom,
                            'l' => $zone_left,
                            'page' => $page_number,
                            'boxes' => array(
                                array(
                                    'r' => $mot_right,
                                    'l' => $mot_left,
                                    'b' => $mot_bottom,
                                    't' => $mot_top,
                                    'page' => $page_number,
                        )));

//                      if ($page_number == 513) {
                        if (true) {
                            $sortie['matches'][] = $tab_zone;
                        }
                    }
                    elseif ($une_ligne != '') {
                        print '####>>>>> PB ligne : #' . $une_ligne . '#' . PHP_EOL;
                    }
                }
            }
        }

        $tab_json = json_encode($sortie);
        $pattern = array(',"', '{', '}');
        $replacement = array(",\n\t\"", "{\n\t", "\n}");

        print $callback . '(' . $tab_json . ')';
    }

    public function imageProxyAction()
    {
        $num_img = $this->getRequest()->getParam('image');
        if ($num_img != '000') {
            $num_img = preg_replace('`^[0]*`', '', $num_img);
        }
        else {
            $num_img = '0';
        }
        $num_img = ($num_img - 1);

        $filesDir = FILES_DIR . DIRECTORY_SEPARATOR;

        $scale = $this->getRequest()->getParam('scale');
        switch ($scale) {
            case ($scale < 1.1): $filesDir .= 'original'; break;
            case ($scale < 1.4): $filesDir .= 'fullsize'; break;
            case ($scale < 6): $filesDir .= 'fullsize'; break;
            case ($scale < 16): $filesDir .= 'thumbnails'; break;
            case ($scale < 32): $filesDir .= 'thumbnails'; break;
            default: $filesDir .= 'fullsize'; break;
        }

        $id = $this->getRequest()->getParam('id');
        $item = get_record_by_id('item', $id);

        // Création d'un tableau composé de l'ensemble des images de l'item consulté
        $list = array();
        set_loop_records('files', $item->getFiles());
        foreach (loop('files') as $file) {
            if ($file->hasThumbnail()) {
                // $list[] = $file->filename;//Création du tableau
                $list[] = $file->original_filename;
            }
        }
        // Compte le nombre d'images dans le tableau.
        $nbimg = count($list);

        // Si $list n'est pas un tableau, message d'erreur, sinon traitement.
        if (!is_array($list)) {
            $html = '<br />' . PHP_EOL;
            $html .= '<br />' . PHP_EOL;
            $html .= __('Problem');
            $html .= '<br />' . PHP_EOL;
            $html .= '<br />' . PHP_EOL;
            $html .= '</a>' . PHP_EOL;
            $html .= '</div>' . PHP_EOL;
        }
        else {
            // Sorting by original filename if needed, or keep original attached order.
            // sort($list);
        }

        $image=$list[$num_img];
        $db = get_db();
        $query = $db
            ->select()
            ->from(array($db->Files), 'filename')
            ->where('original_filename = ?', $image)
            ->where('item_id = ?', $id);
        $image = $db->fetchOne($query);
        $image = $filesDir . DIRECTORY_SEPARATOR . $image;
        $image = file_get_contents($image);

        $this->getResponse()->clearBody ();
        $this->getResponse()->setHeader('Content-Type', 'image/jpeg');
        $this->getResponse()->setBody($image);
    }

    public function thumbProxyAction()
    {
        $num_img = $this->getRequest()->getParam('image');
        if ($num_img != '000') {
            $num_img = preg_replace('`^[0]*`', '', $num_img);
        } else {
            $num_img = '0';
        }
        $num_img = ($num_img - 1);

        $filesDir = FILES_DIR . DIRECTORY_SEPARATOR . 'thumbnails';

        $id = $this->getRequest()->getParam('id');
        $item = get_record_by_id('item', $id);

        //création d'un tableau composé de l'ensemble des images de l'item consulté
        $list = array();
        set_loop_records('files', $item->getFiles());
        foreach (loop('files') as $file) {
            if ($file->hasThumbnail()) {
                // $list[] = $file->filename;
                $list[] = $file->original_filename;
            }
        }
        // Compte le nombre d'images dans le tableau.
        $nbimg = count($list);

        // Si $list n'est pas un tableau, message d'erreyr, sinon traitement.
        if (!is_array($list)) {
            $html = '<br />' . PHP_EOL;
            $html .= '<br />' . PHP_EOL;
            $html .= __('Problem');
            $html .= '<br />' . PHP_EOL;
            $html .= '<br />' . PHP_EOL;
            $html .= '</a>' . PHP_EOL;
            $html .= '</div>' . PHP_EOL;
        }
        else {
            // Sorting by original filename if needed, or keep original attached order.
            // sort($list);
        }

        $image = $list[$num_img];
        $db = get_db();
        $query = $db
            ->select()
            ->from(array($db->Files), 'filename')
            ->where('original_filename = ?', $image);
        $image = $db->fetchOne($query);
        $image = $filesDir . DIRECTORY_SEPARATOR . $image;
        $image = file_get_contents($image);

        $this->getResponse()->clearBody ();
        $this->getResponse()->setHeader('Content-Type', 'image/jpeg');
        $this->getResponse()->setBody($image);
    }
}
