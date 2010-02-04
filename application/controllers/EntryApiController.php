<?php

require_once('Rest/Controller/Action/Abstract.php');
require_once('Rest/Serializer.php');

class EntryApiController extends Rest_Controller_Action_Abstract
{
    protected static function _createModelHandler()
    {
        return new Default_Model_AclHandler_Entry(Zend_Registry::get('acl'));
    }

    protected static function _createValidateObject()
    {
        return new Default_Validate_Entry();
    }

}
