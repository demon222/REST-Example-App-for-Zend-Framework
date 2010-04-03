<?php

class Rest_Model_MethodNotAllowedException extends Zend_Exception
{
    /**
     * @var array of strings
     */
    protected $_allowedMethods = array();

    /**
     * @var string
     */
    protected $_invalidMethod;

    /**
     * @param string $invalidMethod
     * @param array $allowedMethods
     */
    public function __construct($invalidMethod, array $allowedMethods = null)
    {
        if (null !== $allowedMethods) {
            $this->setAllowedMethods($allowedMethods);
        }
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->getInvalidMethod() . ' is not an accepted method. Try ' . implode(', ', $this->getAllowedMethods());
    }

    /**
     * @return string
     */
    public function getInvalidMethod()
    {
        return $this->_invalidMethod;
    }

    /**
     * @param string $invalidMethod
     */
    public function setInvalidMethod($invalidMethod)
    {
        $this->_invalidMethod = $invalidMethod;
    }

    /**
     * @return array of strings
     */
    public function getAllowedMethods()
    {
        return $this->_allowedMethods;
    }

    /**
     * @param array $allowedMethods
     * @return Rest_Model_MethodNotAllowedException
     */
    public function setAllowedMethods(array $allowedMethods)
    {
        $this->_allowedMethods = $allowedMethods;
    }
}
