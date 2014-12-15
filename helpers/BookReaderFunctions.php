<?php
/**
 * Helpers for BookReader.
 *
 * @package BookReader
 */

if (!file_exists(get_option('bookreader_custom_library'))) {
    set_option('bookreader_custom_library', realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'BookReaderCustom.php'));
}
require_once get_option('bookreader_custom_library');

class BookReader
{
    /**
     * Get an array of all images of an item in order to display them with
     * BookReader.
     *
     * @return array
     *   Array of filenames associated to original filenames.
     */
    public static function getLeaves($item = null)
    {
        if (empty($item)) {
            $item = get_current_record('item');
        }

        return method_exists('BookReader_Custom', 'getLeaves')
            ? BookReader_Custom::getLeaves($item)
            : self::_get_list_of_leaves($item, false);
    }

    /**
     * Get an array of all non-images of an item in order to display them as
     * links.
     *
     * @return array
     *   Array of filenames associated to original filenames.
     */
    public static function getNonLeaves($item = null)
    {
        if (empty($item)) {
            $item = get_current_record('item');
        }

        return method_exists('BookReader_Custom', 'getNonLeaves')
            ? BookReader_Custom::getNonLeaves($item)
            : self::_get_list_of_leaves($item, true);
    }

    /**
     * Get an array of all leaves (or all non-leaves) of an item in order to
     * display them with BookReader.
     *
     * @param Item $item
     * @param boolean $invert
     *
     * @return array
     *   Array of files or nulls.
     */
    protected static function _get_list_of_leaves($item = null, $invert = false)
    {
        static $leaves = array();

        if (empty($item)) {
            $item = get_current_record('item');
        }

        if (!isset($leaves[$item->id])) {
            $leaves[$item->id] = array(
                'leaves' => array(),
                'non-leaves' => array(),
            );

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
            set_loop_records('files', $item->getFiles());
            foreach (loop('files') as $file) {
                if ($file->hasThumbnail() && preg_match($supportedFormatRegEx, $file->filename)) {
                    $leaves[$item->id]['leaves'][] = $file;
                }
                else {
                    $leaves[$item->id]['non-leaves'][] = $file;
                }
            }

            // Sorting by original filename or keep attachment order.
            if (get_option('bookreader_sorting_mode')) {
                uasort($leaves[$item->id]['leaves'], array('BookReader', 'compareFilenames'));
                uasort($leaves[$item->id]['non-leaves'], array('BookReader', 'compareFilenames'));
            }
            // Reset keys, because the important is to get files by order.
            $leaves[$item->id]['leaves'] = array_values($leaves[$item->id]['leaves']);
            $leaves[$item->id]['non-leaves'] = array_values($leaves[$item->id]['non-leaves']);
        }

        return $invert
            ? $leaves[$item->id]['non-leaves']
            : $leaves[$item->id]['leaves'];
    }

