<?php
/**
 * The index controller class.
 *
 * @package BookReader
 */
class BookReader_IndexController extends Omeka_Controller_AbstractActionController
{
    /**
     * Initialize the controller.
     */
    public function init()
    {
        // No view for these actions.
        $this->_helper->viewRenderer->setNoRender();
    }

    /**
     * Returns the answer to a query in order to highlight it via javascript.
     *
     * A result can contain multiple words, multiple matches, multiple pars and
     *  multiple boxes, for example when the answer is on two lines or pages.
     *
     * The resulted javascript/json is echoed and sent via Ajax.
     *
     * The structure of the json object is:
     * ->ia = item id
     * ->q = query
     * ->page_count = page count (useless)
     * ->body_length = body lenght (useless)
     * ->leaf0_missing = generally empty
     * ->matches = results as array of objects
     *     ->text = few words to contextualize the result, used in nav bar
     *     ->par = array of parallel images (currently, only the [0] is used)
     *         ->t = top limit of global zone
     *         ->l = left limit of global zone
     *         ->b = bottom limit of global zone
     *         ->r = right limit of global zone
     *         ->page = page number
     *         ->index = page index
     *         ->boxes = array of coordinates of boxes to highlight
     *             ->t = top limit of word zone
     *             ->l = left limit of word zone
     *             ->b = bottom limit of word zone
     *             ->r = right limit of word zone
     *             ->page = page number
     *             ->index = page index
     * Note that only one of "page" or "index" is needed. Index is prefered,
     * because it's generally simpler to manage and more efficient.
     */
    public function fulltextAction()
    {
        $request = $this->getRequest();
        $item_id = $request->getParam('item_id');
        $item = get_record_by_id('Item', $item_id);
        if (empty($item)) {
            throw new Omeka_Controller_Exception_404;
        }

        $part = $request->getParam('part');
        $query = $request->getParam('q');
        $query = utf8_encode($query);
        $callback = $request->getParam('callback');

        $output = array();

        // Check if there are data for search.
        $bookreader = new BookReader($item);
        if ($bookreader->hasDataForSearch()) {
            $output['id'] = $item_id;
            $output['part'] = $part;
            $output['q'] = $query;
            // TODO Check if these keys are really needed.
            // $output['page_count'] = 200;
            // $output['body_length'] = 140000;
            // TODO Kezako ?
            // $output['leaf0_missing'] = false;

            $answer = $bookreader->searchFulltext($query);
            $output['matches'] = $bookreader->highlightFiles($answer);
        }

        // Send answer.
        $this->getResponse()->clearBody();
        $this->getResponse()->setHeader('Content-Type', 'text/html');
        // header('Content-Type: text/javascript; charset=utf8');
        // header('Access-Control-Allow-Methods: GET, POST');
        $tab_json = json_encode($output);
        echo $callback . '(' . $tab_json . ')';
    }

    /**
     * Returns sized image for the current image.
     */
    public function imageProxyAction()
    {
        $this->_sendImage();
    }

    /**
     * Returns thumbnail image of the current image.
     */
    public function thumbProxyAction()
    {
        $this->_sendImage('thumbnail');
    }

    /**
     * Helper to return image of the current image.
     *
     * @param string $type Derivative type of the image.
     * @return void
     */
    protected function _sendImage($type = null)
    {
        $request = $this->getRequest();
        $itemId = $request->getParam('id');
        $item = get_record_by_id('item', $itemId);
        if (empty($item)) {
            throw new Omeka_Controller_Exception_404;
        }

        $index = $request->getParam('image');
        // Get the index.
        if ($index != '000') {
            $index = preg_replace('`^[0]*`', '', $index);
            $index--;
        }
        else {
            $index = 0;
        }

        $bookreader = new BookReader($item);

        $part = $request->getParam('part');
        $bookreader->setPart($part);

        if (is_null($type)) {
            $scale = $request->getParam('scale');
            $type = $bookreader->getSizeType($scale);
            // Set a default, even it's normally useless.
            $type = $type ?: 'fullsize';
        }

        $imagesFiles = $bookreader->getLeaves();
        $file = $imagesFiles[$index];
        // No file, so a blank image
        if (empty($file)) {
            $filepath = 'images/blank.png';
            $image = file_get_contents(physical_path_to($filepath));
            $this->getResponse()->clearBody();
            $this->getResponse()->setHeader('Content-Type', 'image/jpeg');
            $this->getResponse()->setBody($image);
        }
        // Else, redirect (302/307) to the url of the file.
        else {
            $this->_helper->redirector->gotoUrlAndExit($file->getWebPath($type));
        }
    }
}
