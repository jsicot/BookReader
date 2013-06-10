<?php

/**
 * Prepare a string for html display.
 *
 * @return string
 */
function htmlkarakter($string)
{
    $string = strip_tags($string);
    $string = html_entity_decode($string, ENT_QUOTES);
    $string = utf8_encode($string);
    $string = htmlspecialchars_decode($string);
    $string = addslashes($string);

    return $string;
}

/**
 * Return the xml file attached to an item, if any, to allow search inside.
 *
 * @return string|boolean
 *   The path to the xml file or false.
 */
function BrSearchAvailable($item = null)
{
    if ($item == null) {
        $item = get_current_record('item');
    }

    $xml_file = false;

    set_loop_records('files', $item->getFiles());
    if (has_loop_records('files')) {
        foreach (loop('files') as $file) {
            if (strtolower(pathinfo($file->original_filename, PATHINFO_EXTENSION)) == 'xml') {
                $xml_file = escapeshellarg(FILES_DIR . DIRECTORY_SEPARATOR . $file->filename);
            }
        }
    }

    return $xml_file;
}

/**
 * Get the filename of a file from an original filename.
 *
 * @return string
 *   The filename of the file with the original filename.
 */
function findArchiveName($original_filename)
{
    $db = get_db();
    $query = $db
        ->select()
        ->from(array($db->Files), 'filename')
        ->where('original_filename = ?', $original_filename);

    return $db->fetchOne($query);
}

/**
 * Get the id of the current bookreader item.
 *
 * @return integer
 *   The id of the current bookreader item.
 */
function id_bookreader_item()
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
function ui_bookreader_item()
{
    $view = get_view();
    return $view->bookreaderCurrItem->ui;

}

/**
 * Get the webpath of the images of the bookreader.
 *
 * @return string
 *   The web path of the images folder.
 */
function bookreader_img_dir()
{
    return WEB_PLUGIN . '/BookReader/views/shared/images/';
}

/**
 * Get an array of all images (not non image files) of an item.
 *
 * @return array
 *   Array of filenames associated to original filenames.
 */
function bookreader_images_filenames($item = null)
{
    if ($item == null) {
        $item = get_current_record('item');
    }

    $list = array();
    set_loop_records('files', $item->getFiles());
    foreach (loop('files') as $file) {
        if ($file->hasThumbnail()) {
            $list[$file->filename] = $file->original_filename;
        }
    }
    return $list;
}

/**
 * Count the number of image files attached to an item.
 *
 * @return integer
 *   Number of images attached to an item.
 */
function bookreader_item_numLeafs($item = null)
{
    return count(bookreader_images_filenames($item));
}

/**
 * Get an array of the number, label, witdh and height of image file of an item.
 *
 * @return array
 *   Array of the number, label, witdh and height of image file of an item.
 */
function bookreader_images_data($item = null)
{
    if ($item == null) {
        $item = get_current_record('item');
    }

    //retrieve image files from the item
    $list = array();
    $supportedFormats = array(
        'jpeg' => 'JPEG Joint Photographic Experts Group JFIF format',
        'jpg' => 'Joint Photographic Experts Group JFIF format',
        'png' => 'Portable Network Graphics',
        'gif' => 'Graphics Interchange Format',
    );
    // Set the regular expression to match selected/supported formats.
    $supportedFormatRegEx = '/\.'.implode('|', array_keys($supportedFormats)).'$/';

    set_loop_records('files', $item->getFiles());
    foreach (loop('files') as $file) {
        if ($file->hasThumbnail()) {
            if (preg_match($supportedFormatRegEx, $file->filename)) {
                $list[$file->filename] = $file->original_filename;
            }
        }
    }
    // Sorting by original filename if needed, or keep original attached order.
    // uasort($list, 'cmp');

    $nums = array();
    $labels = array();
    $widths = array();
    $heights = array();
    $j = 1;
    foreach($list as $key => $image) {
        $pathImg = FILES_DIR . DIRECTORY_SEPARATOR . 'fullsize' . DIRECTORY_SEPARATOR . $key;
        list($width, $height, $type, $attr) = getimagesize($pathImg);
        $nums[] = $j;
        $labels[] = label_pg($image); //array of images label
        $widths[] = $width; //array of images width
        $heights[] = $height; //array of images height
        $j++;
    }

    return array(
        $nums,
        $labels,
        $widths,
        $heights,
    );
}

/**
 * Get the page label from a string, generally the last word of a filename.
 *
 * @return string
 *   Label of the page, or 'null' if none.
 */
function label_pg($txt)
{
    $re1 = '.*?'; # Non-greedy match on filler
    $re2 = '(page)';  # Word 1
    $re3 = '(\\d+)';  # Integer Number 1
    if ($c = preg_match_all('/' . $re1 . $re2 . $re3 . '/is', $txt, $matches)) {
        $word1 = $matches[1][0];
        $int1 = $matches[2][0];
        $int1 = preg_replace( "/^[0]{0,6}/", '', $int1 );
        return $int1;
    }
    else {
        return 'null';
    }

}

/**
 * Determine if one variable is greater, equal or lower than another one.
 *
 * @return integer
 *   -1, 0 or 1.
 */
