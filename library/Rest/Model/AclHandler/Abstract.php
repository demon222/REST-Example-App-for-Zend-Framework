<?php

abstract class Rest_Model_AclHandler_Abstract implements Rest_Model_AclHandler_Interface, Zend_Acl_Resource_Interface
{
    /**
     * @var Zend_Acl
     */
    protected $_acl;

    /**
     * @var string
     */
    protected $_aclResourceId;

    /**
     * @var Object
     */
    protected $_aclContextUser;

    /**
     * @param array $id
     * @return string
     */
    public function getResourceId(array $id = null)
    {
        if ($this->_aclResourceId) {
            $this->setResourceId(get_class($this));
        }

        $specific = '';
        if ($id !== null) {
            $specfic = '=' . implode(',', $this->getIdentityKeys());
        }

        return $this->_aclResourceId . $specific;
    }

    /**
     * @param string $name
     * @return Rest_Model_AclHandler_Abstract
     */
    public function setResourceId($name)
    {
        $this->_aclResourceId = $name;
        return $this;
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
     * @return Rest_Model_AclHandler_Abstract
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
     * @return Rest_Model_AclHandler_Abstract
     */
    public function setAclContextUser($userObject)
    {
        $this->_aclContextUser = $userObject;
        return $this;
    }
}
