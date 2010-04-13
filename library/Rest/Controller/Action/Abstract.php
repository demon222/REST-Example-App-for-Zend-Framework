<?php
require_once('Rest/Serializer.php');
require_once('Rest/Model/Exception.php');
require_once('Rest/Model/UnauthorizedException.php');
require_once('Rest/Model/NotFoundException.php');
require_once('Rest/Model/MethodNotAllowedException.php');
require_once('Rest/Model/BadRequestException.php');
require_once('ZendPatch/Controller/Action.php');

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
abstract class Rest_Controller_Action_Abstract extends ZendPatch_Controller_Action
{

    /**
     * @return Rest_Model_Handler_Interface
     */
    abstract protected static function _createModelHandler();
    /**
     * @return Rest_Validate_Abstract
     */
    abstract protected static function _createValidateObject();

    /**
     * @var float
     */
    protected $_startTime;

    /*
     * For backwards compatibility (prior to PHP 5.3) '$this->' is being used
     * for references to the above methods but 'static::' would be proper.
     */

    public function indexAction()
    {
        $handler = $this->_createModelHandler();

        $url = $this->getRequest()->getRequestUri();
        $params = Rest_Serializer::decode($url, Rest_Serializer::FULL_URL_UNKNOWN);

        try {
            $items = $handler->getList($params);
        } catch (Rest_Model_MethodNotAllowedException $e) {
            $this->getResponse()->setHttpResponseCode(405);
            $this->getResponse()->setHeader('Allow', implode(', ', $e->getAllowedMethods()));
            $this->view->data = $e->getMessage();
            return;
        } catch (Rest_Model_BadRequestException $e) {
            $this->getResponse()->setHttpResponseCode(400);
            $this->view->data = $e->getMessage();
            return;
        } catch (Exception $e) {
            $this->getResponse()->setHttpResponseCode(500);
            // should output 500 errors conditional to APP_ENV being dev
            // should log errors no matter what environment
            return;
        }

        $this->view->data = $items;
    }

    public function getAction()
    {
        $handler = $this->_createModelHandler();

        // get the identifying parameters into the model
        $ids = $this->_getIdsOfRequest($handler);

        $url = $this->getRequest()->getRequestUri();
        $params = Rest_Serializer::decode($url, Rest_Serializer::FULL_URL_UNKNOWN);

        // load the model
        try {
            $item = $handler->get($ids, $params);
        } catch (Rest_Model_NotFoundException $e) {
            $this->getResponse()->setHttpResponseCode(404);
            return;
        } catch (Rest_Model_MethodNotAllowedException $e) {
            $this->getResponse()->setHttpResponseCode(405);
            $this->getResponse()->setHeader('Allow', implode(', ', $e->getAllowedMethods()));
            $this->view->data = $e->getMessage();
            return;
        } catch (Rest_Model_BadRequestException $e) {
            $this->getResponse()->setHttpResponseCode(400);
            $this->view->data = $e->getMessage();
            return;
        } catch (Exception $e) {
            $this->getResponse()->setHttpResponseCode(500);
            // should output 500 errors conditional to APP_ENV being dev
            // should log errors no matter what environment
            return;
        }

        $this->view->data = $item;
    }

    public function putAction()
    {
        $request = $this->getRequest();

        $handler = $this->_createModelHandler();

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

        $validate = $this->_createValidateObject()->setMethodContext('put');

        if (!$validate->isValid($input)) {
            $this->getResponse()->setHttpResponseCode(400);
            $this->view->data = $validate->getMessages();
            return;
        }

        // giving presedence to the resources uri identity parameters over any
        // that may appear in the content body. Where they are different, it
        // could be thought of as a request to move/rename the resource but
        // this is a feature for another day

        // get the identifying parameters into the model
        $ids = $this->_getIdsOfRequest($handler);

        try {
            $item = $handler->put($ids, $input);
        } catch (Rest_Model_UnauthorizedException $e) {
            // if the resource Acl fails for this method but works for get
            // ie. user knows it exists but can't to do 'method'
            $this->getResponse()->setHttpResponseCode(401);
            $this->view->data = $e->getMessage();
            return;
        } catch (Rest_Model_MethodNotAllowedException $e) {
            $this->getResponse()->setHttpResponseCode(405);
            $this->getResponse()->setHeader('Allow', implode(', ', $e->getAllowedMethods()));
            $this->view->data = $e->getMessage();
            return;
        } catch (Rest_Model_NotFoundException $e) {
            $this->getResponse()->setHttpResponseCode(404);
            return;
        } catch (Rest_Model_BadRequestException $e) {
            $this->getResponse()->setHttpResponseCode(400);
            $this->view->data = $e->getMessage();
            return;
        } catch (Exception $e) {
            $this->getResponse()->setHttpResponseCode(500);
            // should output 500 errors conditional to APP_ENV being dev
            // should log errors no matter what environment
            return;
        }

        $this->view->data = $item;
    }

