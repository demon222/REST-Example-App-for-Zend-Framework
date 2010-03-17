<?php
require_once('Rest/Model/AclHandler/Abstract.php');
require_once('Rest/Model/EntourageImplementer/Interface.php');
require_once('Util/Sql.php');

class Default_Model_AclHandler_Entry_Owner
    extends Rest_Model_AclHandler_Abstract
    implements Rest_Model_EntourageImplementer_Interface
{

    /**
     * @var PDO
     */
    protected $_dbHandler;

    protected $_roles = array(
        'owner',
        'admin'
    );

    protected $_staticPermissions = array(
        'default' => array(
            'deny' => array('get', 'put', 'delete', 'post'),
        ),
        'owner' => array(
            'allow' => array('get', 'put', 'delete', 'post'),
        ),
        'admin' => array(
            'allow' => array('get', 'put', 'delete', 'post'),
        ),
    );

    public function getResourceId()
    {
        return 'Entry';
    }

    /**
     * @return array
     */
    public static function getIdentityKeys()
    {
        return array('id');
    }

    /**
     * Used mainly for testing property requests, where clauses and the like
     * @return array
     */
    public static function getPropertyKeys()
    {
        return array('id', 'entry_id', 'user_id');
    }

    /**
     * @param string $alias
     * @return array
     */
    public function expandEntourageAlias($alias)
    {
        if ('User' == $alias) {
            return array(
                'entourageModel' => 'User',
                'entourageIdKey' => 'id',
                'resourceIdKey' => 'user_id',
                'singleOnly' => true,
            );
        }
        if ('Entry' == $alias) {
            return array(
                'entourageModel' => 'Entry',
                'entourageIdKey' => 'id',
                'resourceIdKey' => 'entry_id',
                'singleOnly' => true,
            );
        }
        return null;
    }

    /**
     * @param array $params
     * @return array
     */
    public function getList(array $params = null)
    {
        $params = is_array($params) ? $params : array();

        if (isset($params['entourage'])) {
            $entourageHandler = new Default_Model_AclHandler_Entourage($this->getAcl(), $this->getAclContextUser());
            $data = $entourageHandler->getList(array('Entry' => $params));
            return $data['Entry'];
        }

        if (isset($params['where'])) {
            // use default properties to search against if none are provided
            if (!is_array($params['where'])) {
                $params['where'] = array('comment creator_user_id' => $params['where']);
            }
        } else {
            $params['where'] = array();
        }

        if (!isset($params['sort']) || !is_array($params['sort'])) {
            $params['sort'] = array('entry_id');
        }

        $whereAndSet = Util_Sql::generateSqlWheresAndParams($params['where'], $this->getPropertyKeys());
        $sortList = Util_Sql::generateSqlSort($params['sort'], $this->getPropertyKeys());

        $sql = ''
            // RESOURCE
            . ' SELECT rr.id, rr.resource_id AS entry_id, rr.user_id'
            . ' FROM entry AS resource'
            . ' INNER JOIN resource_role AS rr ON rr.resource_id = resource.id AND rr.role = "owner" AND rr.resource = "Entry"'

            // ACL
            . $this->_getGenericAclListJoins()

            // ACL
            . ' WHERE ' . $this->_getGenericAclListWheres()

            // RESOURCE
            . ' AND ' . implode(' AND ', array_merge($whereAndSet['sql'], array('1 = 1')))
            . ' ORDER BY ' . implode(', ', $sortList)

            . '';

        $query = $this->_getDbHandler()->prepare($sql);
var_dump($this->_getDbHandler()->errorInfo());
exit();
        $query->execute(array_merge($this->_getGenericAclListParams(), $whereAndSet['param']));
        $rowSet = $query->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param array $id
     * @return array
     * @throws Rest_Model_NotFoundException, Zend_Acl_Exception
     */
    public function get(array $id, array $params = null)
    {
        if (isset($params['entourage'])) {
            $entourageHandler = new Default_Model_AclHandler_Entourage($this->getAcl(), $this->getAclContextUser());
            return $entourageHandler->get($id, array($this, $params['entourage']));
        }

        if ($this->getAcl() && !$this->isAllowed('get', $id)) {
            throw new Zend_Acl_Exception('get for ' . $this->getResourceId() . ' is not allowed');
        }

        return $this->_get($id);
    }

    public function _get(array $id, array $params = null)
    {
        $dbTable = new Default_Model_DbTable_ResourceRole();

        $result = $dbTable->find(array('id = ?' => $id['id'], 'resource = ?' => 'Entry', 'role = ?' => 'owner'));

        if (0 == count($result)) {
            throw new Rest_Model_NotFoundException();
        }

        // 1 to 1, same names
        $keys = $this->getPropertyKeys();
        $map = array_combine($keys, $keys);

        return Util_Array::mapIntersectingKeys($result->current()->toArray(), $map);
    }

    /**
     * @param array $id
     * @param array $prop
     * @return array
     * @throws Rest_Model_NotFoundException, Zend_Acl_Exception
     */
    public function put(array $id, array $prop = null)
    {
        if ($this->getAcl() && !$this->isAllowed('put', $id)) {
            throw new Zend_Acl_Exception('put for ' . $this->getResourceId() . ' is not allowed');
        }

        return $this->_put($id, $prop);
    }

    public function _put(array $id, array $prop = null)
    {
        $dbTable = new Default_Model_DbTable_ResourceRole();

        // if a seperate $prop list is not provided, use the $id list
        if ($prop === null) {
            $prop = $id;
        }

        // could probably implement renaming by having 'id' set by $prop but
        // not going to try to debug that right now
        // 1 to 1, same names
        $keys = array_diff($this->getPropertyKeys(), $this->getIdentityKeys());
        $map = array_combine($keys, $keys);

        $item = Util_Array::mapIntersectingKeys($prop, $map);

        $updated = $dbTable->update($item, array('id = ?' => $id['id'], 'resource = ?' => 'Entry', 'role = ?' => 'owner'));

        if ($updated <= 0) {
            throw new Rest_Model_NotFoundException();
        }

        return $item;
    }

    /**
     * @param array $id
     * @throws Rest_Model_NotFoundException, Zend_Acl_Exception
     */
    public function delete(array $id)
    {
        if ($this->getAcl() && !$this->isAllowed('delete', $id)) {
            throw new Zend_Acl_Exception('delete for ' . $this->getResourceId() . ' is not allowed');
        }

        $this->_delete($id);
    }

    public function _delete(array $id)
    {
        $dbTable = new Default_Model_DbTable_ResourceRole();

        $deleted = $dbTable->delete(array('id = ?' => $id['id'], 'resource = ?' => 'Entry', 'role = ?' => 'owner'));

        if ($deleted == 0) {
            throw new Rest_Model_NotFoundException();
        }
    }

    /**
     * @param array $prop
     * @return array
     * @throws Zend_Acl_Exception
     */
    public function post(array $prop)
    {
        if ($this->getAcl() && !$this->isAllowed('post')) {
            throw new Zend_Acl_Exception('post for ' . $this->getResourceId() . ' is not allowed');
        }

        return $this->_post($prop);
    }

    public function _post(array $prop)
    {
        $dbTable = new Default_Model_DbTable_ResourceRole();

        $keys = array_diff($this->getPropertyKeys(), $this->getIdentityKeys());
        $map = array_combine($keys, $keys);

        $item = Util_Array::mapIntersectingKeys($prop, $map);

        $item['resource'] = 'Entry';
        $item['role'] = 'owner';

        $id = $dbTable->insert($item);

        if ($id === null) {
            return Exception('Unable to post into databse, not sure why');
        }

        $item['id'] = $id;

        return $item;
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
            $resourceSpecific = $this->getSpecificResourceId($id);

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
            . '     AND rr.resource = :generalResource' . $nl
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
