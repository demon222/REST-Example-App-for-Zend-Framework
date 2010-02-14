<?php

require_once('Rest/Controller/Action/Abstract.php');
require_once('Rest/Serializer.php');

class ApiEntryController extends Rest_Controller_Action_Abstract
{
    protected static function _createModelHandler()
    {
        return new Default_Model_AclHandler_Entry(Zend_Registry::get('acl'), Zend_Registry::get('userId'));
    }

    protected static function _createValidateObject()
    {
        return new Default_Validate_Acl_Entry(Zend_Registry::get('acl'), Zend_Registry::get('userId'));
    }
}
