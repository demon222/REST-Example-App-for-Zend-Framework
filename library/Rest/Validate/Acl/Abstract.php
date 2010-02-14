<?php
require_once('Rest/Validate/Abstract.php');

abstract class Rest_Validate_Acl_Abstract extends Rest_Validate_Abstract
{
    /**
     * @var Zend_Acl
     */
    protected $_acl;

    /**
     * @var Object
     */
    protected $_aclContextUser;

    /**
     * @param array|Zend_Acl $options
     */
    function __construct($acl = null, $username = null)
    {
        if ($acl instanceof Zend_Acl) {
            $this->setAcl($acl);
        }
        if (is_string($username)) {
            $this->setAclContextUser($username);
        }
    }

    /**
     * @return Zend_Acl
     */
    public function getAcl()
    {
        return $this->_acl;
    }

    /**
     * @param Zend_Acl $acl
     * @return Rest_Model_AclHandler_Interface
     */
    public function setAcl($acl)
    {
        $this->_acl = $acl;

        $this->_initAclRules();

        return $this;
    }

    /**
     * @return Object
     */
    public function getAclContextUser()
    {
        return $this->_aclContextUser;
    }

    /**
     * @param Object $userObject
     * @return Rest_Model_AclHandler_Interface
     */
    public function setAclContextUser($userObject)
    {
        $this->_aclContextUser = $userObject;
        return $this;
    }
}
