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
        $this->_prepareViewer();
    }

    public function viewAction()
    {
        $this->_prepareViewer();
    }

    /**
     * Helpert to prepare the viewer (only the javascript differs in view).
     */
    protected function _prepareViewer()
    {
        $request = $this->getRequest();
        $id = $request->getParam('id');
        $item = get_record_by_id('Item', $id);
        if (empty($item)) {
            throw new Omeka_Controller_Exception_404;
        }

        $ui = $request->getParam('ui');
        $part = $request->getParam('part');

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
