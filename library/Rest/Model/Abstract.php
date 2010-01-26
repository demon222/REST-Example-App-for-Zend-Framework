<?php

require_once('Rest/Model/Interface.php');

abstract class Rest_Model_Abstract implements Rest_Model_Interface
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
    protected $_aclObjectName;

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
                'aclObjectName',
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
                'mapper',
                'acl',
                'aclRole',
                'aclObjectName',
                'resourcesTree',
            ))
        ) {
            throw Exception('Invalid property "' . $name . '" specified');
        }
        return $this->$method();
    }

    /**
     * @return string
     */
    public function getAclObjectName()
    {
        if ($this->_aclObjectName) {
            $this->setAclObjectName(get_class($this));
        }
        return $this->_aclObjectName;
    }

    /**
     * @param string $name
     * @return Default_Model_Guestbook
     */
    public function setAclObjectName($name)
    {
        $this->_aclObjectName = $name;
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

    /**
     * @param Zend_Acl $acl
     * @return Rest_Model_Abstract
     */
    public function addResourcesToAcl($acl, $parent = null)
    {
        $this->_recursiveAddToAcl($acl, $this->getResourcesTree());
        return $this;
    }

    /**
     * @param Zend_Acl $acl
     * @param arrat $tree
     */
    protected static function _recursiveAddToAcl($acl, $tree, $parent = null)
    {
        foreach ($tree as $nodeKey => $nodeValue) {
            $name = ($parent ? $parent . '-' : '') . ($nodeKey ? $nodeKey : $nodeValue);

            $acl->addResource(new Zend_Acl_Resource($name), $parent);

            if ($nodeKey && is_array($nodeValue)) {
                self::_recursiveAddToAcl($acl, $nodeValue, $name);
            }
        }
    }

    /**
     * @return array
     */
    public function getResourcesTree()
    {
        /**
         * This is a placeholder implementation for getResourcesTree
         * It should be overridden in classes that extend this class.
         *
         * This function must return an array with values as strings for
         * resources that this class represents and checks on for its ACL code.
         * A Typical example would be:
         *
         *   return array(
         *       'guestbookEntry' => array(
         *           'comment',
         *           'created',
         *           'email',
         *       ),
         *   );
         *
         * Nesting of arrays is encouraged where it makes sense
         *
         * When the method addResourcesToAcl method is called from the parent
         */
        return array($this->getAclObjectName());
    }
}