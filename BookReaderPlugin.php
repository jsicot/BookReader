<?php
/**
 * BookReader
 *
 * This plugin adds Internet Archive BookReader into Omeka. It is used to view
 * books from the Internet Archive online and can also be used to view other
 * books.
 *
 * @copyright Daniel Berthereau, 2013-2014
 * @copyright Julien Sicot, 2011-2013
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 */

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'BookReaderFunctions.php';

/**
 * The Book Reader plugin.
 * @package Omeka\Plugins\BookReader
 */
class BookReaderPlugin extends Omeka_Plugin_AbstractPlugin
{
    /**
     * @var array Hooks for the plugin.
     */
    protected $_hooks = array(
        'install',
        'upgrade',
        'uninstall',
        'initialize',
        'config_form',
        'config',
        'define_routes',
        'after_save_item',
        'admin_items_batch_edit_form',
        'items_batch_edit_custom',
        'book_reader_item_show',
    );

    /**
     * @var array Filters for the plugin.
     */
    protected $_filters = array(
        // Currently, it's a checkbox, so no error can be done.
        // 'items_batch_edit_error',
    );

    /**
     * @var array Options and their default values.
     */
    protected $_options = array(
        'bookreader_custom_css' => '',
        'bookreader_favicon_url' => 'your_theme/images/favicon.ico',
        'bookreader_custom_library' => 'BookReaderCustom.php',
        'bookreader_sorting_mode' => false,
        'bookreader_mode_page' => '1',
        'bookreader_embed_functions' => '0',
        'bookreader_class' => '',
        'bookreader_width' => '100%',
        'bookreader_height' => '480',
    );

    /**
     * Installs the plugin.
     */
    public function hookInstall()
    {
        $this->_options['bookreader_favicon_url'] = WEB_THEME . '/' . $this->_options['bookreader_favicon_url'];
        $this->_options['bookreader_custom_library'] = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . $this->_options['bookreader_custom_library'];

        $this->_installOptions();
    }

    /**
     * Upgrade the plugin.
     */
    public function hookUpgrade($args)
    {
        $oldVersion = $args['old_version'];
        $newVersion = $args['new_version'];

        if (version_compare($oldVersion, '2.1', '<=')) {
            set_option('bookreader_custom_css', WEB_PLUGIN .  '/BookReader/' . $this->_options['bookreader_custom_css']);
            delete_option('bookreader_logo_url');
            delete_option('bookreader_toolbar_color');
        }
    }

    /**
     * Uninstalls the plugin.
     */
    public function hookUninstall()
    {
        $this->_uninstallOptions();
    }

    /**
     * Add the translations.
     */
    public function hookInitialize()
    {
        add_translation_source(dirname(__FILE__) . '/languages');
    }

    /**
     * Shows plugin configuration page.
     *
     * @return void
     */
    public function hookConfigForm($args)
    {
        $view = get_view();
        echo $view->partial(
            'plugins/bookreader-config-form.php',
            array(
                'default_library' => dirname(__FILE__) . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'BookReaderCustom.php',
            )
        );
    }

    /**
     * Processes the configuration form.
     *
     * @return void
     */
    public function hookConfig($args)
    {
        $post = $args['post'];
        foreach ($post as $key => $value) {
            set_option($key, $value);
        }
    }

    /**
     * Defines public routes.
     *
     * @return void
     */
    public function hookDefineRoutes($args)
    {
        $router = $args['router'];

        if (is_admin_theme()) {
            return;
        }

        $router->addRoute('bookreader_action', new Zend_Controller_Router_Route(
            'viewer/:action/:id',
            array(
                'controller' => 'viewer',
                'module' => 'book-reader',
                'id' => '/d+',
        )));
    }

    /**
     * Manages data when an item is saved.
     */
    public function hookAfterSaveItem($args)
    {
        $item = $args['record'];

        // This is done after insert, update or post and only if a function exists
        // in the custom library.
        BookReader::saveData($item);
    }

