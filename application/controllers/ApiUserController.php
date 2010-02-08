<?php

require_once('Rest/Controller/Action/Abstract.php');
require_once('Rest/Serializer.php');

class ApiUserController extends Rest_Controller_Action_Abstract
{
    protected static function _createModelHandler()
    {
        $authResult = Zend_Auth::getInstance()->getIdentity();
        return new Default_Model_AclHandler_User(Zend_Registry::get('acl'), $authResult['username']);
    }

    protected static function _createValidateObject()
    {
        return new Default_Validate_User();
    }

}
