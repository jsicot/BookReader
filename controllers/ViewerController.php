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

        $this->view->item = $item;
        $this->view->ui = $ui;
        $this->view->part = $part;
    }
}
