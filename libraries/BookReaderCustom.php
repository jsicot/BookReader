<?php
/**
 * @file
 *   All functions of this file must be adapted to your needs, except names and
 *   parameters.
 *
 * @todo Integrate this in the configuration form.
 * @todo Use an abstract model class.
 * @todo Use Omeka 2.0 search functions.
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

        $txt = metadata($file, array('Dublin Core', 'Title'));
        if (!$txt) {
            $txt = 'null';
        }
        else {
            $txt = substr($txt, strrpos($txt, ' '));
            $txt = (int) $txt;
        }

        return $txt;
    }

    /**
     * Return the cover file of an item. Here, the cover is the first image.
     * Here, the cover file is the first image file of an item.
     *
     * @return File|null
     */
    public static function getCoverFile($item)
    {
        $imagesFiles = BookReader::getImagesFiles($item);
        return $imagesFiles[0];
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

        $metadata = metadata($item, array('Item Type Metadata', 'Text'));

        return !empty($metadata);
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

        // For this example, search is done at item level only.
        $text = metadata($item, array('Item Type Metadata', 'Text'));
        // Warning: PREG_OFFSET_CAPTURE is not Unicode safe.
        if (preg_match_all($pregQuery, $text, $matches, PREG_OFFSET_CAPTURE)) {
            // For this example, the answer is found in the first image only.
            $files = $item->Files;
            $file = $files[0];

            $results[$file->id] = array();
            foreach ($matches as $match) {
                $results[$file->id][] = array(
                    'answer' => $match[0][0],
                    'position' => $match[0][1],
                );
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

            // Text is needed only to get context.
            $item = get_record_by_id('item', $file->item_id);
            $text = metadata($item, array('Item Type Metadata', 'Text'));
            $lengthText = mb_strlen($text);

            // For this example, one third size rectangle is drawn on the
            // middle of the image.
            foreach ($data as $dataToHighlight) {
                $answer = $dataToHighlight['answer'];
                $position = $dataToHighlight['position'];
                $length = mb_strlen($answer);

                // Get the context of the answer.
                $context = '...' . $answer . '...';

                // Create the par zone.
                // TODO Currently, the par zone is not really used by
                // BookReader, so we take the first word coordinates as zone
                // coordinates.
                $zone_left = $originalWidth / 3;
                $zone_top = $originalHeight / 3;
                $zone_right = $originalWidth * 2 / 3;
                $zone_bottom = $originalHeight * 2 / 3;

                // Creates boxes for each word.
                $boxes = array();
                $word_left = $originalWidth / 3;
                $word_top = $originalHeight / 3;
                $word_right = $originalWidth * 2 / 3;
                $word_bottom = $originalHeight * 2 / 3;

                $boxes[] = array(
                    't' => round($word_top * $ratio),
                    'l' => round($word_left * $ratio),
                    'b' => round($word_bottom * $ratio),
                    'r' => round($word_right * $ratio),
                    'page' => $label,
                );

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
