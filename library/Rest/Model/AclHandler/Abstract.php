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
    function __construct($acl = null, $username = null)
    {
        if ($acl instanceof Zend_Acl) {
            $this->setAcl($acl);
        }
        if (is_string($username)) {
            $this->setAclContextUser($username);
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
    public function isAllowed($privilege, array $id = null)
    {
        $acl = $this->getAcl();

        $username = $this->getAclContextUser();

        // first check if against this specific resource things are
        // allowed or denied
        $resourceSpecific = $this->getResourceSpecificId($id);
        
        $sql = 'SELECT p.id, p.permission, p.privilege, p.resource, p.role, r.user_id'
            . ' FROM permission AS p'
            . ' LEFT OUTER JOIN role AS r ON p.role = r.role AND p.resource = r.resource'
            . ' LEFT OUTER JOIN user AS u ON r.user_id = u.id'
            . ' WHERE ('
            . '     u.username = :username'
            . '     OR p.role = "default"'
            . ')'
            . ' AND p.resource = :resourceSpecific'
            . ' AND p.privilege = :privilege'
            . ' ORDER BY p.permission ASC'
            . '';
        $query = $this->_getDbHandler()->prepare($sql);
        $query->execute(array(
            ':username' => $username,
            ':resourceSpecific' => $resourceSpecific,
            ':privilege' => $privilege,
        ));
        $row = $query->fetch(PDO::FETCH_ASSOC);

        if (false !== $row) {
            // able to say that this specific resource is either allowed or denied
            return $row['permission'] == 'allow';
        }

        // specific resource check wasn't definitive, check the general resource
        $resourceGeneral = $this->getResourceId();

        // get the roles
        $sql = 'SELECT role FROM role'
            . ' WHERE user_id = :username'
            . ' AND resource = :resourceGeneral';
        $query = $this->_getDbHandler()->query($sql);
        $query->execute(array(
            ':username' => $username,
            ':resourceGeneral' => $resourceGeneral,
        ));
        $rowSet = $query->fetchAll(PDO::FETCH_ASSOC);
        $roleSet = Util_Array::arrayFromKeyValuesOfSet('role', $rowSet);

        // add the default role
        $roleSet[] = 'default';
        $allowed = false;
        foreach ($roleSet as $role) {
            // check if a role is accepted
            if ($this->getAcl()->isAllowed($role, $resourceGeneral, $privilege)) {
                // if any role is found that allows, the whole thing allows
                return true;
            }
        }

        // no allows found in the general resource, so permission is denied
        return false;
    }
}