    /**
     * Add a partial batch edit form.
     *
     * @return void
     */
    public function hookAdminItemsBatchEditForm($args)
    {
        $view = $args['view'];
        echo get_view()->partial(
            'forms/bookreader-batch-edit.php'
        );
    }

    /**
     * Process the partial batch edit form.
     *
     * @return void
     */
    public function hookItemsBatchEditCustom($args)
    {
        $item = $args['item'];
        $order_by_filename = $args['custom']['bookreader']['orderByFilename'];
        $mix_files_types = $args['custom']['bookreader']['mixFilesTypes'];

        if ($order_by_filename) {
            $this->_sortFiles($item, (boolean) $mix_files_types);
        }
    }

    /**
     * Sort all files of an item by name.
     *
     * @param Item $item
     * @param boolean $mix_files_types
     *
     * @return void
     */
    protected function _sortFiles($item, $mix_files_types = false)
    {
        if ($item->fileCount() == 0) {
            return;
        }

        if ($mix_files_types) {
            $list = $item->Files;
            usort($list, array('BookReader', 'compareFilenames'));
        }
        else {
            // Get leaves and remove blank ones.
            $leaves = array_filter(BookReader::getLeaves($item));
            $non_leaves = array_filter(BookReader::getNonLeaves($item));
            // Manage the case where there is no BookReader data.
            if (empty($leaves) && empty($non_leaves)) {
                $list = $item->Files;
                usort($list, array('BookReader', 'compareFilenames'));
            }
            else {
                // Order them separately.
                usort($leaves, array('BookReader', 'compareFilenames'));
                usort($non_leaves, array('BookReader', 'compareFilenames'));
                // Finally, merge them.
                $list = array_merge($leaves, $non_leaves);
            }
        }

        // To avoid issues with unique index when updating (order should be
        // unique for each file of an item), all orders are reset to null before
        // true process.
        $db = $this->_db;
        $bind = array(
            $item->id,
        );
        $sql = "
            UPDATE `$db->File` files
            SET files.order = NULL
            WHERE files.item_id = ?
        ";
        $db->query($sql, $bind);

        // To avoid multiple updates, we do a single query.
        foreach ($list as &$file) {
            $file = $file->id;
        }
        // The array is made unique, because a leaf can be repeated.
        $list = implode(',', array_unique($list));
        $sql = "
            UPDATE `$db->File` files
            SET files.order = FIND_IN_SET(files.id, '$list')
            WHERE files.id in ($list)
        ";
        $db->query($sql);
    }

    /**
     * Hook to display viewer.
     *
     * @param array $args
     *   Two specific arguments:
     *   - (integer) 'page': set the page to be shown when including the iframe,
     *   - (boolean) 'embed_functions': allow user to include an iframe with all
     *   functions (Zoom, Search...). Can be used to include a better viewer
     *   into items/views.php without requiring user to use the full viewer.
     *
     * @return void
     */
    public function hookBookReaderItemShow($args)
    {
        $view = $args['view'];
        $item = isset($args['item']) && !empty($args['item'])
            ? $args['item']
            : $view->item;
        $part = isset($args['part'])? (integer) $args['part'] : 0;
        $page = isset($args['page']) ? $args['page'] : '0';
        // Currently, all or none functions are enabled.
        $embed_functions = isset($args['embed_functions'])
            ? $args['embed_functions']
            : get_option('bookreader_embed_functions');

        $mode_page = isset($args['mode_page'])
            ? $args['mode_page']
            : get_option('bookreader_mode_page');

        // Build url of the page with iframe.
        $url = WEB_ROOT . '/viewer/show/' . $item->id;
        $url .= ($part > 1) ? '?part=' . $part : '';
        $url .= $embed_functions ? '' : ((($part > 1) ? '&' : '?') . 'ui=embed');
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
        echo $html;
    }
}
