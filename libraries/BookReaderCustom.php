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
     * Get the list of numbers of pages of an item.
     *
     * The page number is the name of a page of a file, like "6" or "XIV".
     * If "null" is returned, the label in viewer will be the page index + 1.
     *
     * This function is used to get quickly all page numbers of an item.
     *  If the page number is empty, the label page will be used. If there is no
     * page number, use 'null'.
     *
     * In this example, numbers are saved in Dublin Core:Title as 'Page 1', etc.
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
                $txt = $leaf->getElementTexts('Dublin Core', 'Title');
                if (empty($txt)) {
                    $number = 'null';
                }
                else {
                    $firstSpace = strrpos($txt[0]->text, ' ');
                    if (substr($txt[0]->text, 0, $firstSpace) == 'Page') {
                        $txt = trim(substr($txt[0]->text, $firstSpace + 1));
                        $number = ((int) $txt == $txt)
                            ? $txt
                            : json_encode($txt);
                    }
                    else {
                        $number = '';
                    }
                }
            }
            $numbers[] = $number;
        }
        return $numbers;
    }

    /**
     * Get the list of labels of pages of an item.
     *
     * This function is used to get quickly all page labels of an item.
     *
     * A label is used first for pages without pagination, like cover, summary,
     * title page, index, inserted page, planches, etc. If there is a page
     * number, this label is not needed, but it can be used to add a specific
     * information ("Page XIV : Illustration").
     *
     * In this example, numbers are saved in Dublin Core:Title as 'Cover', etc.
     *
     * @see getPageNumbers()
     *
     * @return array of strings
     */
    public static function getPageLabels($item)
    {
        $leaves = BookReader::getLeaves($item);
        $labels = array();
        foreach ($leaves as $leaf) {
            if (empty($leaf)) {
                $label = '';
            }
            else {
                $txt = $item->getElementTexts('Dublin Core', 'Title');
                if (empty($txt)) {
                    $label = '';
                }
                else {
                    // Don't add a label if the label is like a page number.
                    $firstSpace = strrpos($txt[0]->text, ' ');
                    $label = (substr($txt[0]->text, 0, $firstSpace) == 'Page')
                        ? ''
                        : $txt[0]->text;
                }
            }
            $labels[] = $label;
        }
        return $labels;
    }

    /**
     * Return index of the title leaf.
     *
     * Here, the title is the first leaf of an item.
     *
     * @return integer
     *   Index for bookreader.
     */
    public static function getTitleLeaf($item)
    {
        return 0;
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

        return $item->hasElementText('Item Type Metadata', 'Text');
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
        $minimumQueryLength = 4;
        $maxResult = 10;
        // Warning: PREG_OFFSET_CAPTURE is not Unicode safe.
        // So, if needed, uncomment the following line.
        // mb_internal_encoding("UTF-8");

        // Simplify checks, because arrays are 0-based.
        $maxResult--;

        $results = array();

        // Normalize query because the search occurs inside a normalized text.
        $cleanQuery = self::_alnumString($query);
        if (strlen($cleanQuery) < $minimumQueryLength) {
            return $results;
        }

        // Prepare regex: replace all spaces to allow any characters, except
        // those accepted (letters, numbers and symbols).
        $pregQuery = '/' . str_replace(' ', '[\p{C}\p{M}\p{P}\p{Z}]*', preg_quote($cleanQuery)) . '/Uui';

        // For this example, search is done at item level only.
        $text = $item->getElementTexts('Item Type Metadata', 'Text');
        if (!empty($text) && preg_match_all($pregQuery, $text[0]->text, $matches, PREG_OFFSET_CAPTURE)) {
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
    public static function highlightFiles($textsToHighlight, $item, $part)
    {
        $imageType = 'fullsize';
        $beforeContext = 120;
        $afterContext = 120;
        // If needed, uncomment the following line.
        // mb_internal_encoding("UTF-8");

        $results = array();
        foreach ($textsToHighlight as $file_id => $data) {
            $file = get_record_by_id('file', $file_id);
            $pageIndex = BookReader::getPageIndex($file);
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
            $text = $item->getElementTexts('Item Type Metadata', 'Text');
            $text = $text[0]->text;
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
                    'index' => $pageIndex,
                );

                // Aggregate zones to prepare current result.
                $result = array();
                $result['text'] = $context;
                $result['par'] = array();
                $result['par'][] = array(
                    't' => round($zone_top * $ratio),
                    'l' => round($zone_left * $ratio),
                    'b' => round($zone_bottom * $ratio),
                    'r' => round($zone_right * $ratio),
                    'index' => $pageIndex,
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
