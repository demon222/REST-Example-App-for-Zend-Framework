<?php

require_once('Rest/Controller/Action/Abstract.php');

/**
 * Guestbook controller
 *
 * In this example, we will build a simple guestbook style application. It is 
 * capable only of being "signed" and listing the previous entries.
 * 
 * @uses       Rest_Controller_Action_Abstract
 * @package    QuickStart
 * @subpackage Controller
 */
class EntryApiController extends Rest_Controller_Action_Abstract
{

    protected static function _mapModelToArray($entry)
    {
        return array(
            '_id' => $entry->getId(),
            'email' => $entry->getEmail(),
            'created' => $entry->getCreated(),
            'comment' => $entry->getComment(),
        );
    }


    protected static function _modelObjectFactory($options = null)
    {
        return new Default_Model_GuestbookEntry($options);
    }


    protected static function _validateObjectFactory()
    {
        return new Default_Validate_Entry();
    }

}
