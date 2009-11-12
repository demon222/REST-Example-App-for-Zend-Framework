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
    
    protected static function _mapModelToArray($guestbookEntry) {
        return array(
            'id' => $guestbookEntry->getId(),
            'email' => $guestbookEntry->getEmail(),
            'created' => $guestbookEntry->getCreated(),
            'comment' => $guestbookEntry->getComment(),
        );
    }
    
    
    public function indexAction()
    {
        $modelObj = new Default_Model_GuestbookEntry();
        $modelSet = $modelObj->fetchAll();
        
        $data = array();
        foreach ($modelSet as $model) {
            $data[] = self::_mapModelToArray($model);
        }
        
        $this->view->data = $data;
    }
    
    
    public function getAction()
    {
        $model = new Default_Model_GuestbookEntry();
        $model->find((integer) $this->getRequest()->getParam('id'));
        
        if (null === $model->getId()) {
            $this->getResponse()->setHttpResponseCode(404);
            $this->view->data = array('ok' => false, 'status' => 404);
            $this->render();
            return;
        }
        
        $this->view->data = self::_mapModelToArray($model);
    }
    
    
    public function putAction()
    {
        $request = $this->getRequest();
        
        $model = new Default_Model_GuestbookEntry();
        $model->find((integer) $request->getParam('id'));
        
        if (null === $model->getId()) {
            $this->getResponse()->setHttpResponseCode(404);
            $this->view->data = array('ok' => false, 'status' => 404);
            $this->render();
            return;
        }
        
        $form = new Default_Form_GuestbookEntry();
        
        if (!$form->isValid($request->getPost())) {
            $this->getResponse()->setHttpResponseCode(409);
            $this->view->data = array('ok' => false, 'status' => 409);
            $this->render();
            return;
        }
        
        $model->setOptions($form->getValues());
        $model->save();
        
        $this->view->data = self::_mapModelToArray($model);
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
        
        $this->view->data = self::_mapModelToArray($model);
    }
    
    public function preDispatch()
    {
        $this->_helper->layout()->disableLayout();
        // use the custom view json for this api class, might be joined by an xml api later
        $this->_helper->viewRenderer->setRender('json');
        
        // JSON is the proper content type but browsers, currently, make it
        // make it difficult to debug it because they attempt to download the
        // code instead of rendering it as they do with Javascript.
        // JSON is a subset of Javascript, so using javascript instead is
        // acceptable, but not optimal.
        if (strpos('application/json', $this->getRequest()->getHeader('Content-Type')) === false) {
            $this->getResponse()->setHeader('Content-Type','application/javascript');
        } else {
            $this->getResponse()->setHeader('Content-Type','application/json');
        }
    }
    
}
