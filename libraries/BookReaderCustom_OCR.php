<?php
/**
 * @file
 *   All functions of this file must be adapted to your needs, except names and
 *   parameters.
 *
 * This code is designed to display and to search inside full text imported
 * from ocr refNum xml data.
 *
 * @see http://bibnum.bnf.fr/refNum
 *
 * @todo Integrate this in the configuration form.
 * @todo Use an abstract model class.
 * @todo Use Omeka 2.0 search functions.
 *
 * @internal Limites de la recherche :
 * - La recherche échoue si les mots sont sur plusieurs pages (utiliser
 * la recherche générale dans ce cas).
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
     * @todo Currently, the page label should be a number.
     *
     * @return string
     *   Label of the page, or 'null' if none.
     */
    public static function getLabelPage($file)
    {
        if (is_null($file)) {
            return '';
        }

        $txt = $file->getElementTexts('Dublin Core', 'Title');
        if (empty($txt)) {
            $txt = 'null';
        }
        else {
            $txt = substr($txt[0]->text, strrpos($txt[0]->text, ' '));
            $txt = (int) $txt;
        }

        return $txt;
    }

    /**
     * Return the cover file of an item.
     * Here, the cover file is the first image file of an item.
     *
     * @return File|null
     */
    public static function getCoverFile($item)
    {
        $imagesFiles = BookReader::getImagesFiles($item);
        return reset($imagesFiles);
    }

    /**
     * Return the title leaf for javascript.
     * Here, return the first leaf.
     *
     * @return string
     */
    public static function getTitleLeaf($item)
    {
        return 'br.titleLeaf = ' . '0';
    }

    /**
     * Returns the derivative size to use for the current image, depending on
     * the scale.
     *
     * @return string
     *   Derivative name of the size.
     */
    public static function sendImage($scale, $item)
    {
        switch ($scale) {
            case ($scale < 1): return 'original';
            case ($scale < 2): return 'fullsize';
            case ($scale < 4): return 'fullsize';
            case ($scale < 8): return 'fullsize';
            case ($scale < 16): return 'thumbnails';
            case ($scale < 32): return 'thumbnails';
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
        $itemType = $item->getItemType();
        if (empty($itemType) || $itemType->name !== 'Text') {
            return false;
        }

        // TODO Use one query.
        foreach ($item->Files as $file) {
            if ($file->hasElementText('OCR', 'Texte auto')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns answers to a full text query.
     *
     * This search is case insensitive and without punctuation. Diacritics are
     * not converted.
     *
     * @see BookReader_Custom::_alnumString()
     *
     * @todo Use one query search or xml search or Zend_Search_Lucene.
     *
     * @return array
     *   Associative array of file ids as keys and an array values for each
     *   result in the page (words and start position):
     * array(
     *   file_id = array(
     *      array(
     *        'answer' => answer, findable in original text,
     *        'position' => position of the answer in original text,
     *      ),
     *   ),
     * );
     */
    public static function searchFulltext($query, $item)
    {
        $minimumQueryLength = 4;
        $maxResult = 10;

        // Simplify checks, because arrays are 0-based.
        $maxResult--;

        $results = array();

        // Normalize query because the search occurs inside a normalized text.
        $cleanQuery = self::_alnumString($query);
        if (strlen($cleanQuery) < $minimumQueryLength) {
            return $results;
        }

        // Prepare query.
        $queryWords = explode(' ', $cleanQuery);
        $countQueryWords = count($queryWords);
        // Prepare regex: replace all spaces to allow any characters, except
        // those accepted (letters, numbers and symbols).
        $pregQuery = '/' . str_replace(' ', '[\p{C}\p{M}\p{P}\p{Z}]*', preg_quote($cleanQuery)) . '/Uui';

        // Search results.
        $iResult = 0;
        $imagesFiles = BookReader::getImagesFiles($item);
        // Look for each page of the item.
        foreach ($imagesFiles as $keyFile => $file) {
            $textAuto = $file->getElementTexts('OCR', 'Texte auto');
            if (!empty($textAuto)) {
                $textAuto = $textAuto[0]->text;
                // Look for all answers on this page.
                // Warning: PREG_OFFSET_CAPTURE is not Unicode safe.
                if (preg_match_all($pregQuery, $textAuto, $matches)) {
                    $result = array();
                    $offset = 0;
                    foreach ($matches[0] as $match) {
                        $offsetTextAuto = mb_substr($textAuto, $offset);
                        $position = mb_strpos($offsetTextAuto, $match);
                        $result[] = array(
                            'answer' => $match,
                            'position' => $offset + $position,
                        );
                        $offset += $position + mb_strlen($match);

                        // All results are kept on each image, even if number
                        // of results is greater than the max.
                        $iResult++;
                    }

                    if (!empty($result)) {
                       $results[$file->id] = $result;
                    }
                    if ($iResult > $maxResult) {
                        break;
                    }
                }
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
    public static function highlightFiles($textsToHighlight)
    {
        $imageType = 'fullsize';
        $beforeContext = 120;
        $afterContext = 120;

        $results = array();
        foreach ($textsToHighlight as $file_id => $data) {
            $file = get_record_by_id('file', $file_id);
            $label = self::getLabelPage($file);
            $pathImg = FILES_DIR . DIRECTORY_SEPARATOR . $imageType . DIRECTORY_SEPARATOR . ($imageType == 'original' ? $file->filename : $file->getDerivativeFilename());
            list($width, $height, $type, $attr) = getimagesize($pathImg);

            // Get the ratio between original widths and heights and fullsize
            // ones, because highlight is done first on a fullsize image, but
            // data are set for original image.
            $pathImg = FILES_DIR . DIRECTORY_SEPARATOR . 'original' . DIRECTORY_SEPARATOR . $file->filename;
            list($originalWidth, $originalHeight, $type, $attr) = getimagesize($pathImg);
            $ratio = $height / $originalHeight;

            // Text auto is needed only to get context.
            $textAuto = $file->getElementTexts('OCR', 'Texte auto');
            $textAuto = $textAuto[0]->text;
            $lengthTextAuto = mb_strlen($textAuto);
            // Convert words to array in order to get next cell simply (and avoid
            // the integer key as string bug).
            $motAmot = $file->getElementTexts('OCR', 'Mot-à-mot');
            $motAmot = json_decode($motAmot[0]->text, true);
            $motAmot = $motAmot['String'];
            $offsetStartPosition = 0;
            foreach ($data as $dataToHighlight) {
                $answer = $dataToHighlight['answer'];
                $position = $dataToHighlight['position'];
                $length = mb_strlen($answer);

                // Get position of first/last character of first/last full
                // word, because positions are recorded by word.
                // Warning: PREG_OFFSET_CAPTURE is not Unicode safe.
                $regex = '/' . '[\p{C}\p{M}\p{P}\p{Z}]{1}[\p{L}\p{N}\p{S}]*' . preg_quote($answer) . '/ui';
                $haystack = mb_substr($textAuto, $offsetStartPosition, $position + $length - $offsetStartPosition);
                $startPosition = (preg_match($regex, $haystack, $match) == 0)
                    ? $offsetStartPosition
                    : $offsetStartPosition + mb_strpos($haystack, $match[0]) + 1;

                // Set offset for the next answer in the same page.
                $offsetStartPosition = $startPosition + $length;

                // Quick check.
                if (!isset($motAmot[$startPosition]) || empty($motAmot[$startPosition])) {
                    continue;
                }

                $regex = '/' . '[\p{L}\p{N}\p{S}]*[\p{C}\p{M}\p{P}\p{Z}]{1}' . '/ui';
                $haystack = mb_substr($textAuto, $position + $length);
                $endPosition = (preg_match($regex, $haystack, $match) == 0)
                    ? mb_strlen($textAuto)
                    : $position + $length + mb_strlen($match[0]) - 1;

                $fullAnswer = mb_substr($textAuto, $startPosition, $endPosition - $startPosition);

                // Get the context of the answer.
                $startContext = ($startPosition - $beforeContext) < 0
                    ? 0
                    : $startPosition - $beforeContext;
                $lengthContext = $beforeContext + $length + $afterContext;
                $context = ($startContext === 0 ? '' : '...' )
                    . mb_substr($textAuto, $startContext, $lengthContext)
                    . ($startContext + $lengthContext > $lengthTextAuto ? '' : '...');

                // Create the par zone.
                // TODO Currently, the par zone is not really used by
                // BookReader, so we take the first word coordinates as zone
                // coordinates.
                $mot = $motAmot[$startPosition];
                $zone_left = $mot['x'];
                $zone_top = $mot['y'];
                $zone_width = $mot['w'];
                $zone_height = $mot['h'];
                $zone_right = $zone_left + $zone_width;
                $zone_bottom = $zone_top + $zone_height;

                // Creates boxes for each word.
                $boxes = array();
                $words = $fullAnswer;

                // Set the internal pointer to current position before getting
                //  values.
                while (list($key, $mot) = each($motAmot)) {
                    if ($key == $startPosition) {
                         prev($motAmot);

                        while (!empty($mot)) {
                            $word_left = $mot['x'];
                            $word_top = $mot['y'];
                            $word_width = $mot['w'];
                            $word_height = $mot['h'];
                            $word_right = $word_left + $word_width;
                            $word_bottom = $word_top + $word_height;

                            $boxes[] = array(
                                't' => round($word_top * $ratio),
                                'l' => round($word_left * $ratio),
                                'b' => round($word_bottom * $ratio),
                                'r' => round($word_right * $ratio),
                                'page' => $label,
                            );

                            // Prepare words for next loop.
                           $words = trim(mb_substr($words, strlen($mot['c'])));
                            $mot = next($motAmot);
                            if ($mot === false || empty($words)) {
                                break;
                            }
                        }
                        break;
                    }
                }

                // Aggregate zones to prepare current result.
                $result = array();
                $result['text'] = $context;
                $result['par'] = array();
                $result['par'][] = array(
                    't' => round($zone_top * $ratio),
                    'r' => round($zone_right * $ratio),
                    'b' => round($zone_bottom * $ratio),
                    'l' => round($zone_left * $ratio),
                    'page' => $label,
                    'boxes' => $boxes,
                );

                $results[] = $result;
            }
        }

        return $results;
    }

    /**
     * Returns a cleaned  string.
     *
     * Removes trailing spaces and anything else, except letters, numbers and
     * symbols.
     *
     * @param string $string The string to clean.
     *
     * @return string
     *   The cleaned string.
     */
    protected static function _alnumString($string)
    {
        $string = preg_replace('/[^\p{L}\p{N}\p{S}]/u', ' ', $string);
        return trim(preg_replace('/\s+/', ' ', $string));
    }
}
