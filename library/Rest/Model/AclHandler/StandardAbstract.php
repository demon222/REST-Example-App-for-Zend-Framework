<?php
require_once('Rest/Model/AclHandler/Abstract.php');

abstract class Rest_Model_AclHandler_StandardAbstract
    extends Rest_Model_AclHandler_Abstract
{

    /**
     * @var PDO
     */
    protected $_dbHandler;

    /**
     * @param array $id
     * @return array
     * @throws Zend_Acl_Exception
     */
    public function get(array $id, array $params = null)
    {
        if (isset($params['entourage'])) {
            $entourageHandler = new Default_Model_AclHandler_Entourage($this->getAcl(), $this->getAclContextUser());
            return $entourageHandler->get($id, array($this, $params['entourage']));
        }

        // will throw Zend_Acl_Exception is not allowed
        $this->_assertAllowed('get', $id);

        return $this->_get($id);
    }

    /**
     * @param array $id
     * @return array
     */
    abstract protected function _get(array $id);

    /**
     * @param array $id
     * @param array $prop
     * @return array
     * @throws Zend_Acl_Exception
     */
    public function put(array $id, array $prop = null)
    {
        // will throw Zend_Acl_Exception is not allowed
        $this->_assertAllowed('put', $id);

        return $this->_put($id, $prop);
    }

    /**
     * @param array $id
     * @param array $prop
     * @return array
     */
    abstract protected function _put(array $id, array $prop = null);

    /**
     * @param array $id
     * @throws Zend_Acl_Exception
     */
    public function delete(array $id)
    {
        // will throw Zend_Acl_Exception is not allowed
        $this->_assertAllowed('delete', $id);

        $this->_delete($id);
    }

    /**
     * @param array $id
     */
    abstract protected function _delete(array $id);

    /**
     * @param array $prop
     * @return array
     * @throws Zend_Acl_Exception
     */
    public function post(array $prop)
    {
        // will throw Zend_Acl_Exception is not allowed
        $this->_assertAllowed('post');

        return $this->_post($prop);
    }

    /**
     * @param array $prop
     * @return array
     */
    abstract protected function _post(array $prop);

    /**
     * @param string $method
     * @param array $id
     * @throws Zend_Acl_Exception
     */
    protected function _assertAllowed($method, $id = null)
    {
        if (!$this->getAcl()) {
            return;
        }
        if ($this->getRoleResourceId() == $this->getResourceId() && $this->isAllowed($method, $id)) {
            return;
        }
        if ($this->getRoleResourceId() != $this->getResourceId() && $this->isAllowed($method, null, $id)) {
            return;
        }
        // oh no, the user isn't allowed, say so
        throw new Zend_Acl_Exception($method . ' for ' . $this->getResourceId() . ' is not allowed');
    }

    /**
     * Loops through the roles to check for one that is allowed for the method.
     *
     * @param string $method, same as what Zend_Acl referers to as 'privilege' but 'method' used for REST context
     * @return boolean
     */
    public function isAllowed($privilege, array $roleResourceId = null, $resourceId = null)
    {
        // for regular resources the $roleResourceId can be specified and
        // it is used for the resource as well. For permission type resources
        // $roleResourceId should be passed as null and will be determined
        if (null !== $roleResourceId && null === $resourceId) {
            $resourceId = $roleResourceId;
        }

        $userId = $this->getAclContextUser();

        $roleResourceGeneral = $this->getRoleResourceId();

        // first get the possible roles this user has with the resource
        if (null === $resourceId) {
            $sql = 'SELECT role FROM resource_role'
                . ' WHERE user_id = :userId'
                . ' AND resource = :roleResourceGeneral'
                . ' AND resource_id IS NULL'
                . '';
            $query = $this->_getDbHandler()->query($sql);
            $query->execute(array(
                ':userId' => $userId,
                ':roleResourceGeneral' => $roleResourceGeneral,
            ));
        } else {
            if (null === $roleResourceId) {
                $sql = 'SELECT role FROM resource_role'
                    . ' WHERE user_id = :userId'
                    . ' AND ('
                    . '     resource = :roleResourceGeneral'
                    . '     AND id = :resourceId'
                    . ' )'
                    . '';
                $query = $this->_getDbHandler()->query($sql);
                $query->execute(array(
                    ':userId' => $userId,
                    ':roleResourceGeneral' => $roleResourceGeneral,
                    ':resourceId' => $resourceId['id'],
                ));
            } else {
                // include roles from specific case
                $roleResourceSpecific = $this->getSpecificRoleResourceId($roleResourceId);

                $sql = 'SELECT role FROM resource_role'
                    . ' WHERE user_id = :userId'
                    . ' AND ('
                    . '     resource = :roleResourceGeneral'
                    . '     AND ('
                    . '         resource_id IS NULL'
                    . '         OR resource_id = :roleResourceSpecific'
                    . '     )'
                    . ' )'
                    . '';
                $query = $this->_getDbHandler()->query($sql);
                $query->execute(array(
                    ':userId' => $userId,
                    ':roleResourceGeneral' => $roleResourceGeneral,
                    ':roleResourceSpecific' => $roleResourceSpecific,
                ));
            }
        }
        $rowSet = $query->fetchAll(PDO::FETCH_ASSOC);
        $roleSet = Util_Array::arrayFromKeyValuesOfSet('role', $rowSet);
        // make sure 'default' role is in there
        if (!in_array('default', $roleSet)) {
            $roleSet[] = 'default';
        }

        $resourceGeneral = $this->getResourceId();

        if (null !== $resourceId && null !== $roleResourceId) {
            $resourceSpecific = $this->getSpecificResourceId($resourceId);

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
        $nl = "\n";

        $staticPermissionsSql = '';
        foreach ($this->_staticPermissions as $role => $permissionSet) {
            foreach($permissionSet as $permission => $privilegeSet) {
                if (!in_array('get', $privilegeSet)) {
                    continue;
                }
                $staticPermissionsSql .= 'UNION SELECT NULL, "' . $role . '", "' . $permission . '"' . $nl;
            }
        }

        return ''
            . ' LEFT OUTER JOIN (' . $nl
            . '     SELECT COALESCE(rr.resource_id, p.resource_id) AS acl_resource_id, min(p.permission) AS acl_permission' . $nl
            . '     FROM resource_role AS rr' . $nl
            . '     INNER JOIN (' . $nl
            . '         SELECT resource_id, role, permission FROM permission' . $nl
            . '         WHERE resource = :generalResource' . $nl
            . '         AND privilege = "get"' . $nl
            . '         ' . $staticPermissionsSql
            . '     ) AS p ON (rr.role = p.role OR p.role = "default")' . $nl
            . '     WHERE rr.user_id = :userId' . $nl
            . '     AND rr.resource = :generalRoleResource' . $nl
            . '     GROUP BY acl_resource_id' . $nl
            . ' ) AS acl ON acl_resource_id = resource.id' . $nl
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
            ':generalRoleResource' => $this->getRoleResourceId(),
        );
    }

    /**
     * @return PDO
     */
    protected function _getDbHandler()
    {
        if (null === $this->_dbHandler) {
            $config = Zend_Registry::getInstance()->get('config');
            $this->_dbHandler = new PDO('sqlite:' . $config['resources']['db']['params']['dbname']);
        }
        return $this->_dbHandler;
    }
}