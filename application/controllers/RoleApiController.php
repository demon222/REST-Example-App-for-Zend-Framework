<?php

require_once('Rest/Controller/Action/Abstract.php');
require_once('Rest/Serializer.php');

class RoleApiController extends Rest_Controller_Action_Abstract
{
    protected static function _createModelHandler($options = null)
    {
        return new Default_Model_Handler_Role($options);
    }

    protected static function _createValidateObject()
    {
        return new Default_Validate_Entry();
    }

}