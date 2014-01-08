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
     * Get the id of the current bookreader item.
     *
     * @return integer
     *   The id of the current bookreader item.
     */
    public static function bookreaderCurrItemId()
    {
        $view = get_view();
        return $view->bookreaderCurrItem->id;

    }

    /**
     * Get the ui of the current bookreader item.
     *
     * @return string
     *   The ui of the current bookreader item.
     */
    public static function bookreaderCurrItemUI()
    {
        $view = get_view();
        return $view->bookreaderCurrItem->ui;

    }

    /**
     * Get an array of all images of an item in order to display them with
     * BookReader.
     *
     * @return array
     *   Array of filenames associated to original filenames.
     */
    public static function getImagesFiles($item = null)
    {
        return self::_getFilesForBookreader($item, false);
    }

    /**
     * Get an array of all images of an item in order to display them with
     * BookReader.
     *
     * @return array
     *   Array of filenames associated to original filenames.
     */
    public static function getNonImagesFiles($item = null)
    {
        return self::_getFilesForBookreader($item, true);
    }

    /**
     * Count the number of image files attached to an item.
     *
     * @return integer
     *   Number of images attached to an item.
     */
    public static function itemLeafsCount($item = null)
    {
        return count(self::getImagesFiles($item));
    }

    /**
     * Get an array of the number, label, witdh and height of each image file of
     *  an item.
     *
     * @return array
     *   Array of the number, label, witdh and height of image file of an item.
     */
    public static function imagesData($item = null, $imageType = 'fullsize')
    {
        if (is_null($item)) {
            $item = get_current_record('item');
        }

        $j = 0;
        $nums = array();
        $labels = array();
        $widths = array();
        $heights = array();
        $imagesFiles = self::getImagesFiles($item);
        foreach($imagesFiles as $file) {
            $pathImg = FILES_DIR . DIRECTORY_SEPARATOR . $imageType . DIRECTORY_SEPARATOR . ($imageType == 'original' ? $file->filename : $file->getDerivativeFilename());
            list($width, $height, $type, $attr) = getimagesize($pathImg);
            $nums[] = ++$j;
            $labels[] = self::getLabelPage($file);
            $widths[] = $width;
            $heights[] = $height;
        }

        return array(
            $nums,
            $labels,
            $widths,
            $heights,
        );
    }

    /**
     * Get an array of the witdhs and heights of each original image file of an
     * item.
     *
     * @return array
     *   Array of witdh and height of image file of an item.
     */
    public static function originalImagesSizes($item = null)
    {
        if (is_null($item)) {
            $item = get_current_record('item');
        }

        $widths = array();
        $heights = array();
        $imagesFiles = self::getImagesFiles($item);
        foreach($imagesFiles as $file) {
            $pathImg = FILES_DIR . DIRECTORY_SEPARATOR . 'original' . DIRECTORY_SEPARATOR . $file->filename;
            list($width, $height, $type, $attr) = getimagesize($pathImg);
            $widths[] = $width;
            $heights[] = $height;
        }

        return array(
            $widths,
            $heights,
        );
    }

    /**
     * Returns the derivative size to use for the current image, depending on
     * the scale.
     *
     * @return string
     *   Derivative name of the size.
     */
    public static function sendImage($scale, $item = null)
    {
        if (is_null($item)) {
            $item = get_current_record('item');
        }

        return BookReader_Custom::sendImage($query, $item);
    }

    /**
     * Get links to non-images files of the item.
     *
     * @return string
     *   Html code of links.
     */
    public static function linksToNonImages($item = null)
    {
        if (is_null($item)) {
            $item = get_current_record('item');
        }

        $html = '';

        $nonImagesFiles = self::getNonImagesFiles($item);
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
            $html .= '<a href="' . $file->getWebPath() . '" class="download-file">' . $file->original_filename. '</a>';
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
        if (is_null($item)) {
            $item = get_current_record('item');
        }

        return BookReader_Custom::hasDataForSearch($item);
    }

    /**
     * Returns answers to a query.
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
    public static function searchFulltext($query, $item = null)
    {
        if (is_null($item)) {
            $item = get_current_record('item');
        }

        return BookReader_Custom::searchFulltext($query, $item);
    }

    /**
     * Prepares data to be highlighted via javascript.
     *
     * @see BookReader_IndexController::fulltextAction()
     *
     * @return array
     *   Array of matches with coordinates.
     */
    public static function highlightFiles($texts)
    {
        return BookReader_Custom::highlightFiles($texts);
    }

    /**
     * Get the page label from a string, generally the last word of a filename.
     *
     * @return string
     *   Label of the page, or 'null' if none.
     */
    public static function getLabelPage($file = null)
    {
        if (is_null($file)) {
            $file = get_current_record('file');
        }

        return BookReader_Custom::getLabelPage($file);
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
        if (is_null($item)) {
            $item = get_current_record('item');
        }

        $img = '';

        $file = BookReader_Custom::getCoverFile($item);
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

            $img = '<img src="' . WEB_FILES . '/thumbnails/' . $file->getDerivativeFilename() . '" ' . self::_tagAttributes($props) . ' width="auto" height="120" />';
        }

        return $img;
    }

    /**
     * Return the cover file of the item.
     *
     * @return file
     *   Object file of the cover.
     */
    public static function getCoverFile($item = null)
    {
        if (is_null($item)) {
            $item = get_current_record('item');
        }

        return BookReader_Custom::getCoverFile($item);
    }

    /**
     * Return the title of leaf for bookreader.
     *
     * @return string
     *   Title of leaf for bookreader.
     */
    public static function titleLeaf($item = null)
    {
        if (is_null($item)) {
            $item = get_current_record('item');
        }

        return BookReader_Custom::getTitleLeaf($item);
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
    private static function compareFilenames($a, $b)
    {
        return strcmp($a->original_filename, $b->original_filename);
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
     * Get an array of all images (or all non-images) of an item in order to
     * display them with BookReader.
     *
     * @param Item $item
     * @param boolean $invert
     *
     * @return array
     *   Array files.
     */
    protected static function _getFilesForBookreader($item = null, $invert = false)
    {
        static $filesForBookreader = array();

        if (is_null($item)) {
            $item = get_current_record('item');
        }

        if (!isset($filesForBookreader[$item->id])) {
            $filesForBookreader[$item->id] = array(
                'images' => array(),
                'non-images' => array(),
            );

            $supportedFormats = array(
                'jpeg' => 'JPEG Joint Photographic Experts Group JFIF format',
                'jpg' => 'Joint Photographic Experts Group JFIF format',
                'png' => 'Portable Network Graphics',
                'gif' => 'Graphics Interchange Format',
            );
            // Set the regular expression to match selected/supported formats.
            $supportedFormatRegEx = '/\.' . implode('|', array_keys($supportedFormats)) . '$/';

            // Retrieve image files from the item.
            set_loop_records('files', $item->getFiles());
            foreach (loop('files') as $file) {
                if ($file->hasThumbnail() && preg_match($supportedFormatRegEx, $file->filename)) {
                    $filesForBookreader[$item->id]['images'][] = $file;
                }
                else {
                    $filesForBookreader[$item->id]['non-images'][] = $file;
                }
            }

            // Sorting by original filename or keep attachment order.
            if (get_option('bookreader_sorting_mode')) {
                uasort($filesForBookreader[$item->id]['images'], array('BookReader', 'compareFilenames'));
                uasort($filesForBookreader[$item->id]['non-images'], array('BookReader', 'compareFilenames'));
            }
            // Reset keys, because the important is to get files by order.
            $filesForBookreader[$item->id]['images'] = array_values($filesForBookreader[$item->id]['images']);
            $filesForBookreader[$item->id]['non-images'] = array_values($filesForBookreader[$item->id]['non-images']);
        }

        return $invert
            ? $filesForBookreader[$item->id]['non-images']
            : $filesForBookreader[$item->id]['images'];
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
}
