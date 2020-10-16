<?php
/**
 * Represent a BookReader.
 *
 * This is not a standard record and it has no associated table.
 * This is a wrapper for the selected creator.
 *
 * @package BookReader
 */
class BookReader
{
    protected $_creator;

    public function __construct($item = null)
    {
        $creator = get_option('bookreader_creator') ?: 'BookReader_Creator_Default';
        $this->_creator = new $creator($item);
    }

    /**
     * Return a property of the selected creator for all other properties.
     *
     * @param string $property
     * @return var
     */
    public function __get($property)
    {
        if (property_exists($this->_creator, $property)) {
            return $this->_creator->$property;
        }
        throw new BadMethodCallException("Property named '$property' does not exist in BookReader class.");
    }

    /**
     * Check if a property exists in the creator for all other properties.
     *
     * @param string $property
     * @return bool
     */
    public function __isset($property)
    {
        return property_exists($this->_creator, $property);
    }

    /**
     * Delegate to the selected creator for all other method calls.
     */
    public function __call($method, $args)
    {
        if (method_exists($this->_creator, $method)) {
            return call_user_func_array(array($this->_creator, $method), $args);
        }
        throw new BadMethodCallException("Method named '$method' does not exist in BookReader class.");
    }

    /**
     * Prepare a string for html display.
     *
     * @return string
     */
    public static function htmlCharacter($string)
    {
        $string = strip_tags($string);
        $string = html_entity_decode($string, ENT_QUOTES);
        $string = utf8_encode($string);
        $string = htmlspecialchars_decode($string);
        $string = addslashes($string);
        $string = utf8_decode($string);

        return $string;
    }
}