    public function deleteAction()
    {
        $handler = $this->_createModelHandler();

        // get the identifying parameters into the model
        $ids = $this->_getIdsOfRequest($handler);

        try {
            $handler->delete($ids);
        } catch (Rest_Model_UnauthorizedException $e) {
            // if the resource Acl fails for this method but works for get
            // ie. user knows it exists but can't to do 'method'
            $this->getResponse()->setHttpResponseCode(401);
            $this->view->data = $e->getMessage();
            return;
        } catch (Rest_Model_NotFoundException $e) {
            $this->getResponse()->setHttpResponseCode(404);
            return;
        } catch (Rest_Model_MethodNotAllowedException $e) {
            $this->getResponse()->setHttpResponseCode(405);
            $this->getResponse()->setHeader('Allow', implode(', ', $e->getAllowedMethods()));
            $this->view->data = $e->getMessage();
            return;
        } catch (Rest_Model_BadRequestException $e) {
            $this->getResponse()->setHttpResponseCode(400);
            $this->view->data = $e->getMessage();
            return;
        } catch (Rest_Model_ConflictException $e) {
            $this->getResponse()->setHttpResponseCode(409);
            $this->view->data = $e->getMessage();
        } catch (Exception $e) {
            $this->getResponse()->setHttpResponseCode(500);
            // should output 500 errors conditional to APP_ENV being dev
            // should log errors no matter what environment
            return;
        }

        // not sure if returning 200 or 410 is better after a delete
        // the request was completed successfully but the requested resource
        // is no longer accessible. 204 could be argued for, but because
        // meta information is passed back in the content (for convience, with
        // this library) 204 is not appropriate
        $this->getResponse()->setHttpResponseCode(200);
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

        $validate = $this->_createValidateObject()->setMethodContext('post');

        if (!$validate->isValid($input)) {
            $this->getResponse()->setHttpResponseCode(400);
            $this->view->data = $validate->getMessages();
            return;
        }

        $handler = $this->_createModelHandler();

        try {
            $item = $handler->post($input);
        } catch (Rest_Model_UnauthorizedException $e) {
            // acl check failed
            $this->getResponse()->setHttpResponseCode(401);
            $this->view->data = $e->getMessage();
            return;
        } catch (Rest_Model_MethodNotAllowedException $e) {
            $this->getResponse()->setHttpResponseCode(405);
            $this->getResponse()->setHeader('Allow', implode(', ', $e->getAllowedMethods()));
            $this->view->data = $e->getMessage();
            return;
        } catch (Exception $e) {
            $this->getResponse()->setHttpResponseCode(500);
            // should output 500 errors conditional to APP_ENV being dev
            // should log errors no matter what environment
            return;
        }

        $this->getResponse()
            ->setHttpResponseCode(201)
            ->setHeader('Location', $this->view->url(array('action' => 'get', 'id' => $item['id'])));

        $this->view->data = $item;
    }

    protected function _getIdsOfRequest($handler)
    {

        $idKeys = $handler->getIdentityKeys();
        $ids = array();
        foreach ($idKeys as $key) {
            $value = $this->getRequest()->getParam($key);
            if (null === $value) {
                $this->getResponse()->setHttpResponseCode(400);
                $this->view->data = $key . ' is required and was not provided in the request';
            }
            $ids[$key] = $value;
        }
        return $ids;
    }

    public function preDispatch()
    {
        $this->_startTime = microtime(true);

        // log in procedure

{
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

    $result = Zend_Auth::getInstance()->authenticate($authAdapter);

    if (!$result->isValid()) {
        // Bad userame/password, or canceled password prompt

        // Authentication failed; print the reasons why
        $this->getResponse()->setHttpResponseCode(401);
        $this->view->data = $result->getMessages();

        // cancel the action but post dispatch will be executed
        $this->setCancelAction(true);
        return;
    }

    $ident = $result->getIdentity();

    $userTable = new Default_Model_DbTable_User();
    $user = $userTable->fetchRow(array('username = ?' => $ident['username']));
    if (null === $user) {
        throw new Exception('User ' . $ident['username'] . ' is not in the database');
    }
}

        Zend_Registry::set('userId', array('id' => $user->id));
    }

    public function postDispatch()
    {
        $data = isset($this->view->data) ? $this->view->data : null;

        // disable aspects that interfer with just outputing JSON or XML
        // disable layout
        $this->_helper->layout()->disableLayout();
        // disable view
        Zend_Controller_Action_HelperBroker
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
                'status-code' => $this->getResponse()->getHttpResponseCode(),
                'request-uri' => $this->getRequest()->getRequestUri(),
                'identity' => Zend_Registry::isRegistered('userId') ? Zend_Registry::get('userId') : null,
                'duration' => microtime(true) - $this->_startTime,
            ),
        ));

        $this->getResponse()->setBody($serializer->getEncodedString());
    }

}
