<?php
/**
 * This code is designed to display and to search inside full text imported
 * from ocr refNum xml data.
 *
 * @see http://bibnum.bnf.fr/refNum
 *
 * @internal Limites de la recherche :
 * - La recherche échoue si les mots sont sur plusieurs pages (utiliser
 * la recherche générale dans ce cas).
 */

/**
 * refNum helper for BookReader.
 *
 * @package BookReader
 */
class BookReader_Creator_RefnumOCR extends BookReader_Creator
{
    /**
     * Get an array of all leaves (or all non-leaves) of an item in order to
     * display them with BookReader.
     *
     * Difference with the default method is that multiple views and missing
     * pages are added when needed, so the parity remains clean.
     *
     * @param boolean $invert
     *
     * @return array
     *   Array of files or nulls.
     */
    protected function _get_list_of_leaves($invert = false)
    {
        if (empty($this->_item)) {
            return;
        }

        if (is_null($this->_leaves)) {
            $this->_leaves = array();
            $this->_nonleaves = array();

            $supportedFormats = array(
                'jpeg' => 'JPEG Joint Photographic Experts Group JFIF format',
                'jpg' => 'Joint Photographic Experts Group JFIF format',
                'png' => 'Portable Network Graphics',
                'gif' => 'Graphics Interchange Format',
                'tif' => 'Tagged Image File Format',
                'tiff' => 'Tagged Image File Format',
            );
            // Set the regular expression to match selected/supported formats.
            $supportedFormatRegEx = '/\.' . implode('|', array_keys($supportedFormats)) . '$/i';

            // Retrieve image files from the item.
            set_loop_records('files', $this->_item->getFiles());
            foreach (loop('files') as $file) {
                if ($file->hasThumbnail() && preg_match($supportedFormatRegEx, $file->filename)) {
                    $this->_leaves[] = $file;
                }
                else {
                    $this->_nonleaves[] = $file;
                }
            }

            // Sorting by original filename or keep attachment order.
            if (get_option('bookreader_sorting_mode')) {
                $this->sortFilesByOriginalName($this->_leaves);
                $this->sortFilesByOriginalName($this->_nonleaves);
            }

            // Insert missing pages and multiple views.
            $this->_leaves = $this->_complete_list_of_leaves();

            // Reset keys, because the important is to get files by order.
            $this->_leaves = array_values($this->_leaves);
            $this->_nonleaves = array_values($this->_nonleaves);
        }

        return $invert
            ? $this->_nonleaves
            : $this->_leaves;
    }

    /**
     * Insert missing pages and multiple views.
     *
     * Of course, there are only digitalized leaves here (we don't use the
     * original scan data or refNum infos).
     *
     * In used source, the order of pages integrates or not missing ones.
     *
     * This process doesn't include multiple successive missing pages.
     *
     * @todo Use a simple query from the item.
     *
     * @return array
     *   Array of files or nulls.
     */
    private function _complete_list_of_leaves()
    {
        $leaves = $this->_leaves;
        if (count($leaves) <= 1) {
            return $leaves;
        }

        // Quick preload infos of all leaves and insert missing or non
        // digitalized leaves.
        $infos_leaves = array();
        $first = true;
        foreach ($leaves as $file) {
            // Get infos of current file.
            $ordre = $file-> getElementTexts('refNum', 'Numéro d’ordre');
            $ordre = $ordre ? $ordre[0]->text : '';
            // If a number is missing, process can't be done, so keep originals.
            if (empty($ordre)) {
                return $leaves;
            }
            // We don't use the type of page, because here, the list contains
            // only the digitalized leaves.
            $multiple = $file-> getElementTexts('refNum', 'Nombre vues');
            $multiple = $multiple ? $multiple[0]->text : '';
            $position = $file->getElementTexts('refNum', 'Position de la page');
            $position = $position ? $position[0]->text : '';

            // Insert missing or non digitalized leaves as null.
            // Two methods are possible: check of order or check of position and
            // multiple views. As the order doesn't include systematicaly the
            // missing page, we use the second method.

            // The first leaf is always good.
            if ($first) {
                $first = false;
            }
            else {
                if (!$multiple && !empty($position) && ($position == $previous_position)) {
                    $infos_leaves[] = array(
                        'file' => null,
                        'ordre' => null,
                        'multiple' => null,
                        'position' => ($previous_position == 'Gauche') ? 'Droite' : 'Gauche',
                    );
                }
            }
            $previous_position = $position;

            // Keep infos of current leaf.
            $infos_leaves[] = array(
                'file' => $file,
                'ordre' => $ordre,
                'multiple' => $multiple,
                'position' => $position,
            );
        }

        // Now, the parity is clean and all infos are available.
        // So we can insert the facing leaf in case of multiple views of a page.
        // The facing leaf can be a missing page.
        // No static is used, because multiple are rare and very rarely more
        // than two.
        $result = array();
        foreach ($infos_leaves as $key => &$info) {
            // Add current leaf.
            $result[] = $info['file'];
            if ($info['multiple']) {
                $current = $key;
                // Check if this is the last multiple view, because no insert is
                // needed for it.
                if ($current + 1 <= count($infos_leaves)
                        && $infos_leaves[$current + 1]['multiple'] != $info['multiple']
                    ) {
                    // continue;
                }
                // If the multiple view is on the left side, the facing leaf
                // will be the first view of the next page.
                elseif ($info['position'] == 'Gauche') {
                    // Check for next pages.
                    while (++$current < count($infos_leaves)) {
                        if ($infos_leaves[$current]['multiple'] != $info['multiple']) {
                            $result[] = $infos_leaves[$current]['file'];
                            break;
                        }
                    }
                }
                // Else facing leaf is the last view of previous page.
                else {
                    // Check for previous pages.
                    while (--$current >= 0) {
                        if ($infos_leaves[$current]['multiple'] != $info['multiple']) {
                            $result[] = $infos_leaves[$current]['file'];
                            break;
                        }
                    }
                }
            }
        }

        // Add "Dos" cover in the first or last position, if present.
        if (count($result) > 1) {
            $first = $result[0]->getElementTexts('Dublin Core', 'Title');
            $first = $first ? $first[0]->text : '';
            $last = $result[count($result) - 1]->getElementTexts('Dublin Core', 'Title');
            $last = $last ? $last[0]->text : '';
            if ($first == 'Dos' && $last != 'Dos') {
                $result[] = $result[0];
            }
            elseif ($first != 'Dos' && $last == 'Dos') {
                array_unshift($result, $result[count($result) - 1]);
            }
        }

        return $result;
    }

