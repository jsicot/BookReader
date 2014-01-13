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
     * Get the page index of a file in the list of images.
     *
     * Generally, the index is the order of the file attached to the item, but
     * it can be another one for right to left languages, or when it's necessary
     * to display an image more than once or to insert a special page. This is
     * specially useful to keep the parity of pages (left / right) when blanck
     * pages are not digitalized or when a page has more than one views.
     *
     * @return integer|null
     *   Index of the page.
     */
    public static function getPageIndex($file)
    {
        if (empty($file)) {
            return null;
        }

        $indexes = self::getPageIndexes($file->getItem());
        $leaves = self::getLeaves($file->getItem());
        foreach($leaves as $key => $leaf) {
            if ($leaf && $leaf->id == $file->id) {
                return $key;
            }
        }
        return null;
    }

    /**
     * Get the list of indexes of pages for an item.
     *
     * This function is used to get quickly all page indexes of an item. First
     * page should be 0 if document starts from right, and 1 if document starts
     * from left. Use null for a missing page.
     *
     * @see getPageIndex()
     *
     * @return array of integers
     */
    public static function getPageIndexes($item)
    {
        $leaves = BookReader::getLeaves($item);
        $indexes = array();
        foreach($leaves as $key => $leaf) {
            $indexes[] = empty($leaf) ? null : $key;
        }
        return $indexes;
    }

    /**
     * Get the page number or the name of a page of a file, like "6" or "XIV".
     * If "null" is returned, the label in viewer will be the page index + 1.
     *
     * This example uses the Dublin Core Title of the file. If this title start
     * with 'Page' like in "Page 6" or "Page XIV", it extracts "6" or "XIV" and
     * return it.
     *
     * @see getPageLabel()
     *
     * @return string
     *   Number of the page, empty to use the page label, or 'null' if none.
     */
    public static function getPageNumber($file)
    {
        if (empty($file)) {
            return '';
        }

        $txt = $file->getElementTexts('Dublin Core', 'Title');
        if (empty($txt)) {
            $txt = 'null';
        }
        else {
            $firstSpace = strrpos($txt[0]->text, ' ');
            if (substr($txt[0]->text, 0, $firstSpace) == 'Page') {
                $txt = trim(substr($txt[0]->text, $firstSpace + 1));
                $txt = ((int) $txt == $txt)
                    ? $txt
                    : json_encode($txt);
            }
            else {
                $txt = '';
            }
        }

        return $txt;
    }

    /**
     * Get the list of numbers of pages of an item.
     *
     * This function is used to get quickly all page numbers of an item.
     *
     * In this example, the process is not optimized and this is only a wrapper
     * for getPageNumber().
     *
     * @see getPageNumber()
     *
     * @return array of strings
     */
    public static function getPageNumbers($item)
    {
        $leaves = BookReader::getLeaves($item);
        $numbers = array();
        foreach ($leaves as $file) {
            $numbers[] = self::getPageNumber($file);
        }
        return $numbers;
    }

    /**
     * Get the page label of a file, like "4th Cover" or "Faux titre".
     *
     * This function is first used for pages without pagination, like cover,
     * summary, title page, index, inserted page, planches, etc. If there is a
     * page number, this label is not needed, but it can be used to add a
     * specific information ("Page XIV : Illustration").
     *
     * @see getPageNumber()
     *
     * @return string
     *   Label of the page, if needed.
     */
    public static function getPageLabel($file)
    {
        if (empty($file)) {
            return '';
        }

        $txt = $file->getElementTexts('Dublin Core', 'Title');
        if (empty($txt)) {
            $txt = '';
        }
        else {
            $firstSpace = strrpos($txt[0]->text, ' ');
            $txt = (substr($txt[0]->text, 0, $firstSpace) == 'Page')
                ? ''
                : $txt[0]->text;
        }

        return $txt;
    }

    /**
     * Get the list of labels of pages of an item.
     *
     * This function is used to get quickly all page labels of an item.
     *
     * In this example, the process is not optimized and this is only a wrapper
     * for getPageLabel().
     *
     * @see getPageLabel()
     *
     * @return array of strings
     */
    public static function getPageLabels($item)
    {
        $leaves = BookReader::getLeaves($item);
        $labels = array();
        foreach ($leaves as $file) {
            $labels[] = self::getPageLabel($file);
        }
        return $labels;
    }

    /**
     * Return the cover file of an item (the leaf to display as a thumbnail).
     *
     * Here, the cover file is the first image file of an item.
     *
     * @return File|null
     */
    public static function getCoverFile($item)
    {
        $leaves = BookReader::getLeaves($item);
        return reset($leaves);
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
    public static function highlightFiles($textsToHighlight)
    {
        $imageType = 'fullsize';
        $beforeContext = 120;
        $afterContext = 120;
        // If needed, uncomment the following line.
        // mb_internal_encoding("UTF-8");

        $results = array();
        foreach ($textsToHighlight as $file_id => $data) {
            $file = get_record_by_id('file', $file_id);
            $label = self::getPageLabel($file);
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
