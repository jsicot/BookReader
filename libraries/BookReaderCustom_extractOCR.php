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
 *
 * @note La fonction de recherche ne fonctionne plus avec la dernière version
 * du fait que la recherche est désormais distincte du surlignage.
 * Néanmoins, cela peut être contourné du fait que la fonction highlightFiles()
 * a désormais pour paramètre $item également. On peut donc faire la recherche
 * et le surlignage directement dans cette fonction et ne rien renvoyer dans la
 * fonction searchFulltext().
 *
 * @internal Limites de la recherche :
 * - La recherche se fait via grep ou regex, alors que c'est du xml.
 * - La recherche est ligne par ligne et échoue si les mots sont sur
 * plusieurs lignes.
 */

/**
 * Custom helpers for BookReader.
 *
 * @package BookReader
 */
class BookReader_Custom
{
    /**
     * Get the list of numbers of pages of an item.
     *
     * The page number is the name of a page of a file, like "6" or "XIV".
     * If "null" is returned, the label in viewer will be the page index + 1.
     *
     * This function is used to get quickly all page numbers of an item.
     *  If the page number is empty, the label page will be used. If there is no
     * page number, use 'null'.
     *
     * @see getPageLabels()
     *
     * @return array of strings
     */
    public static function getPageNumbers($item)
    {
        $leaves = BookReader::getLeaves($item);
        $numbers = array();
        foreach ($leaves as $leaf) {
            if (empty($leaf)) {
                $number = '';
            }
            else {
                $file = &$leaf;
                $txt = $file->original_filename;

                $re1 = '.*?'; # Non-greedy match on filler
                $re2 = '(page)';  # Word 1
                $re3 = '(\\d+)';  # Integer Number 1
                if ($c = preg_match_all('/' . $re1 . $re2 . $re3 . '/is', $txt, $matches)) {
                    $word1 = $matches[1][0];
                    $int1 = $matches[2][0];
                    $int1 = preg_replace( "/^[0]{0,6}/", '', $int1 );
                    $number = $int1;
                }
                else {
                    $number = 'null';
                }
            }
            $numbers[] = $number;
        }
        return $numbers;
    }

    /**
     * Returns the derivative size to use for the current image, depending on
     * the scale.
     *
     * @return string
     *   Derivative name of the size.
     */
    public static function getSizeType($scale, $item)
    {
        switch ($scale) {
            case ($scale < 1.1): return 'original';
            case ($scale < 1.4): return 'fullsize';
            case ($scale < 6): return 'fullsize';
            case ($scale < 16): return 'thumbnail';
            case ($scale < 32): return 'thumbnail';
        }
        return 'fullsize';
    }

    /**
     * Check if there are data for search.
     *
     * @return boolean
     *   True if there are data for search, else false.
     */
    public static function hasDataForSearch($item)
    {
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

        return (boolean) $xml_file;
    }

