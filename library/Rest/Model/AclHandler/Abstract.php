<?php
require_once('Rest/Model/AclHandler/Interface.php');

abstract class Rest_Model_AclHandler_Abstract
    implements Rest_Model_AclHandler_Interface, Zend_Acl_Resource_Interface
{
    /**
     * @var Zend_Acl
     */
    protected $_acl;

    /**
     * @var string
     */
    protected $_aclResourceId;

    /**
     * @var Object
     */
    protected $_aclContextUser;

    /**
     * @param array|Zend_Acl $options
     */
    function __construct($options = null)
    {
        if ($options instanceof Zend_Acl) {
            $this->setAcl($options);
        }
        if (is_array($options) && $options['acl']) {
            $this->setAcl($options['acl']);
        }
    }

    /**
     * Important for every AclHandler to add to the acl all the relevant
     * general resource rules
     */
    protected function _initAclRules()
    {
    }

    /**
     * Used mainly to ensure that the required keys have been passed to
     * controllers that inturn implement model handlers
     *
     * @return array
     */
    public static function getIdentityKeys()
    {
        return array('id');
    }

    /**
     * @param array $id
     * @return string
     */
    public function getResourceId()
    {
        if (null === $this->_aclResourceId) {
            // look for the part after the last '_' in the class name and use
            // that as the resource id, else use the full class name
            $fullClassName = get_class($this);
            $nameStart = strrpos($fullClassName, '_');
            if (false === $nameStart) {
                $name = $fullClassName;
            } else {
                $name = substr($fullClassName, $nameStart + 1);
            }
            $this->setResourceId($name);
        }
        return $this->_aclResourceId;
    }

    /**
     * @param array $id
     * @return string
     */
    public function getResourceSpecificId(array $id)
    {
        return $this->getResourceId() . '=' . implode(',', $id);
    }

    /**
     * @param string $name
     * @return Rest_Model_AclHandler_Interface
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
     * @return Rest_Model_AclHandler_Interface
     */
    public function setAcl($acl)
    {
        $this->_acl = $acl;

        $this->_initAclRules();

        return $this;
    }

    /**
     * @return Object
     */
    public function getAclContextUser()
    {
        return $this->_aclContextUser;
    }

    /**
     * @param Object $userObject
     * @return Rest_Model_AclHandler_Interface
     */
    public function setAclContextUser($userObject)
    {
        $this->_aclContextUser = $userObject;
        return $this;
    }

    /**
     * Loops through the roles to check for one that is allowed for the method.
     *
     * @param string $method, same as what Zend_Acl referers to as 'privilege' but 'method' used for REST context
     * @return boolean
     */
    public function isAllowed(string $method, array $id = null)
    {
        // check the specific case, done if any of the roles gives an allowed
        // if all of the specific resource role relations are denied then denied
        // if not sure yet, check further, check the resource generally against
        // its roles. Allowed if any are allowed
        $allowed = false;
        $roles = $this->getAclContextRoles();
        foreach($roles as $role) {
            // for the current role check if this specific resource is allowed
            // or denied. If no record, then proceed to check the resource
            // generally (and its parents)
            if (null !== ($itemPermission = Permission::get($this->getResourceSpecificId($id), $role, $method))) {
                if ($itemPermission == 'allow') {
                    return true;
                } else {
                    continue;
                }
            }

            if ($this->getAcl()->isAllowed($role, $this->getResourceId(), $method)) {
                $allowed = true;
                break;
            }
        }

        return $allowed;
    }
}