function cmp($a, $b)
{
    if ($a == $b) {
        return 0;
    }
    return ($a < $b) ? -1 : 1;
}

/**
 * Return the image in html format of the cover of the item.
 *
 * @return string
 *   Html code of the image of the cover of the item.
 */
function item_cover($props = array(), $index = 0, $item = null)
{
    if ($item == null) {
        $item = get_current_record('item');
    }

    $list = bookreader_images_filenames($item);
    $defaultProps = array(
        'alt' => html_escape(metadata($item, array('Dublin Core', 'Title'))),
    );

    $img = '';
    foreach ($list as $filename => $original_filename) {
        $props = array_merge($defaultProps, $props);

        // TODO Currently use automatic width.
        $width = @$props['width'];
        $height = @$props['height'];

        $re1 = '.*?';
        $re2 = '(titre)';
        $re3 = '(\\d+)';
        if ($c = preg_match_all ('/' . $re1 . $re2 . $re3 . '/is', $original_filename, $matches)) {
            $img = '<img src="' . WEB_FILES . '/thumbnails/' . $filename . '" ' . _tag_attributes($props) . ' width="auto" height="120" />';
        }
    }

    return $img;
}

/**
 * Return the html code of an array of attributes.
 *
 * @return string
 *   Html code of the attributes.
 *
 * @todo Escape value.
 */
function _tag_attributes($props)
{
    $html = '';
    foreach ($props as $key => $value) {
        $html .= $key . ' "' . $value . '" ';
    }
    return $html;
}

/**
 * Récupére les liens des fichiers de type PDF, DOC, ODT, etc (tous sauf les images).
 *
 * @return string
 *   Html code of links.
 */
function item_PDF($item = null)
{
    if ($item == null) {
        $item = get_current_record('item');
    }

    // Récupération du fichier xml à traiter en fonction de l'id de l'item

    // Extensions supportées
    $pdfMimeTypes = array(
        'application/pdf',
        'application/x-pdf',
        'application/acrobat',
        'text/x-pdf',
        'text/pdf',
        'applications/vnd.pdf',
    );

    // Iterate through the item's files.
    $html = '';
    set_loop_records('files', $item->getFiles());
    foreach (loop('files') as $key => $file) {
        // Embed only those files that end with the selected/supported formats.
        if (in_array($file->mime_type, $pdfMimeTypes)) {
            // Set the document's absolute URL.
            // Note: file_download_uri($file) does not work here. It results
            // in the iPaper error: "Unable to reach provided URL."
            //$documentUrl = WEB_FILES . '/' . $file->filename;
            //$documentUrl = file_download_uri($file);
            $sizefile = formatFileSize($file->size);
            $extension = pathinfo($file->filename, PATHINFO_EXTENSION);
            $fileURL = WEB_FILES . '/original/' . $file->filename;
            $html .= '<div style="clear:both; padding:2px;">';
            $html .= '<a href="' . $fileURL . '" class="download-file">' . $file->original_filename. '</a>';
            $html .= '&nbsp; ('.$extension.' / ' . $sizefile . ')';
            $html .= '</div>';
        }
    }

    return $html;
}

/**
 * Return the title of leaf for bookreader.
 *
 * @return string
 *   Title of leaf for bookreader.
 */
function titleLeaf($item = null)
{
    if ($item == null) {
        $item = get_current_record('item');
    }
    //extensions supportées
    $SupportedFormats = array(
        'jpeg' => 'JPEG Joint Photographic Experts Group JFIF format',
        'jpg' => 'Joint Photographic Experts Group JFIF format',
        'png' => 'Portable Network Graphics',
        'gif' => 'Graphics Interchange Format',
    );
    // Set the regular expression to match selected/supported formats.
    $supportedFormatRegEx = '/\.'.implode('|', array_keys($SupportedFormats)).'$/';

    // Création du tableau
    $list = array();
    $i = 1;
    set_loop_records('files', $item->getFiles());
    foreach (loop('files') as $file) {
        if ($file->hasThumbnail()) {
            if (preg_match($supportedFormatRegEx, $file->filename)) {
                $list[$i] = $file->original_filename;
            }
        }
        $i++;
    }

    // Sorting by original filename if needed, or keep original attached order.
    // sort($list);

    $titleLeaf = '';
    foreach ($list as $key => $value) {
        $re1 = '.*?'; # Non-greedy match on filler
        $re2 = '(titre)';   # Word 1
        $re3 = '.*?';   # Non-greedy match on filler
        $re4 = '(01)';  # Any Single Digit 1
        if ($c = preg_match_all('/' . $re1 . $re2 . $re3 . $re4 . '/is', $value, $matches)) {
            $titleLeaf = 'br.titleLeaf = ' . $key;
        }
    }

    return $titleLeaf;
}

/**
 * Return a file size with the appropriate format of unit.
 *
 * @return string
 *   String of the file size.
 */
function formatFileSize($size)
{
    if ($size < 1024) {
        return $size . ' ' . __('bytes');
    }

    foreach (array(__('KB'), __('MB'), __('GB'), __('TB')) as $unit) {
        $size /= 1024.0;
        if ($size < 10) {
            return sprintf("%.1f" . $unit, $size);
        }
        if ($size < 1024) {
            return (int) $size . ' ' . $unit;
        }
    }
}
