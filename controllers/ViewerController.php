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
        $db = get_db();
        $id = $this->getRequest()->getParam('id');
        $ui = $this->getRequest()->getParam('ui');
        $itemObj = new stdClass();
        $itemObj->id = $id;
        $itemObj->ui = $ui;
        $this->view->bookreaderCurrItem = $itemObj;
    }
}
