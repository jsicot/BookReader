<?php
/**
 * BookReader
 *
 * This plugin adds Internet Archive BookReader into Omeka. It is used to view
 * books from the Internet Archive online and can also be used to view other
 * books.
 *
 * @copyright Julien Sicot, 2011-2013
 * @copyright Daniel Berthereau, 2013-2014
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 */

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
        'public_items_show',
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
        'bookreader_creator' => 'BookReader_Creator_Default',
        'bookreader_sorting_mode' => false,
        'bookreader_mode_page' => '1',
        'bookreader_append_items_show' => true,
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

        if (version_compare($oldVersion, '2.6', '<=')) {
            delete_option('bookreader_custom_library');
            set_option('bookreader_creator', $this->_options['bookreader_creator']);
            set_option('bookreader_append_items_show', $this->_options['bookreader_append_items_show']);
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
     * Initialize the plugin.
     */
    public function hookInitialize()
    {
        add_translation_source(dirname(__FILE__) . '/languages');
        add_shortcode('bookreader', array($this, 'shortcodeBookReader'));
    }

    /**
     * Shows plugin configuration page.
     *
     * @return void
     */
    public function hookConfigForm($args)
    {
        $view = get_view();
        echo $view->partial('plugins/bookreader-config-form.php');
    }

    /**
     * Processes the configuration form.
     *
     * @return void
     */
    public function hookConfig($args)
    {
        $post = $args['post'];
        foreach ($this->_options as $optionKey => $optionValue) {
            if (isset($post[$optionKey])) {
                set_option($optionKey, $post[$optionKey]);
            }
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
        $bookreader = new BookReader($item);
        $bookreader->saveData();
    }

    /**
     * Add a partial batch edit form.
     *
     * @return void
     */
    public function hookAdminItemsBatchEditForm($args)
    {
        $view = $args['view'];
        echo $view->partial(
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
            BookReader_Creator::sortFilesByOriginalName($list, false);
        }
        else {
            $bookreader = new BookReader($item);
            // Get leaves and remove blank ones.
            $leaves = array_filter($bookreader->getLeaves());
            $non_leaves = array_filter($bookreader->getNonLeaves());
            // Manage the case where there is no BookReader data.
            if (empty($leaves) && empty($non_leaves)) {
                $list = $item->Files;
                BookReader_Creator::sortFilesByOriginalName($list, false);
            }
            else {
                // Order them separately.
                BookReader_Creator::sortFilesByOriginalName($leaves, false);
                BookReader_Creator::sortFilesByOriginalName($non_leaves, false);
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
     *
     * @return void
     */
    public function hookPublicItemsShow($args)
    {
        if (!get_option('bookreader_append_items_show') && empty($args['direct'])) {
            return;
        }

        $view = empty($args['view']) ? get_view() : $args['view'];
        echo $view->getBookReader($args);
    }

    /**
     * Shortcode to display viewer.
     *
     * @param array $args
     * @param Omeka_View $view
     * @return string
     */
    public static function shortcodeBookReader($args, $view)
    {
        $args['view'] = $view;
        return $view->getBookReader($args);
    }
}
