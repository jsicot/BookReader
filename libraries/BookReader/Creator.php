<?php
/**
 * Helper to create a BookReader.
 *
 * @todo Use Omeka 2.0 search functions.
 *
 * @package BookReader
 */
abstract class BookReader_Creator
{
    protected $_item;
    protected $_ui;
    protected $_part;

    // List of files that are pages of the item.
    protected $_leaves;
    // List of files that are not pages of the item.
    protected $_nonleaves;

    public function __construct($item = null)
    {
        $this->setItem($item);
    }

    /**
     * Set item.
     *
     * @param integer|Item $item
     */
    public function setItem($item)
    {
        if (empty($item)) {
            $this->_item = null;
        }
        elseif ($item instanceof Item) {
            $this->_item = $item;
        }
        else {
            $this->_item = get_record_by_id('Item', (integer) $item);
        }
    }

    public function setUI($ui)
    {
        $this->_ui = ($ui == 'embed') ? 'embed' : '';
    }

    public function setPart($part)
    {
        $this->_part = (integer) $part;
    }

    public function getItem()
    {
        return $this->_item;
    }

    public function getUI()
    {
        return $this->_ui;
    }

    public function getPart()
    {
        return $this->_part;
    }

    /**
     * Get an array of all images of an item in order to display them with
     * BookReader.
     *
     * @return array
     *   Array of filenames associated to original filenames.
     */
    public function getLeaves()
    {
        return $this->_get_list_of_leaves(false);
    }

    /**
     * Get an array of all non-images of an item in order to display them as
     * links.
     *
     * @return array
     *   Array of filenames associated to original filenames.
     */
    public function getNonLeaves()
    {
        return $this->_get_list_of_leaves(true);
    }

    /**
     * Get an array of all leaves (or all non-leaves) of an item in order to
     * display them with BookReader.
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
            // Reset keys, because the important is to get files by order.
            $this->_leaves = array_values($this->_leaves);
            $this->_nonleaves = array_values($this->_nonleaves);
        }

        return $invert
            ? $this->_nonleaves
            : $this->_leaves;
    }

    /**
     * Count the number of image files attached to an item.
     *
     * @return integer
     *   Number of images attached to an item.
     */
    public function itemLeafsCount()
    {
        if (empty($this->_item)) {
            return;
        }
        return count($this->getLeaves());
    }

