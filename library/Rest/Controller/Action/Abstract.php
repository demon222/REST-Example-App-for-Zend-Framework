<?php

require_once('Rest/Serializer.php');
require_once('Rest/Model/NotFoundException.php');

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
     * for references to the above methods but 'static::' would be proper.
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

        // get the identifying parameters into the model
        $idKeys = $model->getIdentityKeys();
        $ids = array();
        foreach ($idKeys as $key) {
            $ids[$key] = $this->getRequest()->getParam($key);
        }
        $model->setOptions($ids);

        // load the model
        try {
            $model->get();
        } catch (Zend_Acl_Exception $e) {
            // acl check failed
            $this->getResponse()->setHttpResponseCode(401);
            $this->view->data = $e->getMessage();
            return;
        } catch (Rest_Model_NotFoundException $e) {
            $this->getResponse()->setHttpResponseCode(404);
            return;
        } catch (Exception $e) {
            $this->getResponse()->setHttpResponseCode(500);
            return;
        }

        $this->view->data = $model->toArray();
    }

    public function putAction()
    {
        $request = $this->getRequest();

        $model = $this->_modelObjectFactory();

        // Can't beleive I'm doing this in PHP 5.3 and Zend Framework 1.9.
        // Should be replaced as soon as possible with
        // "$values = $request->getPut();" when such a function is available.
        $rawData = file_get_contents('php://input');

        $contentType = $this->getRequest()->getHeader('Content-Type');

        if (!Rest_Serializer::identifyType($contentType)) {
            // if the content type is not specificied it is probably URL_ENCODE
            $contentType = Rest_Serializer::URL_ENCODE;
        }

        $input = Rest_Serializer::getInstance()
            ->setEncodedString($rawData)
            ->setType($contentType)
            ->getDecodedArray();
        
        $validate = $this->_validateObjectFactory();

        if (!$validate->isValid($input)) {
            $this->getResponse()->setHttpResponseCode(400);
            $this->view->data = $validate->getMessages();
            return;
        }

        $model->setOptions($input);

        // giving presedence to the resources uri identity parameters over any
        // that may appear in the content body. Where they are different, it
        // could be thought of as a request to move/rename the resource but
        // this is a lower priority, for later.

        // get the identifying parameters into the model
        $idKeys = $model->getIdentityKeys();
        $ids = array();
        foreach ($idKeys as $key) {
            $ids[$key] = $this->getRequest()->getParam($key);
        }
        $model->setOptions($ids);

        try {
            $model->put();
        } catch (Zend_Acl_Exception $e) {
            // acl check failed
            $this->getResponse()->setHttpResponseCode(401);
            $this->view->data = $e->getMessage();
            return;
        } catch (Rest_Model_NotFoundException $e) {
            $this->getResponse()->setHttpResponseCode(404);
            return;
        } catch (Exception $e) {
            $this->getResponse()->setHttpResponseCode(500);
            return;
        }

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

        $input = Rest_Serializer::getInstance()
            ->setEncodedString($rawData)
            ->setType($contentType)
            ->getDecodedArray();

        $validate = $this->_validateObjectFactory();

        if (!$validate->isValid($input)) {
            $this->getResponse()->setHttpResponseCode(400);
            $this->view->data = $validate->getMessages();
            return;
        }

        $model = $this->_modelObjectFactory($input);
        try {
            $model->post();
        } catch (Zend_Acl_Exception $e) {
            // acl check failed
            $this->getResponse()->setHttpResponseCode(401);
            $this->view->data = $e->getMessage();
            return;
        } catch (Exception $e) {
            $this->getResponse()->setHttpResponseCode(500);
            return;
        }

        $this->getResponse()
            ->setHttpResponseCode(201)
            ->setHeader('Location', $this->view->url(array('action' => 'get', 'id' => $model->getId())));

        $this->view->data = $model->toArray();
    }

    public function deleteAction()
    {
        $model = $this->_modelObjectFactory();

        // get the identifying parameters into the model
        $idKeys = $model->getIdentityKeys();
        $ids = array();
        foreach ($idKeys as $key) {
            $ids[$key] = $this->getRequest()->getParam($key);
        }
        $model->setOptions($ids);

        try {
            $model->delete();
        } catch (Zend_Acl_Exception $e) {
            // acl check failed
            $this->getResponse()->setHttpResponseCode(401);
            $this->view->data = $e->getMessage();
            return;
        } catch (Rest_Model_NotFoundException $e) {
            $this->getResponse()->setHttpResponseCode(404);
            return;
        } catch (Exception $e) {
            $this->getResponse()->setHttpResponseCode(500);
            return;
        }

        // not sure if returning 200 or 410 is better after a delete
        // the request was completed successfully but the requested resource
        // is no longer accessible. 204 could be argued for, but because
        // meta information is passed back in the content for convience with
        // this library 204 is never appropriate
        $this->getResponse()->setHttpResponseCode(200);
    }

    public function preDispatch()
    {
        // Get a reference to the singleton instance of Zend_Auth
        $auth = Zend_Auth::getInstance();

$config = array(
    'accept_schemes' => 'basic digest',
    'realm'          => 'Guestbook API',
    'digest_domains' => '/',
    'nonce_timeout'  => 3600,
);

$authAdapter = new Zend_Auth_Adapter_Http($config);

$basicResolver = new Zend_Auth_Adapter_Http_Resolver_File();
$basicResolver->setFile(APPLICATION_PATH . '/configs/basicAuth.txt');

$digestResolver = new Zend_Auth_Adapter_Http_Resolver_File();
$digestResolver->setFile(APPLICATION_PATH . '/configs/digestAuth.txt');

$authAdapter->setBasicResolver($basicResolver);
$authAdapter->setDigestResolver($digestResolver);

$authAdapter->setRequest($this->getRequest());
$authAdapter->setResponse($this->getResponse());

$result = $authAdapter->authenticate();

        if (!$result->isValid()) {
            // Bad userame/password, or canceled password prompt
            
            // Authentication failed; print the reasons why
            $this->getResponse()->setHttpResponseCode(401);
            $this->view->data = $result->getMessages();

            // cancel the action but post dispatch will be executed
            $this->setCancelAction(true);
        }

    }


    // controls action execution, see dispatch method for more details
    protected $_cancelAction = false;

    /**
     * Controls execution of the intended dispatched action
     *
     * @param boolean $value
     * @return Rest_Controller_Action_Abstract
     */
    public function setCancelAction($value)
    {
        $this->_cancelAction = $value;
        return $this;
    }

    public function postDispatch()
    {
        $data = isset($this->view->data) ? $this->view->data : null;

        // disable aspects that interfer with just outputing JSON or XML
        // disable layout
        $this->_helper->layout()->disableLayout();
        // disable view
        $viewRenderer = Zend_Controller_Action_HelperBroker
            ::getStaticHelper('viewRenderer')->setNoRender();

        $contentType = $this->getRequest()->getHeader('Accept');

        $serializer = Rest_Serializer::getInstance()->setType($contentType);

        if (!$serializer->getType()) {
            // Don't know how to render in the identified type(s)
            // respond in Javascript with the http code set accordingly
            $this->getResponse()->setHttpResponseCode(406);
        }
        if (!$serializer->getType() || $serializer->getType() == Rest_Serializer::ANYTHING) {
            // JSON is the proper content type but browsers (2009/12) make it
            // difficult to debug it because they attempt to download the
            // code instead of rendering it as they do with Javascript.
            // JSON is a subset of Javascript, so using javascript instead is
            // often acceptable, if not optimal.
            $serializer->setType(Rest_Serializer::JAVASCRIPT);
        }

        $this->getResponse()->setHeader('Content-Type', $serializer->getType());

        $serializer->setDecodedArray(array(
            'content' => $data,
            'meta' => array(
                // mirror some values in the actual data to ease debugging
                '_status-code' => $this->getResponse()->getHttpResponseCode(),
                '_request-uri' => $this->getRequest()->getRequestUri(),
            ),
        ));

        $this->getResponse()->setBody($serializer->getEncodedString());
    }


    /**
     * Overwritten dispatch inorder to introduce cancel action functionality.
     * 
     * Dispatch the requested action
     *
     * @param string $action Method name of action
     * @return void
     */
    public function dispatch($action)
    {
        // Notify helpers of action preDispatch state
        $this->_helper->notifyPreDispatch();

        $this->preDispatch();
        if ($this->getRequest()->isDispatched()) {
            if (null === $this->_classMethods) {
                $this->_classMethods = get_class_methods($this);
            }

            // THE FOLLOWING IF CONDITION HAS BEEN INTRODUCED FOR CLEAN USAGE
            // OF REST IN PRE AND POST DISPATCH, OTHERWISE DISPATCH IS SAME TO
            // Zend_Controller_ 'Action.php 16541 2009-07-07 06:59:03Z bkarwin'
            if (!$this->_cancelAction) {
                // preDispatch() didn't change the action, so we can continue
                if ($this->getInvokeArg('useCaseSensitiveActions') || in_array($action, $this->_classMethods)) {
                    if ($this->getInvokeArg('useCaseSensitiveActions')) {
                        trigger_error('Using case sensitive actions without word separators is deprecated; please do not rely on this "feature"');
                    }
                    $this->$action();
                } else {
                    $this->__call($action, array());
                }
            }
            $this->postDispatch();
        }

        // whats actually important here is that this action controller is
        // shutting down, regardless of dispatching; notify the helpers of this
        // state
        $this->_helper->notifyPostDispatch();
    }

}
