<?php
/**
 * The viewer controller class.
 *
 * Even the embedded version uses it.
 *
 * @package BookReader
 */
class BookReader_ViewerController extends Omeka_Controller_AbstractActionController
{
    /**
     * Forward to the 'browse' action
     *
     * @see self::browseAction()
     */
    public function indexAction()
    {
        $this->_forward('show');
    }

    public function showAction()
    {
        $id = $this->getParam('id');
        $item = get_record_by_id('Item', $id);
        if (empty($item)) {
            throw new Omeka_Controller_Exception_404;
        }

        $this->_prepareViewer($item);
    }

    public function viewAction()
    {
        $id = $this->getParam('id');
        $item = get_record_by_id('Item', $id);
        if (empty($item)) {
            throw new Omeka_Controller_Exception_404;
        }

        $relations = metadata($item, array('Dublin Core', 'Relation'),
            array('all' => true, 'no_escape' => true, 'no_filter' => true));

        // Currently, only support gDoc urls.
        $tableUrl = '';
        $baseUrl = 'https://spreadsheets.google.com/feeds/list/';
        $endUrl = '/public/values';
        foreach ($relations as $relation) {
            if (strpos($relation, $baseUrl) === 0
                    && substr_compare($relation, $endUrl, -strlen($endUrl), strlen($endUrl)) === 0
                ) {
                $tableUrl = $relation;
                break;
            }
        }
        if (empty($tableUrl)) {
            $this->_helper->flashMessenger(__('This item has no table of images.'), 'error');
            return $this->forward('show', 'items', 'default', array(
                'module' => null,
                'controller' => 'items',
                'action' => 'show',
                'id' => $item->id,
            ));
        }

        $this->_prepareViewer($item);
        $this->view->tableUrl = $tableUrl . '?alt=json-in-script&callback=spreadsheetLoaded';
    }

    /**
     * Helpert to prepare the viewer (only the javascript differs in view).
     */
    protected function _prepareViewer($item)
    {
        $ui = $this->getParam('ui');
        $part = $this->getParam('part');

        $bookreader = new BookReader($item);
        $bookreader->setUI($ui);
        $bookreader->setPart($part);

        $this->view->bookreader = $bookreader;
        $this->view->item = $item;
        // Values have been checked inside BookReader.
        $this->view->ui = $bookreader->getUI();
        $this->view->part = $bookreader->getPart();
    }
}
