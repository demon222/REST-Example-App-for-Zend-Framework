<?php

/**
 * Guestbook controller
 *
 * In this example, we will build a simple guestbook style application. It is
 * capable only of being "signed" and listing the previous entries.
 *
 * @uses       Zend_Controller_Action
 * @package    Rest
 * @subpackage Controller
 */
abstract class Rest_Controller_Action_Abstract extends Zend_Controller_Action
{

    abstract protected static function _validateObjectFactory();
    abstract protected static function _modelObjectFactory($options = null);

    /*
     * For backwards compatibility (prior to PHP 5.3) '$this->' is being used
     * for references to the above 3 methods but 'static::' would be proper.
     */


    public function indexAction()
    {
        $modelObj = $this->_modelObjectFactory();
        $modelSet = $modelObj->fetchAll();

        $data = array();
        foreach ($modelSet as $model) {
            $data[] = $model->toArray();
        }

        $this->view->data = $data;
    }


    public function getAction()
    {
        $model = $this->_modelObjectFactory();
        $model->find((integer) $this->getRequest()->getParam('id'));

        if (null === $model->getId()) {
            $this->getResponse()->setHttpResponseCode(404);
            $this->view->data = array('ok' => false, 'status' => 404);
            $this->render();
            return;
        }

        $this->view->data = $model->toArray();
    }


    public function putAction()
    {
        $request = $this->getRequest();

        $model = $this->_modelObjectFactory();
        $model->find((integer) $request->getParam('id'));

        if (null === $model->getId()) {
            $this->getResponse()->setHttpResponseCode(404);
            $this->view->data = array('ok' => false, 'status' => 404);
            $this->render();
            return;
        }

        // Can't beleive I'm doing this in PHP 5.3 and Zend Framework 1.9.
        // Should be replaced as soon as possible with
        // "$values = $request->getPut();" when such a function is available.
        $rawData = file_get_contents('php://input');

        $contentType = $this->getRequest()->getHeader('Content-Type');

        if (!Rest_Serializer::identifyType($contentType)) {
            // if the content type is not specificied it is probably URL_ENCODE
            $contentType = Rest_Serializer::URL_ENCODE;
        }

        $values = Rest_Serializer::getInstance()
            ->setEncodedString($rawData)
            ->setType($contentType)
            ->getDecodedArray();
        
        $validator = $this->_validateObjectFactory();

        if (!$validator->isValid($values)) {
            $this->getResponse()->setHttpResponseCode(409);
            $this->view->data = array('ok' => false, 'status' => 409);
            $this->render();
            return;
        }

        $model->setOptions($values);
        $model->save();

        $this->view->data = $model->toArray();
    }


    public function postAction()
    {
        $rawData = file_get_contents('php://input');

        $contentType = $this->getRequest()->getHeader('Content-Type');

        if (!Rest_Serializer::identifyType($contentType)) {
            // if the content type is not specificied it is probably URL_ENCODE
            $contentType = Rest_Serializer::URL_ENCODE;
        }

        $values = Rest_Serializer::getInstance()
            ->setEncodedString($rawData)
            ->setType($contentType)
            ->getDecodedArray();

        $validate = $this->_validateObjectFactory();

        if (!$validate->isValid($values)) {
            $this->getResponse()->setHttpResponseCode(409);
            $this->view->data = array('ok' => false, 'status' => 409);
            $this->render();
            return;
        }
        
        $model = $this->_modelObjectFactory($values);
        $model->save();

        $this->getResponse()
            ->setHttpResponseCode(201)
            ->setHeader('Location', $this->view->url(array('action' => 'get', 'id' => $model->getId())));

        $this->view->data = $model->toArray();
    }

    public function deleteAction()
    {
        $model = $this->_modelObjectFactory();
        $model->find((integer) $this->getRequest()->getParam('id'));

        if (null === $model->getId()) {
            $this->getResponse()->setHttpResponseCode(404);
            $this->view->data = array('ok' => false, 'status' => 404);
            $this->render();
            return;
        }

        $model->delete();
    }


    public function postDispatch() {
        $data = isset($this->view->data) ? $this->view->data : null;

        // disable aspects that interfer with just outputing JSON or XML
        // disable layout
        $this->_helper->layout()->disableLayout();
        // disable view
        $viewRenderer = Zend_Controller_Action_HelperBroker
            ::getStaticHelper('viewRenderer')->setNoRender();

        $contentType = $this->getRequest()->getHeader('Accept');
        
        if (!Rest_Serializer::identifyType($contentType)) {
            // JSON is the proper content type but browsers (2009/12) make it
            // difficult to debug it because they attempt to download the
            // code instead of rendering it as they do with Javascript.
            // JSON is a subset of Javascript, so using javascript instead is
            // often acceptable, if not optimal.
            $contentType = Rest_Serializer::JAVASCRIPT;
        }
        
        $this->getResponse()->setHeader('Content-Type', $contentType);

        $this->getResponse()->setBody(Rest_Serializer::getInstance()
            ->setDecodedArray($data)
            ->setType($contentType)
            ->getEncodedString()
        );
    }

}
