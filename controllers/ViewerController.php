<?php
require_once 'Omeka/Controller/Action.php';

class BookReader_ViewerController extends Omeka_Controller_Action {	
	
	public function showAction()
	{
		$db = get_db();
		$Id = $this->getRequest()->getParam('id');
		$ui = $this->getRequest()->getParam('ui');		
		$itemObj = new stdClass();
		$itemObj->id = $Id;
		$itemObj->ui = $ui;	
		$this->view->booreaderCurrItem = $itemObj;
	}
	
	
}
