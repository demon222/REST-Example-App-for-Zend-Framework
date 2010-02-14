<?php
require_once('ZendPatch/Validate/Abstract.php');

abstract class Rest_Validate_Abstract extends ZendPatch_Validate_Abstract
{
    const NOT_DATA = 'notData';
    const NOT_ENOUGH_DATA = 'notEnoughData';

    /**
     * Validation failure message template definitions
     *
     * @var array
     */
    protected $_messageTemplates = array(
        self::NOT_DATA => "Data structure not supplied to validator",
        self::NOT_ENOUGH_DATA => "Not enough data for validation",
    );

    /**
     * @var string
     */
    protected $_methodContext;

    /**
     * returns either 'put' or 'post'
     *
     * @return string
     */
    public function getMethodContext()
    {
        if (null === $this->_methodContext) {
            $this->_methodContext = 'put';
        }
        return $this->_methodContext;
    }

    /**
     * set the method context as either 'put' or 'post'
     *
     * @param string
     * @return Rest_Validate_Interface
     */
    public function setMethodContext($mc)
    {
        if ($mc != 'post' && $mc != 'put') {
            throw new Exception('invalid method context passed, expecting "put" or "post"');
        }
        $this->_methodContext = $mc;
        return $this;
    }

    /**
     * Returns true if and only if $value meets the validation requirements
     *
     * If $value fails validation, then this method returns false, and
     * getMessages() will return an array of messages that explain why the
     * validation failed.
     *
     * @param  mixed $value
     * @return boolean
     * @throws Zend_Valid_Exception If validation of $value is impossible
     */
    public function isValid($value)
    {
        // $value is expected to be an array
        if (!is_array($value)) {
            $this->_error(self::NOT_DATA);
            return false;
        }

        $partialHappened = $this->_isValid($value);

        // not enough data for even a partial validation
        if (false === $partialHappened) {
            $this->_error(self::NOT_ENOUGH_DATA);
        }

        // report failure if there are any messages
        if (count($this->_messages)) {
            return false;
        }

        // validation passed!
        return true;
    }

    /**
     * @param array $value
     * @return boolean indicating is any valid data of interest was passed
     */
    abstract protected function _isValid($value);
}