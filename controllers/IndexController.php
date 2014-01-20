<?php
/**
 * The index controller class.
 *
 * @package BookReader
 */
class BookReader_IndexController extends Omeka_Controller_AbstractActionController
{
    /**
     * Returns the answer to a query in order to highlight it via javascript.
     *
     * A result can contain multiple words, multiple matches, multiple pars and
     *  multiple boxes, for example when the answer is on two lines or pages.
     *
     * The resulted javascript/json is echoed and sent via Ajax.
     *
     * The structure of the json object is:
     *     ->ia = item id
     *     ->q = query
     *      ->page_count = page count (useless)
     *      ->body_length = body lenght (useless)
     *      ->leaf0_missing = generally empty
     *     ->matches = results as array of objects
     *         ->text = few words to contextualize the result, used in nav bar
     *         ->par = array of par zones (currently, only the [0] is used)
     *             ->t = top limit of global zone
     *             ->l = left limit of global zone
     *             ->b = bottom limit of global zone
     *             ->r = right limit of global zone
     *             ->page = page number
     *             ->index = page index
     *             ->boxes = array of coordinates of boxes to highlight
     *                 ->t = top limit of word zone
     *                 ->l = left limit of word zone
     *                 ->b = bottom limit of word zone
     *                 ->r = right limit of word zone
     *                 ->page = page number
     *                 ->index = page index
     * Note that only one of "page" or "index" is needed. Index is prefered,
     * because it's generally simpler to manage and more efficient.
     */
    public function fulltextAction()
    {
        $item_id = $this->getRequest()->getParam('item_id');
        // TODO Check if doc is different than item_id.
        $doc = $this->getRequest()->getParam('doc');
        $query = $this->getRequest()->getParam('q');
        $query = utf8_encode($query);
        $callback = $this->getRequest()->getParam('callback');

        $item = get_record_by_id('item', $item_id);

        $output = array();

        // Check if there are data for search.
        if (BookReader::hasDataForSearch($item)) {
            $output['ia'] = $doc;
            $output['q'] = $query;
            // TODO Check if these keys are really needed.
            // $output['page_count'] = 200;
            // $output['body_length'] = 140000;
            // TODO Kezako ?
            $output['leaf0_missing'] = false;

            $answer = BookReader::searchFulltext($query, $item);
            $output['matches'] = BookReader::highlightFiles($answer, $item);
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
        $scale = $this->getRequest()->getParam('scale');
        $itemId = $this->getRequest()->getParam('id');
        $item = get_record_by_id('item', $itemId);

        $type = BookReader::sendImage($scale, $item);

        $this->_sendImage($type);
    }

    /**
     * Returns image of the current image.
     */
    public function thumbProxyAction()
    {
        $this->_sendImage('thumbnail');
    }

    /**
     * Helper to return image of the current image.
     */
    protected function _sendImage($type = 'fullsize')
    {
        $id = $this->getRequest()->getParam('id');
        $item = get_record_by_id('item', $id);

        $index = $this->getRequest()->getParam('image');
        // Get the index.
        if ($index != '000') {
            $index = preg_replace('`^[0]*`', '', $index);
            $index--;
        }
        else {
            $index = 0;
        }

        $imagesFiles = BookReader::getLeaves($item);
        $image = $imagesFiles[$index];
        $image = empty($image)
            ? dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'shared' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'blank.png'
            : $image->getWebPath($type);

        $image = file_get_contents($image);
        $this->getResponse()->clearBody ();
        $this->getResponse()->setHeader('Content-Type', 'image/jpeg');
        $this->getResponse()->setBody($image);
    }
}