    /**
     * Count the number of image files attached to an item.
     *
     * @return integer
     *   Number of images attached to an item.
     */
    public static function itemLeafsCount($item = null)
    {
        return count(self::getLeaves($item));
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
    public static function getPageIndexes($item = null)
    {
        if (empty($item)) {
            $item = get_current_record('item');
        }

        if (method_exists('BookReader_Custom', 'getPageIndexes')) {
            return BookReader_Custom::getPageIndexes($item);
        }

        // Start from 0 by default.
        $leaves = BookReader::getLeaves($item);
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
    public static function getPageNumbers($item = null)
    {
        if (empty($item)) {
            $item = get_current_record('item');
        }

        return BookReader_Custom::getPageNumbers($item);
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
    public static function getPageLabels($item = null)
    {
        if (empty($item)) {
            $item = get_current_record('item');
        }

        if (method_exists('BookReader_Custom', 'getPageLabels')) {
            return BookReader_Custom::getPageLabels($item);
        }

        // No label by default.
        $leaves = BookReader::getLeaves($item);
        return array_fill(0, count($leaves), '');
    }

    /**
     * Return the cover file of an item (the leaf to display as a thumbnail).
     *
     * @return file
     *   Object file of the cover.
     */
    public static function getCoverFile($item = null)
    {
        if (empty($item)) {
            $item = get_current_record('item');
        }

        if (method_exists('BookReader_Custom', 'getCoverFile')) {
            return BookReader_Custom::getCoverFile($item);
        }

        $leaves = self::getLeaves($item);
        // This could be too:
        // return reset($leaves);
        $index = self::getTitleLeaf($item);
        return isset($leaves[$index]) ? $leaves[$index] : reset($leaves);
    }

    /**
     * Return index of the first leaf to display by BookReader.
     *
     * @return integer
     *   Index for bookreader.
     */
    public static function getTitleLeaf($item = null)
    {
        if (empty($item)) {
            $item = get_current_record('item');
        }

        if (method_exists('BookReader_Custom', 'getTitleLeaf')) {
            return BookReader_Custom::getTitleLeaf($item);
        }

        return 0;
    }

    /**
     * Get the index of a file in the list of leaves.
     *
     * @return integer|null
     */
    public static function getLeafIndex($file = null, $leaves = null)
    {
        if (empty($file)) {
            $file = get_current_record('file');
        }

        if (empty($file)) {
            return null;
        }

        $item = $file->getItem();
        // Use leaves if they are sent as parameter.
        $leaves = is_null($leaves) ? self::getLeaves($item) : $leaves;
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
    public static function getPageIndex($file = null)
    {
        return self::_getLeafData('PageIndexes', $file);
    }

    /**
     * Get the page number of a file.
     *
     * @see getPageNumbers()
     *
     * @return string
     *   Number of the page, empty to use the page label, or 'null' if none.
     */
    public static function getPageNumber($file = null)
    {
        return self::_getLeafData('PageNumbers', $file);
    }

    /**
     * Get the page label of a file, like "4th Cover" or "Faux titre".
     *
     * @see getPageLabels()
     *
     * @return string
     *   Label of the page, if needed.
     */
    public static function getPageLabel($file = null)
    {
        return self::_getLeafData('PageLabels', $file);
    }

    /**
     * Get a specific data of a file in list of leaves.
     *
     * @return integer|null
     */
    protected static function _getLeafData($dataType, $file = null)
    {
        $key = self::getLeafIndex($file);
        if (is_null($key)) {
            return null;
        }
        $callback = 'get' . $dataType;
        $array = self::$callback($file->getItem());
        return isset($array[$key]) ? $array[$key] : null;
    }

    /**
     * Get an array of the widths and heights of each image file of an item.
     *
     * @return array
     *   Array of width and height of image files of an item.
     */
    public static function getImagesSizes($item = null, $imageType = 'fullsize', $leaves = null)
    {
        if (empty($item)) {
            $item = get_current_record('item');
        }

        if (method_exists('BookReader_Custom', 'getImagesSizes')) {
            return BookReader_Custom::getImagesSizes($item, $imageType);
        }

        $widths = array();
        $heights = array();
        // Use leaves if they are sent as parameter.
        $leaves = is_null($leaves) ? self::getLeaves($item) : $leaves;
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
    public static function imagesData($item = null, $imageType = 'fullsize')
    {
        if (empty($item)) {
            $item = get_current_record('item');
        }

        // Some arrays need to be encoded in json for javascript. This function
        // produces a lighter array.
        $json_encode_value = function($txt) {
            return (empty($txt) || (string) (integer) $txt == $txt)
                ? $txt
                : json_encode($txt);
        };

        $indexes = self::getPageIndexes($item);
        $numbers = array_map($json_encode_value, self::getPageNumbers($item));
        $labels = array_map($json_encode_value, self::getPageLabels($item));
        list($widths, $heights) = self::getImagesSizes($item, $imageType);

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
    public static function itemCover($props = array(), $index = 0, $item = null)
    {
        if (empty($item)) {
            $item = get_current_record('item');
        }

        $file = self::getCoverFile($item);

        $img = '';
        if ($file) {
            $title = $item->getElementTexts('Dublin Core', 'Title');
            $title = empty($title) ? '' : $title[0]->text;
            $defaultProps = array(
                'alt' => html_escape($title),
            );

            $props = array_merge($defaultProps, $props);

            // TODO Currently use automatic width.
            $width = @$props['width'];
            $height = @$props['height'];

            $img = '<img src="' . $file->getWebPath('thumbnail') . '" ' . self::_tagAttributes($props) . ' width="auto" height="120" />';
        }

        return $img;
    }

    /**
     * Returns the derivative size to use for the current image, depending on
     * the scale.
     *
     * @return string
     *   Derivative name of the size.
     */
    public static function getSizeType($scale, $item = null)
    {
        if (empty($item)) {
            $item = get_current_record('item');
        }

        if (method_exists('BookReader_Custom', 'getSizeType')) {
            return BookReader_Custom::getSizeType($scale, $item);
        }

        // Default scales.
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
    public static function linksToNonImages($item = null)
    {
        if (empty($item)) {
            $item = get_current_record('item');
        }

        $html = '';
        $nonImagesFiles = self::getNonLeaves($item);
        foreach ($nonImagesFiles as $file) {
            // Set the document's absolute URL.
            // Note: file_download_uri($file) does not work here. It results
            // in the iPaper error: "Unable to reach provided URL."
            //$documentUrl = WEB_FILES . '/' . $file->filename;
            //$documentUrl = file_download_uri($file);
            $sizefile = self::_formatFileSize($file->size);
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
    public static function hasDataForSearch($item = null)
    {
        if (empty($item)) {
            $item = get_current_record('item');
        }

        if (method_exists('BookReader_Custom', 'hasDataForSearch')) {
            return BookReader_Custom::hasDataForSearch($item);
        }
        return false;
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
    public static function searchFulltext($query, $item = null, $part = null)
    {
        if (empty($item)) {
            $item = get_current_record('item');
        }

        if (method_exists('BookReader_Custom', 'searchFulltext')) {
            return BookReader_Custom::searchFulltext($query, $item, $part);
        }
    }

    /**
     * Prepares data to be highlighted via javascript.
     *
     * @see BookReader_IndexController::fulltextAction()
     *
     * @return array
     *   Array of matches with coordinates.
     */
    public static function highlightFiles($texts, $item = null, $part = null)
    {
        if (empty($item)) {
            $item = get_current_record('item');
        }

        if (method_exists('BookReader_Custom', 'highlightFiles')) {
            return BookReader_Custom::highlightFiles($texts, $item, $part);
        }
    }

    /**
     * Prepare a string for html display.
     *
     * @return string
     */
    public static function htmlCharacter($string)
    {
        $string = strip_tags($string);
        $string = html_entity_decode($string, ENT_QUOTES);
        $string = utf8_encode($string);
        $string = htmlspecialchars_decode($string);
        $string = addslashes($string);
        $string = utf8_decode($string);

        return $string;
    }

    /**
     * Determine if one variable is greater, equal or lower than another one.
     *
     * @return integer
     *   -1, 0 or 1.
     */
    public static function compareFilenames($file_a, $file_b)
    {
        return strcmp($file_a->original_filename, $file_b->original_filename);
    }

    /**
     * Return the html code of an array of attributes.
     *
     * @return string
     *   Html code of the attributes.
     *
     * @todo Escape value.
     */
    protected static function _tagAttributes($props)
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
    protected static function _formatFileSize($size)
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
     * Save all BookReader data about an item in a file or in database.
     *
     * @return false|array
     *   False if an error occur, else array of data.
     */
    public static function saveData($item)
    {
        if (method_exists('BookReader_Custom', 'saveData')) {
            return BookReader_Custom::saveData($item);
        }
    }
}
