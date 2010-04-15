<?php

class App_IndexController extends Zend_Controller_Action
{
    public function indexAction()
    {
        $acl = Zend_Registry::get('acl');

//        $ident = Zend_Auth::getInstance()->getIdentity();
//        $userTable = new Default_Model_DbTable_User();
//        $userRow = $userTable->fetchRow(array('username = ?' => $ident['username']));

        $userTable = new Default_Model_DbTable_User();
        $userRow = $userTable->fetchRow(array('username = ?' => 'Alex'));

        $userId = $userRow ? $userRow->id : null;
        Zend_Registry::set('userId', $userId);

        $discussionId = $this->getRequest()->getParam('id', 0);

        $handler = new Default_Model_AclHandler_Entry($acl, $userId);
        $this->view->entries = $handler->getList(array(
            'entourage' => 'Creator',
            'where' => array(
                'discussion_id' => $discussionId
            )
        ));

        $handler = new Default_Model_AclHandler_User($acl, $userId);
        $this->view->users = $handler->getList();

        $handler = new Default_Model_AclHandler_Discussion($acl, $userId);
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
        try {
            $this->view->discussion = $handler->get(array('id' => $discussionId));
        } catch(Rest_Model_Exception $e) {
            $this->view->discussion = null;
        }

        $handler = new Default_Model_AclHandler_Community($acl, $userId);
        $this->view->communities = $handler->getList();
    }
}
