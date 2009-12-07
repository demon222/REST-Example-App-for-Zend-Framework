<?php

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
        $modelObj = new Default_Model_GuestbookEntry();
        $modelSet = $modelObj->fetchAll();
        
        $data = array();
        foreach ($modelSet as $model) {
            $data[] = array(
                '_id' => $model->getId(),
                'email' => $model->getEmail(),
                'created' => $model->getCreated(),
                'comment' => $model->getComment(),
            );
        }
        
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
