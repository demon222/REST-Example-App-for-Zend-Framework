<?php
/**
 * Converts between decoded PHP arrays and encoded strings. Supported encodings
 * include JSON, Javascript and simple XML.
 */
class Rest_Serializer
{
    const ANYTHING = '*';
    const JSON = 'application/json';
    const JAVASCRIPT = 'application/javascript';
    const XML = 'text/xml';
    const URL_ENCODE = 'application/x-www-form-urlencoded';
    const URL_JSON_ENCODE = 'url-json-encode';
    const FULL_URL_UNKNOWN = 'full-url-unknown';

    /**
     * Is checked against to determine type, see identifyType method.
     * Precendence indicated preference if a multiple content types
     * are specified in string to identifyType
     * 
     * @var array
     * @static
     */
    protected static $_types = array(
        'JSON' => self::JSON,
        'XML' => self::XML,
        'JAVASCRIPT' => self::JAVASCRIPT,
        'URL_ENCODE' => self::URL_ENCODE,
        'ANYTHING' => self::ANYTHING,
    );

    /**
     * usually from http Content-Type or Http-Accept
     * @var string
     */
    protected $_type = null;
    protected $_rawType = self::JSON;

    /**
     * decoded data structure
     * @var array
     */
    protected $_decodedArray = null;
    
    /**
     * encoded data structure
     * @var array
     */
    protected $_encodedString = null;


    /**
     * @return Rest_Serializer
     */
    public static function getInstance()
    {
        return new self();
    }

    /**
     * usually from http Content-Type or Http-Accept
     * @param string $type
     * @return Rest_Serializer
     */
    public function setType($type)
    {
        $this->_rawType = $type;
        $this->_type = null;
        return $this;
    }

    /**
     * cleaned up type
     * @return string
     */
    public function getType()
    {
        if ($this->_type === null) {
            $this->_type = self::identifyType($this->_rawType);
        }
        return $this->_type;
    }

    /**
     * Set an encoded string for conversion to a data structure
     * @param string $string
     * @return Rest_Serializer
     */
    public function setEncodedString($string)
    {
        $this->_encodedString = $string;
        $this->_decodedArray = null;
        return $this;
    }

    /**
     * Returns an encoded string converted from a data structure or false if
     * type is invalid or a problem during conversion
     * @return string
     */
    public function getEncodedString()
    {
        if ($this->_encodedString && $this->_decodedArray) {
            return $this->_encodedString;
        }
        $this->_encodedString = self::encode(
            $this->_decodedArray,
            $this->getType()
        );
        return $this->_encodedString;
    }

    /**
     * Set a data structure for conversion to an encoded string
     * @param array $array
     * @return Rest_Serializer
     */
    public function setDecodedArray($array)
    {
        $this->_decodedArray = $array;
        $this->_encodedString = null;
        return $this;
    }

    /**
     * Returns a datastructure by converting the encoded string or false if
     * type is invalid or a problem during conversion
     * @return array
     */
    public function getDecodedArray()
    {
        if ($this->_decodedArray && $this->_encodedString) {
            return $this->_decodedArray;
        }
        $this->_decodedArray = self::decode(
            $this->_encodedString,
            $this->getType()
        );
        return $this->_decodedArray;
    }

    /**
     * Returns the simplified type for the input string. Usually takes
     * in http Content-Type or Http-Accept string and determines if they are
     * of a particular type useful for supported REST applications
     * @param string $type
     * @return string
     * @static
     */
    public static function identifyType($type)
    {
        // iterate through the known types scanning the input type string
        foreach(self::$_types as $name => $value) {
            if (false !== strpos($type, $value)) {
                return $value;
            }
        }

        // if we get here, failed to find a match
        return false;
    }

    /**
     * Returns a serialized string for the passed data structure based on type
     * @param array $array
     * @param string $type
     * @return string
     * @static
     */
    public static function encode($array, $type)
    {
        if ($type == self::JSON || $type == self::JAVASCRIPT || $type == self::ANYTHING) {
            return Zend_Json::encode($array);
        }

        if ($type == self::XML) {
            return 'XML not yet implemented, sorry, come again';
        }

        if ($type == self::URL_ENCODE) {
            return 'Url encoded format not yet implemented';
        }

        return false;
    }

    /**
     * Returns a data structure for the deserialized passed string based on type
     * @param string $string
     * @param string $type
     * @return array
     * @static
     */
    public static function decode($string, $type)
    {
        if ($type == self::JSON || $type == self::JAVASCRIPT) {
            return Zend_Json::decode($string);
        }

        if ($type == self::XML) {
            return array('XML decode to array not yet supported');
        }

        if ($type == self::URL_ENCODE) {
            parse_str($string, $a);
            return $a;
        }

        if ($type == self::URL_JSON_ENCODE) {
            return self::decode(rawurldecode($string), self::JSON);
        }

        if ($type == self::FULL_URL_UNKNOWN) {
            $encodedData = parse_url($string, PHP_URL_QUERY);

            if (!strlen($encodedData)) {
                return null;
            }

            // if the url query part starts with '{' very likely JSON
            if ('{' == substr(rawurldecode($encodedData), 0, 1)) {
                return self::decode($encodedData, self::URL_JSON_ENCODE);
            }

            return self::decode($encodedData, self::URL_ENCODE);
        }

        return null;
    }

}
