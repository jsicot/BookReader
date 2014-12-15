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
    public function showAction()
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
