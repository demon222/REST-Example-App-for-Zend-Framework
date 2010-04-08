<?php

require_once('Rest/Requestor.php');

/**
 * Guestbook controller
 *
 * In this example, we will build a simple guestbook style application. It is 
 * capable only of being "signed" and listing the previous entries.
 * 
 * @uses       Zend_Controller_Action
 * @package    QuickStart
 * @subpackage Controller
 */
class EntryController extends Zend_Controller_Action
{
    /**
     * The index, or landing, action will be concerned with listing the entries 
     * that already exist.
     *
     * Assuming the default route and default router, this action is dispatched 
     * via the following urls:
     * - /guestbook/entry
     * - /guestbook/entry/index
     *
     * @return void
     */
    public function indexAction()
    {
        $ident = Zend_Auth::getInstance()->getIdentity();
        $userTable = new Default_Model_DbTable_User();
        $userRow = $userTable->fetchRow(array('username = ?' => $ident['username']));
        Zend_Registry::set('userId', $userRow ? $userRow->id : null);

        $handler = new Default_Model_AclHandler_Entry(Zend_Registry::get('acl'), Zend_Registry::get('userId'));
        $data = $handler->getList(array('entourage' => 'Creator'));

        //$result = Rest_Requestor::apiRequest('GET', '/entry-api/');
        //$data = $result['content'];

        $this->view->data = $data;
    }
    
    /**
     * Provides a destination for direct links
     *
     * @return void
     */
    public function signAction()
    {
    }
}