    /**
     * Get the list of indexes of pages for an item.
     *
     * This function is used to get quickly all page indexes of an item. First
     * page should be 0 if document starts from right, and 1 if document starts
     * from left. Use null for a missing page.
     *
     * By default, indexes are simply a list of numbers starting from 0.
     *
     * @return array of integers
     */
    public function getPageIndexes()
    {
        if (empty($this->_item)) {
            return;
        }

        // Start from 0 by default.
        $leaves = $this->getLeaves();
        $indexes = array();
        foreach($leaves as $key => $leaf) {
            $indexes[] = empty($leaf) ? null : $key;
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
     * No page number by default.
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
        return array_fill(0, count($leaves), 'null');
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
     * No label by default.
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
        return array_fill(0, count($leaves), '');
    }

    /**
     * Return the cover file of an item (the leaf to display as a thumbnail).
     *
     * This data can be saved in the base in order to speed the display.
     *
     * @return file
     *   Object file of the cover.
     */
    public function getCoverFile()
    {
        if (empty($this->_item)) {
            return;
        }

        $leaves = $this->getLeaves();
        $index = $this->getTitleLeaf();
        return isset($leaves[$index]) ? $leaves[$index] : reset($leaves);
    }

    /**
     * Return index of the first leaf to display by BookReader.
     *
     * @return integer
     *   Index for bookreader.
     */
    public function getTitleLeaf()
    {
        if (empty($this->_item)) {
            return;
        }

        return 0;
    }

    /**
     * Get the index of a file in the list of leaves.
     *
     * @return integer|null
     */
    public function getLeafIndex($file = null)
    {
        if (empty($file)) {
            $file = get_current_record('file');
            if (empty($file)) {
                return null;
            }
        }

        $leaves = $this->getLeaves();
        foreach($leaves as $key => $leaf) {
            if ($leaf && $leaf->id == $file->id) {
                return $key;
            }
        }

        return null;
    }

    /**
     * Get the page index of a file in the list of images.
     *
     * @see getPageIndexes()
     *
     * @return integer
     *   Index of the page.
     */
    public function getPageIndex($file = null)
    {
        return $this->_getLeafData('PageIndexes', $file);
    }

    /**
     * Get the page number of a file.
     *
     * @see getPageNumbers()
     *
     * @return string
     *   Number of the page, empty to use the page label, or 'null' if none.
     */
    public function getPageNumber($file = null)
    {
        return $this->_getLeafData('PageNumbers', $file);
    }

    /**
     * Get the page label of a file, like "4th Cover" or "Faux titre".
     *
     * @see getPageLabels()
     *
     * @return string
     *   Label of the page, if needed.
     */
    public function getPageLabel($file = null)
    {
        return $this->_getLeafData('PageLabels', $file);
    }

    /**
     * Get a specific data of a file in list of leaves.
     *
     * @return integer|null
     */
    protected function _getLeafData($dataType, $file = null)
    {
        $key = $this->getLeafIndex($file);
        if (is_null($key)) {
            return null;
        }
        $callback = 'get' . $dataType;
        $array = $this->$callback();
        return isset($array[$key]) ? $array[$key] : null;
    }

    /**
     * Get an array of the widths and heights of each image file of an item.
     *
     * This data can be saved in the base in order to speed the display.
     *
     * @return array
     *   Array of width and height of image files of an item.
     */
    public function getImagesSizes($imageType = 'fullsize')
    {
        if (empty($this->_item)) {
            return;
        }

        $widths = array();
        $heights = array();
        $leaves = $this->getLeaves();
        foreach ($leaves as $file) {
            // The size of a missing page is calculated by javascript from the
            // size of the verso of the current page or from the first page.
            if (empty($file)) {
                $widths[] = null;
                $heights[] = null;
            }
            else {
                // Don't use the webpath to avoid the transfer through server.
                // TODO WARNING: Image type is not the image path, except for
                // original and fullsize...
                $pathImg = FILES_DIR . DIRECTORY_SEPARATOR . $imageType . DIRECTORY_SEPARATOR . ($imageType == 'original' ? $file->filename : $file->getDerivativeFilename());
                list($width, $height, $type, $attr) = getimagesize($pathImg);
                $widths[] = $width;
                $heights[] = $height;
            }
        }

        return array(
            $widths,
            $heights,
        );
    }

    /**
     * Get an array of the number, label, witdh and height of each image file of
     *  an item. Individual data are json encoded.
     *
     * @return array
     *   Array of the json encoded index, number, label, width and height of
     * images (leaves) files of an item.
     */
    public function imagesData($imageType = 'fullsize')
    {
        if (empty($this->_item)) {
            return;
        }

        // Some arrays need to be encoded in json for javascript. This function
        // produces a lighter array.
        $json_encode_value = function($txt) {
            return (empty($txt) || (string) (integer) $txt == $txt)
                ? $txt
                : json_encode($txt);
        };

        $indexes = $this->getPageIndexes();
        $numbers = array_map($json_encode_value, $this->getPageNumbers());
        $labels = array_map($json_encode_value, $this->getPageLabels());
        list($widths, $heights) = $this->getImagesSizes($imageType);

        return array(
            $indexes,
            $numbers,
            $labels,
            $widths,
            $heights,
        );
    }

    /**
     * Return the image in html format of the cover of the item.
     *
     * @todo Put it in a custom library.
     *
     * @return string
     *   Html code of the image of the cover of the item.
     */
    public function itemCover($props = array(), $index = 0)
    {
        if (empty($this->_item)) {
            return;
        }

        $file = $this->getCoverFile();

        $img = '';
        if ($file) {
            $title = $this->_item->getElementTexts('Dublin Core', 'Title');
            $title = empty($title) ? '' : $title[0]->text;
            $defaultProps = array(
                'alt' => html_escape($title),
            );

            $props = array_merge($defaultProps, $props);

            // TODO Currently use automatic width.
            $width = @$props['width'];
            $height = @$props['height'];

            $img = '<img src="' . $file->getWebPath('thumbnail') . '" ' . $this->_tagAttributes($props) . ' width="auto" height="120" />';
        }

        return $img;
    }

    /**
     * Returns the derivative size to use for the current image, depending on
     * the scale.
     *
     * This default is correct for all digitalized normal books.
     *
     * @return string
     *   Derivative name of the size.
     */
    public function getSizeType($scale)
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
     * Get links to non-images files of the item.
     *
     * @return string
     *   Html code of links.
     */
    public function linksToNonImages()
    {
        if (empty($this->_item)) {
            return;
        }

        $html = '';
        $nonImagesFiles = $this->getNonLeaves();
        foreach ($nonImagesFiles as $file) {
            // Set the document's absolute URL.
            // Note: file_download_uri($file) does not work here. It results
            // in the iPaper error: "Unable to reach provided URL."
            //$documentUrl = WEB_FILES . '/' . $file->filename;
            //$documentUrl = file_download_uri($file);
            $sizefile = $this->_formatFileSize($file->size);
            $extension = pathinfo($file->original_filename, PATHINFO_EXTENSION);
            //$type = $file->mime_browser;
            $html .= '<li>';
            $html .= '<div style="clear:both; padding:2px;">';
            $html .= '<a href="' . $file->getWebPath() . '" class="download-file">' . $file->original_filename . '</a>';
            $html .= '&nbsp; (' . $extension . ' / ' . $sizefile . ')';
            $html .= '</div>'; // Bug when PHP_EOL is added.
            $html .= '</li>';
        }

        return $html;
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

        return false;
    }

   /**
     * Save all BookReader data about an item in a file or in database.
     *
     * @return false|array
     *   False if an error occur, else array of data.
     */
    public function saveData()
    {
        return null;
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
    public function searchFulltext($query)
    {
        return null;
    }

    /**
     * Prepares data to be highlighted via javascript.
     *
     * @see BookReader_IndexController::fulltextAction()
     *
     * @return array
     *   Array of matches with coordinates.
     */
    public function highlightFiles($texts)
    {
        return null;
    }

     /**
     * Return the html code of an array of attributes.
     *
     * @return string
     *   Html code of the attributes.
     *
     * @todo Escape value.
     */
    protected function _tagAttributes($props)
    {
        $html = '';
        foreach ($props as $key => $value) {
            $html .= $key . '="' . $value . '" ';
        }
        return $html;
    }

    /**
     * Return a file size with the appropriate format of unit.
     *
     * @return string
     *   String of the file size.
     */
    protected function _formatFileSize($size)
    {
        if ($size < 1024) {
            return $size . ' ' . __('bytes');
        }

        foreach (array(__('KB'), __('MB'), __('GB'), __('TB')) as $unit) {
            $size /= 1024.0;
            if ($size < 10) {
                return sprintf("%.1f" . ' ' . $unit, $size);
            }
            if ($size < 1024) {
                return (int) $size . ' ' . $unit;
            }
        }
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
    protected function _alnumString($string)
    {
        $string = preg_replace('/[^\p{L}\p{N}\p{S}]/u', ' ', $string);
        return trim(preg_replace('/\s+/', ' ', $string));
    }

    /**
     * Sort an array of files by name.
     *
     * @param array $files By reference array of files.
     * @param boolean $associative Keep association or not.
     *
     * @return void
     */
    public static function sortFilesByOriginalName(&$files, $associative = true)
    {
        // The function determines if one variable is greater, equal or lower
        // than another one. It returns an integer -1, 0 or 1.

        if ($associative) {
            uasort($files, function($file_a, $file_b) {
                return strcmp($file_a->original_filename, $file_b->original_filename);
            });
        }
        else {
            usort($files, function($file_a, $file_b) {
                return strcmp($file_a->original_filename, $file_b->original_filename);
            });
        }
    }
}
