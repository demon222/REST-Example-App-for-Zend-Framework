<?php

class App_IndexController extends Zend_Controller_Action
{
    public function indexAction()
    {
        $ident = Zend_Auth::getInstance()->getIdentity();
        $userTable = new Default_Model_DbTable_User();
        $userRow = $userTable->fetchRow(array('username = ?' => $ident['username']));
        Zend_Registry::set('userId', $userRow ? $userRow->id : null);

        $handler = new Default_Model_AclHandler_Entry(Zend_Registry::get('acl'), Zend_Registry::get('userId'));
        $entries = $handler->getList(array('entourage' => 'Creator', 'where' => array('discussion_id' => $this->getRequest()->getParam('id', 0))));

        $this->view->entries = $entries;
        $handler = new Default_Model_AclHandler_User(Zend_Registry::get('acl'), Zend_Registry::get('userId'));
        $this->view->users = $handler->getList();
        $handler = new Default_Model_AclHandler_Discussion(Zend_Registry::get('acl'), Zend_Registry::get('userId'));
        $this->view->discussions = $handler->getList(array(
            'entourage' => array(
                'RecentEntry' => array(
                    'entourageModel' => 'Entry',
                    'entourageIdKey' => 'discussion_id',
                    'resourceIdKey' => 'id',
                    'singleOnly' => true,
                    'properties' => 'id discussion_id modified creator_user_id',
                    'entourage' => 'Creator',
                )
            )
        ));
    }
}
