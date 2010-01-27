<?php

require_once('Rest/Model/Interface.php');

abstract class Rest_Model_Abstract implements Rest_Model_Interface, Zend_Acl_Resource_Interface
{
    /**
     * @var Zend_Acl
     */
    protected $_acl;

    /**
     * @var Zend_Acl_Role
     */
    protected $_aclRole;

    /**
     * @var string
     */
    protected $_aclResourceId;

    /**
     * Constructor
     *
     * @param  array|null $options
     * @return void
     */
    public function __construct(array $options = null)
    {
        if (is_array($options)) {
            $this->setOptions($options);
        }
    }

    /**
     * Provide a set of id key names. These values are commonly used to determine
     * what values are needed to uniquely identify a resource for get, put, or
     * delete methods
     *
     * @return array
     */
    public function getIdentityKeys() {
        // the identity of a function is almost always 'id' solely. So implement
        // that here in the abstract implementation. Sometimes multiple keys
        // are used or a different name, for these cases this function
        // could be overwritten by extending classes
        return array('id');
    }

    /**
     * Sets object state by taking an associative array and calling set methods
     * corresponding to the array's keys.
     *
     * @param array $options
     * @return Rest_Model_Abstract
     */
    public function setOptions(array $options)
    {
        $methods = get_class_methods($this);
        foreach ($options as $key => $value) {
            $method = 'set' . ucfirst($key);
            if (in_array($method, $methods)) {
                $this->$method($value);
            }
        }
        return $this;
    }

    /**
     * Fetch all entries from the persistance layer and return as array of
     * associative arrays (key & value).
     *
     * @return array
     */
    public function fetchAllAsArrays()
    {
            $modelSet = $this->fetchAll();

            $data = array();
            foreach ($modelSet as $model) {
                $data[] = $model->toArray();
            }

            return $data;
    }

    /**
     * Overloading: allow property access
     *
     * @param  string $name
     * @param  mixed $value
     * @return void
     */
    public function __set($name, $value)
    {
        $method = 'set' . $name;
        if (!method_exists($this, $method) || in_array($name, array(
                'mapper',
                'acl',
                'aclRole',
                'resourceId',
            ))
        ) {
            throw Exception('Invalid property "' . $name . '" specified');
        }
        $this->$method($value);
    }

    /**
     * Overloading: allow property access
     *
     * @param  string $name
     * @return mixed
     */
    public function __get($name)
    {
        $method = 'get' . $name;
        if (!method_exists($this, $method) || in_array($name, array(
                'identityKeys',
                'mapper',
                'acl',
                'aclRole',
                'resourceId',
            ))
        ) {
            throw Exception('Invalid property "' . $name . '" specified');
        }
        return $this->$method();
    }

    /**
     * @return string
     */
    public function getResourceId()
    {
        if ($this->_aclResourceId) {
            $this->setResourceId(get_class($this));
        }
        return $this->_aclResourceId;
    }

    /**
     * @param string $name
     * @return Default_Model_Guestbook
     */
    public function setResourceId($name)
    {
        $this->_aclResourceId = $name;
        return $this;
    }

    /**
     * @return Zend_Acl
     */
    public function getAcl()
    {
        return $this->_acl;
    }

    /**
     * @param Zend_Acl $acl
     * @return Default_Model_Guestbook
     */
    public function setAcl($acl)
    {
        $this->_acl = $acl;
        return $this;
    }

    /**
     * @return Zend_Acl_Role
     */
    public function getAclRole()
    {
        return $this->_aclRole;
    }

    /**
     * @param string|Zend_Acl_Role $role
     * @return Default_Model_Guestbook
     */
    public function setAclRole($role)
    {
        // find the Zend_Acl_Role if not already provided
        if (!($role instanceof Zend_Acl_Role)) {
            $role = $this->getAcl()->getRole($role);
        }

        $this->_aclRole = $role;
        return $this;
    }
}
