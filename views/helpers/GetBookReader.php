<?php
/**
 * Helper to display a Book Reader.
 */
class BookReader_View_Helper_GetBookReader extends Zend_View_Helper_Abstract
{
    /**
     * Get the specified BookReader.
     *
     * @param array $args Associative array of optional values:
     *   - (integer|Item) item
     *   - (integer) page: set the page to be shown when including the iframe,
     *   - (boolean) embed_functions: include buttons (Zoom, Search...).
     *   - (integer) mode_page: allow to display 1 or 2 pages side-by-side.
     *   - (integer) part: can be used to display the specified part of a book.
     *
     * @return string. The html string corresponding to the BookReader.
     */
    public function getBookReader($args = array())
    {
        if (!isset($args['item'])) {
            $item = get_current_record('item');
        }
        elseif ($args['item'] instanceof Item) {
            $item = $args['item'];
        }
        else {
            $item = get_record_by_id('Item', (integer) $args['item']);
        }

        if (empty($item)) {
            return '';
        }

        $part = empty($args['part'])? 0 : (integer) $args['part'];
        $page = empty($args['page']) ? '0' : $args['page'];

        // Currently, all or none functions are enabled.
        $embed_functions = isset($args['embed_functions'])
            ? $args['embed_functions']
            : get_option('bookreader_embed_functions');

        $mode_page = isset($args['mode_page'])
            ? $args['mode_page']
            : get_option('bookreader_mode_page');

        // Build url of the page with iframe.
        $queryParams = array();
        if ($part > 1) $queryParams['part'] = $part;
        if (empty($embed_functions)) $queryParams['ui'] = 'embed';
        $url = absolute_url(array('id' => $item->id), 'bookreader_viewer', $queryParams);
        $url .= '#';
        $url .= empty($page) ? '' : 'page/n' . $page . '/';
        $url .= 'mode/' . $mode_page . 'up';

        $class = get_option('bookreader_class');
        if (!empty($class)) {
            $class = ' class="' . $class . '"';
        }
        $width = get_option('bookreader_width');
        if (!empty($width)) {
            $width = ' width="' . $width . '"';
        }
        $height = get_option('bookreader_height');
        if (!empty($height)) {
            $height = ' height="' . $height . '"';
        }

        $html = '<div><iframe src="' . $url . '"' . $class . $width . $height . ' frameborder="0"></iframe></div>';
        return $html;
    }
}
