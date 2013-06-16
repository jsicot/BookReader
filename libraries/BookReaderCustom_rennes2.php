<?php
/**
 * @file
 *   All functions of this file must be adapted to your needs, except names and
 *   parameters.
 *
 * @todo Integrate this in the configuration form.
 * @todo Use an abstract model class.
 * @todo Use Omeka 2.0 search functions.
 *
 * @note These functions are an example used by Université de Rennes 2 and have
 *   not been fully checked.
 */

/**
 * Custom helpers for BookReader.
 *
 * @package BookReader
 */
class BookReader_Custom
{
    /**
     * Get the page label from a string, generally the last word of a filename.
     *
     * @return string
     *   Label of the page, or 'null' if none.
     */
    public static function getLabelPage($file)
    {
        if (is_null($file)) {
            return '';
        }

        $txt = $file->original_filename;

        $re1 = '.*?'; # Non-greedy match on filler
        $re2 = '(page)';  # Word 1
        $re3 = '(\\d+)';  # Integer Number 1
        if ($c = preg_match_all('/' . $re1 . $re2 . $re3 . '/is', $txt, $matches)) {
            $word1 = $matches[1][0];
            $int1 = $matches[2][0];
            $int1 = preg_replace( "/^[0]{0,6}/", '', $int1 );
            return $int1;
        }
        else {
            return 'null';
        }
    }

    /**
     * Return the cover file of an item.
     *
     * @return File|null
     */
    public static function getCoverFile($item)
    {
        $imagesFiles = BookReader::getImagesFiles($item);
        foreach ($imagesFiles as $key => $file) {
            $re1 = '.*?';
            $re2 = '(titre)';
            $re3 = '(\\d+)';
            if ($c = preg_match_all ('/' . $re1 . $re2 . $re3 . '/is', $file->original_filename, $matches)) {
                return $file;
            }
        }
    }

    /**
     * Return the title leaf for javascript.
     *
     * @return string
     */
    public static function getTitleLeaf($item)
    {
        $imagesFiles = BookReader::getImagesFiles($item);
        foreach ($imagesFiles as $key => $file) {
            $re1 = '.*?'; // Non-greedy match on filler
            $re2 = '(titre)'; // Word 1
            $re3 = '.*?'; // Non-greedy match on filler
            $re4 = '(01)'; // Any Single Digit 1
            if ($c = preg_match_all('/' . $re1 . $re2 . $re3 . $re4 . '/is', $file->original_filename, $matches)) {
                return 'br.titleLeaf = ' . $key;
            }
        }
    }

    /**
     * Return the xml file attached to an item, if any, to allow search inside.
     *
     * @return string|boolean
     *   The path to the xml file or false.
     */
    public static function getDataForSearch($item) {
        $xml_file = false;

        set_loop_records('files', $item->getFiles());
        if (has_loop_records('files')) {
            foreach (loop('files') as $file) {
                if (strtolower($file->getExtension()) == 'xml') {
                    $xml_file = escapeshellarg(FILES_DIR . DIRECTORY_SEPARATOR . 'original' . DIRECTORY_SEPARATOR . $file->filename);
                    break;
                }
            }
        }

        return $xml_file;
    }

    /**
     * This function returns the answer to a query with coordinates of the
     * matching words.
     */
    public static function fulltextAction()
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
        // uasort($list, array(BookReader, 'compareStrings'));

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
}
