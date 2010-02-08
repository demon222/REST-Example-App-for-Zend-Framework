<?php

class Rest_Model_MethodNotAllowedException extends Zend_Exception
{
    /**
     * @var array of strings
     */
    protected $_allowedMethods = array();

    /**
     * @param array $allowedMethods
     */
    public function __construct(array $allowedMethods = null)
    {
        if (null !== $allowedMethods) {
            $this->setAllowedMethods($allowedMethods);
        }
    }

    /**
     * return array of strings
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
