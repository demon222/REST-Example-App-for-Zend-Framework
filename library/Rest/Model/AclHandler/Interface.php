<?php

interface Rest_Model_AclHandler_Interface extends Rest_Model_Handler_Interface
{
    /**
     * Important for every AclHandler to add to the acl all the relevant
     * general resource rules
     */
    protected function _initAclRules();

    /**
     * @param array $id
     * @return array
     * @throws Rest_Model_NotFoundException, Zend_Acl_Exception
     */
    public function get(array $id);

    /**
     * @param array $id
     * @param array $prop
     * @return array
     * @throws Rest_Model_NotFoundException, Zend_Acl_Exception
     */
    public function put(array $id, array $prop = null);

    /**
     * @param array $id
     * @throws Rest_Model_NotFoundException, Zend_Acl_Exception
     */
    public function delete(array $id);

    /**
     * @param array $prop
     * @return array
     * @throws Zend_Acl_Exception
     */
    public function post(array $prop);
}
