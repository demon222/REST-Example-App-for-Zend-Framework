<?php
require_once('Rest/Model/Handler/Interface.php');

interface Rest_Model_AclHandler_Interface extends Rest_Model_Handler_Interface
{
    /**
     * @param string $name
     * @return Rest_Model_AclHandler_Interface
     */
    public function setResourceId($name);

    /**
     * @param array $id
     * @return string
     */
    public function getResourceSpecificId(array $id);

    /**
     * @return Zend_Acl
     */
    public function getAcl();

    /**
     * @param Zend_Acl $acl
     * @return Rest_Model_AclHandler_Interface
     */
    public function setAcl($acl);

    /**
     * @return Object
     */
    public function getAclContextUser();

    /**
     * @param Object $userObject
     * @return Rest_Model_AclHandler_Interface
     */
    public function setAclContextUser($userObject);
}
