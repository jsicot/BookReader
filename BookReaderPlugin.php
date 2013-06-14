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

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'bookreaderFunctions.php';

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
        'config_form',
        'config',
        'define_routes',
        'public_theme_header',
        'public_items_show',
    );

    /**
     * @var array Options and their default values.
     */
    protected $_options = array(
        'bookreader_logo_url' => 'BookReader/views/shared/images/logo_icon.png',
        'bookreader_favicon_url' => 'your_theme/images/favicon.ico',
        'bookreader_toolbar_color' => '#E2DCC5',
        'bookreader_embed_enable' => true,
        'bookreader_sorting_mode' => true,
        'bookreader_mode_page' => '1',
        'bookreader_embed_functions' => '0',
        'bookreader_width' => 620,
        'bookreader_height' => 500,
        'bookreader_toolbar_color' => '#e2dcc5',
    );

    /**
     * Installs the plugin.
     */
    public function hookInstall()
    {
        $this->_options['bookreader_logo_url'] = WEB_PLUGIN . DIRECTORY_SEPARATOR . $this->_options['bookreader_logo_url'];
        $this->_options['bookreader_favicon_url'] = WEB_THEME . DIRECTORY_SEPARATOR . $this->_options['bookreader_favicon_url'];
	$this->_options['bookreader_toolbar_color'] = $this->_options['bookreader_toolbar_color'];
        self::_installOptions();
    }

    /**
     * Uninstalls the plugin.
     */
    public function hookUninstall()
    {
        $this->_uninstallOptions();
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

        set_option('bookreader_logo_url', $post['bookreader_logo_url']);
        set_option('bookreader_favicon_url', $post['bookreader_favicon_url']);
        set_option('bookreader_toolbar_color', $post['bookreader_toolbar_color']);
        set_option('bookreader_embed_enable', (boolean) $post['bookreader_embed_enable']);
        set_option('bookreader_sorting_mode', (boolean) $post['bookreader_sorting_mode']);
        set_option('bookreader_mode_page', (($post['bookreader_mode_page'] == '1') ? '1' : '2'));
        set_option('bookreader_embed_functions', (($post['bookreader_embed_functions'] == '1') ? '1' : '0'));
        set_option('bookreader_width', $post['bookreader_width']);
        set_option('bookreader_height', $post['bookreader_height']);
        set_option('bookreader_toolbar_color', $post['bookreader_toolbar_color']);
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
     * Add css and js in the header of the theme.
     */
    public function hookPublicThemeHeader($args)
    {
        if (!get_option('bookreader_embed_enable')) {
            return;
        }

        $request = Zend_Controller_Front::getInstance()->getRequest();
        if ($request->getControllerName() == 'items' && $request->getActionName() == 'show') {
            queue_css_file('BookReader');
            queue_css_file('BookReaderCustom');
            queue_js_file('jquery-1.4.2.min');
            queue_js_file('jquery-ui-1.8.5.custom.min');
            queue_js_file('dragscrollable');
            queue_js_file('jquery.colorbox-min');
            queue_js_file('jquery.ui.ipad');
            queue_js_file('jquery.bt.min');
            queue_js_file('BookReader');
            queue_js_file('ToCmenu');
        }
    }

    /**
     * Display viewer.
     *
     * @param array $args
     *   Two specific arguments:
     *   - (integer) page: set the page to be shown when including the iframe,
     *   - (boolean) embed_functions: allow user to include an iframe with all
     *   functions (Zoom, Search...). Can be used to include a better viewer
     *   into items/views.php without requiring user to use the full viewer.
     *
     * @return void
     */
    public function hookPublicItemsShow($args)
    {
        if (!get_option('bookreader_embed_enable')) {
            return;
        }

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
        $url = WEB_ROOT . "/viewer/show/" . $item->id;
        $url .= $embed_functions ? '' : '?ui=embed';
        $url .= '#';
        $url .= empty($page) ? '' : 'page/n' . $page . '/';
        $url .= 'mode/' . $mode_page . 'up';

        $width = get_option('bookreader_width');
        $height = get_option('bookreader_height');

        include_once 'views' . DIRECTORY_SEPARATOR . 'public'. DIRECTORY_SEPARATOR . 'bookreader-iframe.php';
    }
}
