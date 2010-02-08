<?php

require_once('Rest/Controller/Action/Abstract.php');
require_once('Rest/Serializer.php');

class ApiEntourageController extends Rest_Controller_Action_Abstract
{
    protected static function _createModelHandler()
    {
        $authResult = Zend_Auth::getInstance()->getIdentity();
        return new Default_Model_AclHandler_Entourage(Zend_Registry::get('acl'), $authResult['username']);
    }

    protected static function _createValidateObject()
    {
        return null;
    }

}
