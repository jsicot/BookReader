<?php
/**
 * Simple helper for BookReader.
 *
 * Page numbers or labels are saved in Dublin Core Title of each file.
 *
 * Full text is saved in Item Type Metadata Text of item.
 *
 * @todo To be finished.
 * @todo Save text in Dublin Core Description of each file.
 *
 * @package BookReader
 */
class BookReader_Creator_Simple extends BookReader_Creator
{
    /**
     * Get the list of numbers of pages of an item.
     *
     * The page number is the name of a page of a file, like "6" or "XIV".
     *
     * This function is used to get quickly all page numbers of an item. If the
     * page number is empty, the label page will be used. If there is no page
     * number, use 'null', so the label in viewer will be the page index + 1.
     *
     * In this example, numbers are saved in Dublin Core:Title as 'Page 1', etc.
     * in metadata of each file.
     *
     * @see getPageLabels()
     *
     * @return array of strings
     */
    public function getPageNumbers()
    {
        if (empty($this->_item)) {
            return;
        }

        $leaves = $this->getLeaves();
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
                    if (strtolower(substr($txt[0]->text, 0, $firstSpace)) == 'page') {
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
     * in metadata of each file.
     *
     * @see getPageNumbers()
     *
     * @return array of strings
     */
    public function getPageLabels()
    {
        if (empty($this->_item)) {
            return;
        }

        $leaves = $this->getLeaves();
        $labels = array();
        foreach ($leaves as $leaf) {
            if (empty($leaf)) {
                $label = '';
            }
            else {
                $txt = $leaf->getElementTexts('Dublin Core', 'Title');
                if (empty($txt)) {
                    $label = '';
                }
                else {
                    // Don't add a label if the label is like a page number.
                    $firstSpace = strrpos($txt[0]->text, ' ');
                    $label = strtolower(substr($txt[0]->text, 0, $firstSpace)) == 'page'
                        ? ''
                        : $txt[0]->text;
                }
            }
            $labels[] = $label;
        }
        return $labels;
    }

    /**
     * Check if there are data for search.
     *
     * In this example, search is done inside "Item Type Metadata:Text".
     *
     * @return boolean
     *   True if there are data for search, else false.
     */
    public function hasDataForSearch()
    {
        if (empty($this->_item)) {
            return;
        }

        $this->_itemType = $this->_item->getItemType();
        if (empty($this->_itemType) || $this->_itemType->name !== 'Text') {
            return false;
        }

        return $this->_item->hasElementText('Item Type Metadata', 'Text');
    }

    /**
     * Returns answers to a full text query.
     *
     * This search is case insensitive and without punctuation. Diacritics are
     * not converted.
     *
     * @uses BookReader_Creator::_alnumString()
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
    public function searchFulltext($query)
    {
        if (empty($this->_item)) {
            return;
        }

        $minimumQueryLength = 4;
        $maxResult = 10;
        // Warning: PREG_OFFSET_CAPTURE is not Unicode safe.
        // So, if needed, uncomment the following line.
        // mb_internal_encoding("UTF-8");

        // Simplify checks, because arrays are 0-based.
        $maxResult--;

        $results = array();

        // Normalize query because the search occurs inside a normalized text.
        $cleanQuery = $this->_alnumString($query);
        if (strlen($cleanQuery) < $minimumQueryLength) {
            return $results;
        }

        // Prepare regex: replace all spaces to allow any characters, except
        // those accepted (letters, numbers and symbols).
        $pregQuery = '/' . str_replace(' ', '[\p{C}\p{M}\p{P}\p{Z}]*', preg_quote($cleanQuery)) . '/Uui';

        // For this example, search is done at item level only.
        $text = $this->_item->getElementTexts('Item Type Metadata', 'Text');
        if (!empty($text) && preg_match_all($pregQuery, $text[0]->text, $matches, PREG_OFFSET_CAPTURE)) {
            // For this example, the answer is found in the first image only.
            $files = $this->_item->Files;
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
    public function highlightFiles($textsToHighlight)
    {
        if (empty($this->_item)) {
            return;
        }

        $imageType = 'fullsize';
        $beforeContext = 120;
        $afterContext = 120;
        // If needed, uncomment the following line.
        // mb_internal_encoding("UTF-8");

        $results = array();
        foreach ($textsToHighlight as $file_id => $data) {
            $file = get_record_by_id('file', $file_id);
            $pageIndex = $this->getPageIndex($file);
            $pathImg = FILES_DIR . DIRECTORY_SEPARATOR . $imageType . DIRECTORY_SEPARATOR . ($imageType == 'original' ? $file->filename : $file->getDerivativeFilename());
            list($width, $height, $type, $attr) = getimagesize($pathImg);

            // Get the ratio between original widths and heights and fullsize
            // ones, because highlight is done first on a fullsize image, but
            // data are set for original image.
            $pathImg = FILES_DIR . DIRECTORY_SEPARATOR . 'original' . DIRECTORY_SEPARATOR . $file->filename;
            list($originalWidth, $originalHeight, $type, $attr) = getimagesize($pathImg);
            $ratio = $height / $originalHeight;

            // Text is needed only to get context.
            $this->_item = get_record_by_id('item', $file->item_id);
            $text = $this->_item->getElementTexts('Item Type Metadata', 'Text');
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
}
