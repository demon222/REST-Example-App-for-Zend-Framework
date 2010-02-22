<?php
require_once('Rest/Model/AclHandler/Interface.php');
require_once('Util/Array.php');

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
     * A set of roles that the concrete class requires to be initialized.
     * Note: the default role doesn't need to be defined it is assumed
     * @var array
     */
     protected $_roles = array();

     /**
      * Defines permissions that apply to the all the resource items of the
      * model. For instance, the public (default) can't see an entry, but
      * members can and owners can edit, create and delete entries. Keep in mind
      * that the scope of a permission can be indicated by roles which are
      * dynamic and based in the database.
      *
      *
      * EXAMPLE:
      * protected $_staticPermissions = array(
      *     'default' => array(
      *         'deny' => array('get', 'put', 'delete', 'post'),
      *     ),
      *     'owner' => array(
      *         'allow' => array('get', 'put', 'delete', 'post'),
      *     ),
      *     'member' => array(
      *         'allow' => array('get'),
      *         'deny' => array('put', 'delete', 'post'),
      *     )
      * );
      * 
      * @var array
      */
     protected $_staticPermissions = array();

    /**
     * Important for every AclHandler to add to the acl all the relevant
     * general resource rules
     */
    protected function _initAclRules()
    {
        $acl = $this->getAcl();

        if (!$acl->has($this)) {
            $acl->addResource($this);
        }

        if (!$acl->hasRole('default')) {
            $acl->addRole('default');
        }

        foreach($this->_roles as $role) {
            if (!$acl->hasRole($role)) {
                $acl->addRole($role, 'default');
            }
        }

        foreach($this->_staticPermissions as $role => $permission) {
            if (isset($permission['allow'])) {
                $acl->allow($role, $this, $permission['allow']);
            }
            if (isset($permission['deny'])) {
                $acl->deny($role, $this, $permission['deny']);
            }
        }
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
    public function getSpecificResourceId(array $id)
    {
        return implode(',', $id);
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
}
