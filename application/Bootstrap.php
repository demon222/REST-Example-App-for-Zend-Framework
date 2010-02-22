<?php

/**
 * Application bootstrap
 * 
 * @uses    Zend_Application_Bootstrap_Bootstrap
 * @package QuickStart
 */
class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{

    protected function _initApplication()
    {
        Zend_Registry::set('config', $this->getApplication()->getOptions());
        Zend_Registry::set('acl', new Zend_Acl());
    }

    /**
     * Bootstrap autoloader for application resources
     * 
     * @return Zend_Application_Module_Autoloader
     */
    protected function _initAutoload()
    {
        $autoloader = new Zend_Application_Module_Autoloader(array(
            'namespace' => 'Default',
            'basePath'  => dirname(__FILE__),
        ));

        // set up applicatio/validates as a place where the autoloader will
        // look. Forms has similar support baked in and would traditionally
        // have been the mechanism through which validates are used in MVC.
        // However with REST design forms aren't useful but validates are.
        $autoloader->addResourceType('validate', 'validates', 'Validate');
        
        return $autoloader;
    }

    /**
     * Bootstrap the view doctype
     * 
     * @return void
     */
    protected function _initDoctype()
    {
        $this->bootstrap('view');
        $view = $this->getResource('view');
        $view->doctype('XHTML1_STRICT');
    }
    
    /**
     * Bootstrap the REST Routes
     *
     * @return void
     */
    protected function _initRestRoute()
    {
        $this->bootstrap('Request');
        
        // set the whole app as using REST routing
        $front = $this->getResource('FrontController');
        $restControllers = array(
            'default' => array(
                'api-entry',
                'api-entry-owner',
                'api-user',
                'api-entourage',
                'api-email',
            )
        );
        $restRoute = new Zend_Rest_Route($front, array(), $restControllers);
        
        $front->getRouter()->addRoute('rest', $restRoute);
    }
    
    /**
     * Bootstrap the Request
     *
     * @return Zend_Controller_Request_Abstract
     */
    protected function _initRequest()
    {
        $this->bootstrap('FrontController');
        
        $front = $this->getResource('FrontController');
        $request = $front->getRequest();
        
        if ($request === null) {
            $request = new Zend_Controller_Request_Http();
            $front->setRequest($request);
        }
        
        return $request;
    }
}