    /**
     * Returns answers to a query.
     *
     * @return array
     *   Result can be returned by leaf index or by file id. The custom
     *   highlightFiles() function should use the same.
     *   Associative array of leaf indexes or file ids as keys and an array
     *   values for each result in the page (words and start position):
     * array(
     *   leaf index = array(
     *     array(
     *       'answer' => answer, findable in original text,
     *       'position' => position of the answer in original text,
     *     ),
     *   ),
     * );
     */
    public static function searchFulltext($query, $item, $part)
    {
        $minimumQueryLength = 3;
        $maxResult = 10;

        // Simplify checks, because arrays are 0-based.
        $maxResult--;

        $results = array();

        // Normalize query because the search occurs inside a normalized text.
        $cleanQuery = self::_alnumString($query);
        if (strlen($cleanQuery) < $minimumQueryLength) {
                return $results;
        }

        $queryWords = explode(' ', $cleanQuery);
        $countQueryWords = count($queryWords);

        if ($countQueryWords > 1) $queryWords[] = $cleanQuery;

        $iResult = 0;
        $list = array();
        set_loop_records('files', $item->getFiles());
        foreach (loop('files') as $file) {
            if (strtolower(pathinfo($file->original_filename, PATHINFO_EXTENSION)) == 'xml') {
                $xml_file = FILES_DIR . DIRECTORY_SEPARATOR . 'original' . DIRECTORY_SEPARATOR . $file->filename;
            }
            elseif ($file->hasThumbnail()) {
                if (preg_match('/(jpg|jpeg|png|gif)/', $file->filename)) {
                    $list[$file->filename] = $file->original_filename;
                }
            }
        }

        $widths = array();
        $heights = array();
        foreach ($list as $key => $image) {
            $pathImg = FILES_DIR . DIRECTORY_SEPARATOR . 'fullsize' . DIRECTORY_SEPARATOR . $key;
            list($width, $height, $type, $attr) = getimagesize($pathImg);
            $widths[] = $width;
            $heights[] = $height;
        }

        if ($xml_file) {
            $results = array();
            if (file_exists($xml_file)) {
                $string = file_get_contents($xml_file);
                $string = preg_replace('/\s{2,}/ui', ' ', $string);
                $string = preg_replace('/<\/?b>/ui', '', $string);
                $string = preg_replace('/<\/?i>/ui', '', $string);
                $string = str_replace('<!doctype pdf2xml system "pdf2xml.dtd">', '<!DOCTYPE pdf2xml SYSTEM "pdf2xml.dtd">', $string);
                $xml =  simplexml_load_string($string);
                if(!$xml) die('{"Error":"Invalid XML!"}');
                $result = array();

                // We need to store the name of the function to be used
                // for string length. mb_strlen() is better (especially
                // for diacrictics) but not available on all systems so
                // sometimes we need to use the default strlen()
                $strlen_function = "strlen";
                if (function_exists('mb_strlen'))
                {
                        $strlen_function = "mb_strlen";
                }

                foreach( $xml->page as $page) {
                    foreach($page->attributes() as $a => $b) {
                        if ($a == 'height') $page_height = (string)$b ;
                        if ($a == 'width')  $page_width = (string)$b ;
                        if ($a == 'number') $page_number = (string)$b ;
                    }
                    $t = 1;
                    foreach( $page->text as $row) {
                        $boxes = array();
                        $zone_text = strip_tags($row->asXML());
                        foreach($queryWords as $q) {
                            if($strlen_function($q) >= 3) {
                                if(preg_match("/$q/Uui", $zone_text) > 0) {
                                    foreach($row->attributes() as $a => $b) {
                                        if ($a == 'top') $zone_top = (string)$b;
                                        if ($a == 'left') $zone_left = (string)$b;
                                        if ($a == 'height') $zone_height = (string)$b;
                                        if ($a == 'width') $zone_width = (string)$b;
                                    }
                                    $zone_right = ($page_width - $zone_left - $zone_width);
                                    $zone_bottom = ($page_height - $zone_top - $zone_height);

                                    $zone_width_char = strlen($zone_text);
                                    $word_start_char = stripos($zone_text, $q);
                                    $word_width_char = strlen($q);

                                    $word_left = $zone_left + ( ($word_start_char * $zone_width) / $zone_width_char);
                                    $word_right = $word_left + ( ( ( $word_width_char + 2) * $zone_width) / $zone_width_char );

                                    $word_left = round($word_left * $widths[$page_number] / $page_width);
                                    $word_right = round( $word_right * $widths[$page_number] / $page_width);

                                    $word_top = round($zone_top * $heights[$page_number] / $page_height);
                                    $word_bottom = round($word_top + ( $zone_height * $heights[$page_number] / $page_height ));

                                    $boxes[] = array(
                                        'r' => $word_right,
                                        'l' => $word_left,
                                        'b' => $word_bottom,
                                        't' => $word_top,
                                        'page' => $page_number,
                                    );

                                    $zone_text = str_ireplace($q, '{{{' . $q . '}}}', $zone_text);
                                    $result['text'] = $zone_text;
                                    $result['par'] = array();
                                    $result['par'][] = array(
                                        't' => $zone_top,
                                        'r' => $zone_right,
                                        'b' => $zone_bottom,
                                        'l' => $zone_left,
                                        'page' => $page_number,
                                        'boxes' => $boxes,
                                    );

                                    $results[] = $result;
                                }
                                $t += 1;
                            }
                        }
                    }
                }

            } else {
                die('{"Error":"PDF to XML conversion failed!"}');
            }
        }
        return $results;
    }

    /**
     * Prepares data to be highlighted via javascript.
     *
     * @see BookReader_IndexController::fulltextAction()
     *
     * @return array
     *   Array of matches with coordinates.
     */
    public static function highlightFiles($textsToHighlight, $item, $part)
    {
        return $textsToHighlight;
    }

    protected static function _alnumString($string)
    {
        $string = preg_replace('/[^\p{L}\p{N}\p{S}]/u', ' ', $string);
        return trim(preg_replace('/\s+/', ' ', $string));
    }
}