    /**
     * Get the list of indexes of pages for an item.
     *
     * This function is used to get quickly all page indexes of an item. First
     * page should be 0 if document starts from right, and 1 if document starts
     * from left. Use null for a missing page.
     *
     * @return array of integers
     */
    public function getPageIndexes()
    {
        if (empty($this->_item)) {
            return;
        }

        $leaves = $this->getLeaves();
        $indexes = array();
        $first = true;
        foreach($leaves as $key => $leaf) {
            if ($first === true) {
                $position = $leaf->getElementTexts('refNum', 'Position de la page');
                $first = ($position && $position[0]->text == 'Gauche') ? 1 : 0;
            }
            $indexes[] = $first + $key;
        }
        return $indexes;
    }

    /**
     * Get the list of numbers of pages of an item.
     *
     * The page number is the name of a page of a file, like "6" or "XIV".
     *
     * This function is used to get quickly all page numbers of an item. If the
     * page number is empty, the label page will be used. If there is no page
     * number, use 'null', so the label in viewer will be the page index + 1.
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
                $txt = $leaf->getElementTexts('refNum', 'Numéro de page');
                $number = $txt ? $txt[0]->text : '';
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
        // Don't add a label if this is the same than the number.
        $numbers = $this->getPageNumbers();
        $labels = array();
        foreach ($leaves as $key => $leaf) {
            if (empty($leaf)) {
                $label = __('Blank page');
            }
            else {
                $txt = $leaf->getElementTexts('Dublin Core', 'Title');
                if (empty($txt)) {
                    $label = '';
                }
                else {
                    $label = ($txt[0]->text == __('Page') . ' ' . $numbers[$key])
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
     * @return integer
     *   Index for bookreader.
     */
    public function getTitleLeaf()
    {
        if (empty($this->_item)) {
            return;
        }

        $db = get_db();

        $element = $db->getTable('Element')->findByElementSetNameAndElementName('refNum', 'Type de page');

        $bind = array(
            $element->id,
            $this->_item->id,
        );

        // Order: "Première page à afficher" before "Page de titre".
        $sql = "
            SELECT files.id
            FROM {$db->File} files
                JOIN {$db->ElementText} element_texts
                    ON element_texts.record_id = files.id
                        AND element_texts.record_type = 'File'
                        AND element_texts.element_id = ?
            WHERE files.item_id = ?
                AND (element_texts.text = 'Première page à afficher'
                    OR element_texts.text = 'Page de titre')
            ORDER BY
                element_texts.text DESC
            LIMIT 1
        ";
        $result = $db->fetchOne($sql, $bind);

        if ($result) {
            $file = get_record_by_id('File', $result);
            return $this->getLeafIndex($file);
        }

        return 0;
    }

    /**
     * Check if there are data for search.
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

        // TODO Use one query.
        foreach ($this->_item->Files as $file) {
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
        $maxResult = 100;
        // Warning: PREG_OFFSET_CAPTURE is not Unicode safe.
        // So, if needed, uncomment the following line.
        mb_internal_encoding("UTF-8");

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
        // Search results.
        $iResult = 0;
        $leaves = $this->getLeaves();
        // Look for each page of the item.
        foreach ($leaves as $key => $file) {
            if (empty($file)) {
                continue;
            }
            $textAuto = $file->getElementTexts('OCR', 'Texte auto');
            if (!empty($textAuto)) {
                $textAuto = $textAuto[0]->text;
                // Look for all answers on this page.
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
                        $results[$key] = $result;
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
    public function highlightFiles($textsToHighlight)
    {
        if (empty($this->_item)) {
            return;
        }

        $imageType = 'fullsize';
        $beforeContext = 120;
        $afterContext = 120;
        // If needed, uncomment the following line.
        mb_internal_encoding('UTF-8');

        $leaves = $this->getLeaves();
        $results = array();
        foreach ($textsToHighlight as $key => $data) {
            $file = $leaves[$key];
            $pageIndex = $this->getPageIndex($file);
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
            if (!isset($motAmot[0])) {
                continue;
            }
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

                $words = mb_substr($textAuto, $startPosition, $endPosition - $startPosition);
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
                                'index' => $pageIndex,
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
