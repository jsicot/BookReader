<?php
/**
 * This plugin adds Internet Archive BookReader into Omeka. It is used to view
 * books from the Internet Archive online and can also be used to view other
 * books.
 *
 * @see README.md
 *
 * @copyright Daniel Berthereau, 2013
 * @copyright Julien Sicot, 2011-2012
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 * @package BookReader
 */

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'BookReaderFunctions.php';

/**
 * BookReader plugin.
 */
class BookReaderPlugin extends Omeka_Plugin_AbstractPlugin
{
    /**
     * @var array Hooks for the plugin.
     */
    protected $_hooks = array(
        'install',
        'uninstall',
        'upgrade',
        'initialize',
        'config_form',
        'config',
        'define_routes',
        'book_reader_item_show',
    );

    /**
     * @var array Options and their default values.
     */
    protected $_options = array(
        'bookreader_custom_css' => 'views/shared/css/BookReaderCustom.css',
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
        $this->_options['bookreader_custom_css'] = WEB_PLUGIN . '/' . $this->_options['bookreader_custom_css'];
        $this->_options['bookreader_favicon_url'] = WEB_THEME . '/' . $this->_options['bookreader_favicon_url'];
        $this->_options['bookreader_custom_library'] = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . $this->_options['bookreader_custom_library'];

        $this->_installOptions();
    }

    /**
     * Uninstalls the plugin.
     */
    public function hookUninstall()
    {
        $this->_uninstallOptions();
    }

    /**
     * Upgrade the plugin.
     */
    public function hookUpgrade($args)
    {
        $oldVersion = $args['old_version'];
        $newVersion = $args['new_version'];

        if (version_compare($oldVersion, '2.1', '<=')) {
            set_option('bookreader_custom_css', WEB_PLUGIN . '/' . $this->_options['bookreader_custom_css']);
            delete_option('bookreader_logo_url');
            delete_option('bookreader_toolbar_color');
        }
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
    public function hookConfigForm()
    {
        require 'config_form.php';
    }

    /**
     * Processes the configuration form.
     *
     * @return void
     */
    public function hookConfig($args)
    {
        $post = $args['post'];

        set_option('bookreader_custom_css', trim($post['bookreader_custom_css']));
        set_option('bookreader_favicon_url', trim($post['bookreader_favicon_url']));
        set_option('bookreader_custom_library', realpath($post['bookreader_custom_library']));
        set_option('bookreader_sorting_mode', (boolean) $post['bookreader_sorting_mode']);
        set_option('bookreader_mode_page', (($post['bookreader_mode_page'] == '1') ? '1' : '2'));
        set_option('bookreader_embed_functions', (($post['bookreader_embed_functions'] == '1') ? '1' : '0'));
        set_option('bookreader_class', trim($post['bookreader_class']));
        set_option('bookreader_width', trim($post['bookreader_width']));
        set_option('bookreader_height', trim($post['bookreader_height']));
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
                'module'     => 'book-reader',
                'id'         => '/d+',
        )));
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
        $page = isset($args['page']) ? $args['page'] : '0';
        // Currently, all or none functions are enabled.
        $embed_functions = isset($args['embed_functions'])
            ? $args['embed_functions']
            : get_option('bookreader_embed_functions');

        $mode_page = get_option('bookreader_mode_page');

        // Build url of the page with iframe.
        $url = WEB_ROOT . '/viewer/show/' . $item->id;
        $url .= $embed_functions ? '' : '?ui=embed';
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
