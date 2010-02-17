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

    /**
     * Loops through the roles to check for one that is allowed for the method.
     *
     * @param string $method, same as what Zend_Acl referers to as 'privilege' but 'method' used for REST context
     * @return boolean
     */
    public function isAllowed($privilege, array $id = null)
    {
        $userId = $this->getAclContextUser();

        $resourceGeneral = $this->getResourceId();

        // first get the possible roles this user has with the resource
        if (null === $id) {
            $sql = 'SELECT role FROM resource_role'
                . ' WHERE user_id = :userId'
                . ' AND resource = :resourceGeneral'
                . ' AND resource_id IS NULL'
                . '';
            $query = $this->_getDbHandler()->query($sql);
            $query->execute(array(
                ':userId' => $userId,
                ':resourceGeneral' => $resourceGeneral,
            ));
        } else {
            // include roles from specific case
            $resourceSpecific = $this->getResourceSpecificId($id);

            $sql = 'SELECT role FROM resource_role'
                . ' WHERE user_id = :userId'
                . ' AND ('
                . '     resource = :resourceGeneral'
                . '     AND ('
                . '         resource_id IS NULL'
                . '         OR resource_id = :resourceSpecific'
                . '     )'
                . ' )'
                . '';
            $query = $this->_getDbHandler()->query($sql);
            $query->execute(array(
                ':userId' => $userId,
                ':resourceGeneral' => $resourceGeneral,
                ':resourceSpecific' => $resourceSpecific,
            ));
        }
        $rowSet = $query->fetchAll(PDO::FETCH_ASSOC);
        $roleSet = Util_Array::arrayFromKeyValuesOfSet('role', $rowSet);
        // make sure 'default' role is in there
        if (!in_array('default', $roleSet)) {
            $roleSet[] = 'default';
        }

        if (null !== $id) {
            // first check if against this specific resource things are
            // allowed or denied
            $roleVarKeyLookup = array();
            foreach ($roleSet as $index => $role) {
                $roleVarKeyLookup[':role_' . $index] = $role;
            }

            $sql = 'SELECT p.id, p.permission, p.privilege, p.resource, p.role'
                . ' FROM permission AS p'
                . ' WHERE p.role IN (' . implode(', ', array_keys($roleVarKeyLookup)) . ')'
                . ' AND p.resource = :resourceGeneral'
                . ' AND p.resource_id = :resourceSpecific'
                . ' AND p.privilege = :privilege'
                . ' ORDER BY p.permission ASC'
                . '';
            $query = $this->_getDbHandler()->prepare($sql);
            $query->execute(array_merge(
                array(
                    ':resourceGeneral' => $resourceGeneral,
                    ':resourceSpecific' => $resourceSpecific,
                    ':privilege' => $privilege,
                ),
                $roleVarKeyLookup
            ));
            $row = $query->fetch(PDO::FETCH_ASSOC);

            if (false !== $row) {
                // able to say that this specific resource is either allowed or denied
                return $row['permission'] == 'allow';
            }
        }

        // specific resource check wasn't definitive, check the general resource

        // add the default role
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

    protected function _getGenericAclListJoins()
    {
        return ''
            . ' LEFT OUTER JOIN ('
            . ' SELECT rr.resource AS resource, max(COALESCE(rr.resource_id, p.resource_id)) AS resource_id, p.permission AS permission'
            . ' FROM resource_role AS rr'
            . ' INNER JOIN permission AS p ON (rr.role = p.role OR p.role = "default") AND rr.resource = p.resource'
            . ' WHERE p.privilege = "get"'
            . ' AND p.resource = :generalResource'
            . ' AND rr.user_id = :userId'
            . ' GROUP BY permission'
/*
            . '     SELECT resource AS acl_resource, MIN(permission) AS acl_permission'
            . '     FROM permission'
            . '     WHERE role IN ('
            . '         SELECT role'
            . '         FROM resource_role AS acl_rr'
            . '         WHERE acl_rr.user_id = :userId'
            . '         AND (acl_rr.resource = :generalResource OR substr(acl_rr.resource, 1, length(permission.resource)) = permission.resource)'
            . '         UNION'
            . '         SELECT "default" AS role'
            . '     )'
            . '     AND privilege = "get"'
            . '     GROUP BY resource'
*/
            . ' ) AS acl ON acl_resource = (:generalResource || "=" || resource.id)'
            . '';
    }

    protected function _getGenericAclListWheres()
    {
        return ''
            . ' (acl_permission = "allow"' . ($this->isAllowed('get') ? ' OR acl_permission IS NULL' : '') . ')'
            . '';
    }

    protected function _getGenericAclListParams()
    {
        return array(
            ':userId' => $this->getAclContextUser(),
            ':generalResource' => $this->getResourceId(),
        );
    }
}
