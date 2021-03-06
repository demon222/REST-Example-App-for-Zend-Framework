<?php
require_once('Rest/Model/AclHandler/Abstract.php');
require_once('Rest/Model/UnauthorizedException.php');
require_once('Rest/Model/NotFoundException.php');

abstract class Rest_Model_AclHandler_StandardAbstract
    extends Rest_Model_AclHandler_Abstract
{
    /**
     * @var PDO
     */
    protected $_dbHandler;

    /**
     * @var objects that
     */
    protected $_permissionDependency = array();

    /**
     * @param array $id
     * @return array
     * @throws Rest_Model_Exception
     */
    public function get(array $id, array $params = null)
    {
        if (isset($params['entourage'])) {
            $entourageHandler = new Default_Model_AclHandler_Entourage($this->getAcl(), $this->getAclContextUser());
            return $entourageHandler->get($id, array($this, $params['entourage']));
        }

        try {

            $this->_assertAllowed('get', $id);

        } catch (Rest_Model_UnauthorizedException $e) {
            // resources are secret if not Acl approved, say it doesn't exist
            throw new Rest_Model_NotFoundException();
        }

        $item = $this->_get($id);

        $this->_assertDependencyAllowed('get', $item);

        return $item;
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
     * @throws Rest_Model_Exception
     */
    public function put(array $id, array $prop = null)
    {
        try {

            $this->_assertAllowed('put', $id);

        } catch (Rest_Model_UnauthorizedException $e) {
            // acl check failed, user can't user this method but if they can
            // access the resource by get then it isn't secret, just permissions
            try {
                $this->get($id);
            } catch (Exception $e) {
                throw new Rest_Model_NotFoundException();
            }
            // will throw Rest_Model_UnauthorizedException if not allowed
            throw $e;
        }

        $this->_assertDependencyAllowed('put', $id);

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
     * @throws Rest_Model_Exception
     */
    public function delete(array $id)
    {
        try {

            $this->_assertAllowed('delete', $id);

        } catch (Rest_Model_UnauthorizedException $e) {
            // acl check failed, user can't user this method but if they can
            // access the resource by get then it isn't secret, just permissions
            try {
                $this->get($id);
            } catch (Exception $e) {
                throw new Rest_Model_NotFoundException();
            }
            // will throw Rest_Model_UnauthorizedException if not allowed
            throw $e;
        }

        $this->_assertDependencyAllowed('delete', $id);

        $this->_delete($id);
    }

    /**
     * @param array $id
     */
    abstract protected function _delete(array $id);

    /**
     * @param array $prop
     * @return array
     * @throws Rest_Model_Exception
     */
    public function post(array $prop)
    {
        // will throw Rest_Model_UnauthorizedException if not allowed
        $this->_assertAllowed('post');

        $this->_assertDependencyAllowed('post', $prop);

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
     * @throws Rest_Model_Exception
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
        throw new Rest_Model_UnauthorizedException($method . ' for ' . $this->getResourceId() . ' is not allowed');
    }

    /**
     * @param array $items
     * @param string $throwOrFitler
     * @thows Rest_Model_Exception
     */
    protected function _assertDependencyAllowed($method, array $item)
    {
        if (0 == count($this->_permissionDependency)) {
            // no depencies, done
            return;
        }

        foreach ($this->_permissionDependency as $modelName => $identityMapping) {
            $parentResourceHandler = $this->_createAclHandler($modelName);

            $parentId = array();
            foreach ($identityMapping as $partialParentId => $partialResourceId) {
                if (!isset($item[$partialResourceId])) {
                    // might be that id was passed for dependency check, get full item
                    $item = $this->get($item);
                }
                if (!isset($item[$partialResourceId])) {
                    // stil having troubles, the wrong resource id was specified
                    throw new Exception($partialResourceId . ' is not a valid property to pull from for a parent dependency to ' . $modelName);
                }
                $parentId[$partialParentId] = $item[$partialResourceId];
            }

            try {
                $parentResourceHandler->get($parentId);
            } catch (Rest_Model_NotFoundException $e) {
                // if the resource doesn't exist for 'get' request throw that
                // access to this resource is unauthorized (because of
                // dependency check failure)
                throw new Rest_Model_UnauthorizedException($method . ' for ' . $this->getResourceId() . ' is not allowed');
            }
        }
    }

    /**
     * @param array $items
     * @param string $throwOrFitler
     * @thows Rest_Model_Exception
     */
    protected function _filterDependenciesNotAllowed(array &$items)
    {
        if (0 == count($this->_permissionDependency)) {
            // no depencies, done
            return;
        }

        foreach ($this->_permissionDependency as $modelName => $identityMapping) {

            $parentResourceHandler = $this->_createAclHandler($modelName);

            $cache = array();

            $filtered = array();
            foreach ($items  as $i => $item) {
                $parentId = array();
                foreach ($identityMapping as $partialParentId => $partialResourceId) {
                    $parentId[$partialParentId] = $item[$partialResourceId];
                }

                // going to check cache before calling the parent resource get
                // and doing all the db work that comes with that
                $cacheKey = implode(',', $parentId);

                if (isset($cache[$cacheKey])) {
                    if ($cache[$cacheKey]) {
                        $filtered[$i] = true;
                    }
                    continue;
                }

                $cache[$cacheKey] = false;
                try {
                    $parentResourceHandler->get($parentId);
                } catch (Rest_Model_NotFoundException $e) {
                    $filtered[$i] = true;
                    $cache[$cacheKey] = true;
                }
            }

            $items = array_diff_key($items, $filtered);
        }
    }

    /**
     * Loops through the roles to check for one that is allowed for the method.
     *
     * @param string $method, same as what Zend_Acl referers to as 'privilege' but 'method' used for REST context
     * @return boolean
     */
    public function isAllowed($privilege, array $roleResourceId = null, array $resourceId = null)
    {
        // for regular resources the $roleResourceId can be specified and
        // it is used for the resource as well. For permission type resources
        // $roleResourceId should be passed as null and will be determined
        if (null !== $roleResourceId && null === $resourceId) {
            $resourceId = $roleResourceId;
        }

        $user = $this->getAclContextUser();

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
                ':userId' => $user['id'],
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
                    ':userId' => $user['id'],
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
                    ':userId' => $user['id'],
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
