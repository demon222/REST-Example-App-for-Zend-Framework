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
class EntryApiController extends Zend_Controller_Action
{
    public function indexAction()
    {
        $modelObj = new Default_Model_GuestbookEntry();
        $modelSet = $modelObj->fetchAll();
        
        $data = array();
        foreach ($modelSet as $model) {
            $data[] = array(
                'id' => $model->getId(),
                'email' => $model->getEmail(),
                'created' => $model->getCreated(),
                'comment' => $model->getComment(),
            );
        }
        
        $this->view->data = $data;
    }
    
    public function getAction()
    {
        $id = $this->getRequest()->getQuery('id', null);
        
        if ($id === null) {
            // is this possible? if so either forward, redirect or render index
        }
        
        $model = new Default_Model_GuestbookEntry();
        $model->find($id);
        
        if ($model->getId() === null) {
            $this->view->data = array('ok' => false, 'status' => '404');
            $this->render();
            // error, should show 404 error with 404 header, use ErrorControler in some way
            // doesn't need to be json response, in fact it shouldn't be
            //$this->_forward();
        }
        
        $this->view->data = array(
            'id' => $model->getId(),
            'email' => $model->getEmail(),
            'created' => $model->getCreated(),
            'comment' => $model->getComment(),
        );
    }
    
    public function putAction()
    {
        $request = $this->getRequest();
        
        $id = $request->getQuery('id', null);
        
        if ($id === null) {
            // is this possible? if so either forward, redirect or render index
        }
        
        $model = new Default_Model_GuestbookEntry();
        $model->find($id);
        
        if ($model->getId() === null) {
            $this->view->data = array('ok' => false, 'status' => '404');
            $this->render();
            // error, should show 404 error with 404 header, use ErrorControler in some way
            // doesn't need to be json response, in fact it shouldn't be
            //$this->_forward();
        }
        
        $form = new Default_Form_GuestbookEntry();
        
        $model->setOptions($form->getValues());
        
        if (!$form->isValid($request->getPost())) {
            $this->view->data = array('ok' => false);
            $this->render();
        }
        
        $this->getAction();
    }
    
    public function postAction()
    {
        $form = new Default_Form_GuestbookEntry();
        
        if (!$form->isValid($this->getRequest()->getPost())) {
            $this->view->data = array('ok' => false);
            $this->render();
        }
        
        $model = new Default_Model_GuestbookEntry($form->getValues());
        $model->save();
        
        $this->getResponse()
            ->setHttpResponseCode(201)
            ->setHeader('Location', $this->view->url(array('action' => 'get', 'id' => $model->getId())));
        
        $this->view->data = array(
            'id' => $model->getId(),
            'email' => $model->getEmail(),
            'created' => $model->getCreated(),
            'comment' => $model->getComment(),
        );
    }
    
    public function preDispatch()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setRender('json');
    }
    
}
