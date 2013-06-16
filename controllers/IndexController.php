<?php
/**
 * The index controller class.
 *
 * @package BookReader
 */
class BookReader_IndexController extends Omeka_Controller_AbstractActionController
{
    /**
     * Returns the answer to a query with coordinates of the matching words.
     */
    public static function fulltextAction()
    {
        return BookReader_Custom::fulltextAction();
    }

    /**
     * Returns image of the current image.
     */
    public function imageProxyAction()
    {
        $scale = $this->getRequest()->getParam('scale');
        switch ($scale) {
            case ($scale < 1.1): $type = 'original'; break;
            case ($scale < 1.4): $type = 'fullsize'; break;
            case ($scale < 6): $type = 'fullsize'; break;
            case ($scale < 16): $type = 'thumbnails'; break;
            case ($scale < 32): $type = 'thumbnails'; break;
            default: $type = 'fullsize'; break;
        }

        $this->_sendImage($type);
    }

    /**
     * Returns image of the current image.
     */
    public function thumbProxyAction()
    {
        $this->_sendImage('thumbnails');
    }

    /**
     * Helper to return image of the current image.
     */
    protected function _sendImage($type = 'fullsize')
    {
        $id = $this->getRequest()->getParam('id');
        $item = get_record_by_id('item', $id);

        $num_img = $this->getRequest()->getParam('image');
        if ($num_img != '000') {
            $num_img = preg_replace('`^[0]*`', '', $num_img);
        }
        else {
            $num_img = '0';
        }
        $num_img = ($num_img - 1);

        // Création d'un tableau composé de l'ensemble des images de l'item consulté.
        $imagesFiles = BookReader::getImagesFiles($item);

        $image = FILES_DIR . DIRECTORY_SEPARATOR . $type . DIRECTORY_SEPARATOR . $imagesFiles[$num_img]->getDerivativeFilename();
        $image = file_get_contents($image);

        $this->getResponse()->clearBody ();
        $this->getResponse()->setHeader('Content-Type', 'image/jpeg');
        $this->getResponse()->setBody($image);
    }
}
