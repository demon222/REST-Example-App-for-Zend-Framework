<?php
require_once('Rest/Model/AclHandler/StandardAbstract.php');
require_once('Rest/Model/EntourageImplementer/Interface.php');
require_once('Util/Sql.php');

class Default_Model_AclHandler_Entry_Private
    extends Rest_Model_AclHandler_StandardAbstract
    implements Rest_Model_EntourageImplementer_Interface
{
    // a key part of this private model is that it has two components: the
    // permission denying the default role for get on entry and the permission
    // allowing the members role for get on entry.

    protected $_roles = array(
        'owner',
        'member',
    );

    protected $_staticPermissions = array(
        'owner' => array(
            'allow' => array('get', 'put', 'delete'),
        ),
    );

    /**
     * @return string
     */
    public function getResourceId()
    {
        return 'Entry_Private';
    }

    /**
     * @return string
     */
    public function getRoleResourceId()
    {
        return 'Entry';
    }

    /**
     * @return array
     */
    public static function getIdentityKeys()
    {
        return array('entry_id');
    }

    /**
     * Used mainly for testing property requests, where clauses and the like
     * @return array
     */
    public static function getPropertyKeys()
    {
        return array('entry_id');
    }

    /**
     * @param string $alias
     * @return array
     */
    public function expandEntourageAlias($alias)
    {
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
            $data = $entourageHandler->getList(array('Entry_Private' => $params));
            return $data['Entry_Private'];
        }

        if (isset($params['where'])) {
            // use default properties to search against if none are provided
            if (!is_array($params['where'])) {
                $params['where'] = array('entry_id' => $params['where']);
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
            . ' SELECT resource.id AS entry_id'
            . ' FROM entry AS resource'
            . ' INNER JOIN permission AS p1 ON p1.resource_id = resource.id AND p1.resource = "Entry" AND p1.role = "default" AND p1.privilege = "get" AND p1.permission = "deny"'
            . ' INNER JOIN permission AS p2 ON p2.resource_id = resource.id AND p2.resource = "Entry" AND p2.role = "member" AND p2.privilege = "get" AND p2.permission = "allow"'

            // ACL
            . $this->_getGenericAclListJoins()

            // ACL
            . ' WHERE ' . $this->_getGenericAclListWheres()

            // RESOURCE
            . ' AND ' . implode(' AND ', array_merge($whereAndSet['sql'], array('1 = 1')))
            . ' ORDER BY ' . implode(', ', $sortList)

            . '';

        $query = $this->_getDbHandler()->prepare($sql);
        $query->execute(array_merge($this->_getGenericAclListParams(), $whereAndSet['param']));
        $rowSet = $query->fetchAll(PDO::FETCH_ASSOC);

        return $rowSet;
    }

    public function _get(array $id, array $params = null)
    {
        $dbTable = new Default_Model_DbTable_Permission();

        $result = $dbTable->find(array('id = ?' => $id['id'], 'resource = ?' => 'Entry', 'role = ?' => 'default', 'privilege = ?' => 'get', 'permission = ?' => 'deny'));
        if (0 == count($result)) {
            throw new Rest_Model_NotFoundException();
        }

        $result = $dbTable->find(array('id = ?' => $id['id'], 'resource = ?' => 'Entry', 'role = ?' => 'member', 'privilege = ?' => 'get', 'permission = ?' => 'allow'));
        if (0 == count($result)) {
            throw new Rest_Model_NotFoundException();
        }

        // 1 to 1, same names
        $keys = $this->getPropertyKeys();
        $map = array_combine($keys, $keys);

        return Util_Array::mapIntersectingKeys($id, $map);
    }

    public function _put(array $id, array $prop = null)
    {
        $dbTable = new Default_Model_DbTable_Permission();

        $dbTable->getDefaultAdapter()->beginTransaction();

        $id = $dbTable->insert(array('id = ?' => $id['id'], 'resource = ?' => 'Entry', 'role = ?' => 'default', 'privilege = ?' => 'get', 'permission = ?' => 'deny'));
        if ($id === null) {
            throw Exception('Unable to post into databse, not sure why');
        }

        $id = $dbTable->insert(array('id = ?' => $id['id'], 'resource = ?' => 'Entry', 'role = ?' => 'member', 'privilege = ?' => 'get', 'permission = ?' => 'allow'));
        if ($id === null) {
            throw Exception('Unable to post into databse, not sure why');
        }

        $dbTable->getDefaultAdapter()->commit();
    }

    public function _delete(array $id)
    {
        $dbTable = new Default_Model_DbTable_Permission();

        $dbTable->getDefaultAdapter()->beginTransaction();

        $deleted = $dbTable->delete(array('id = ?' => $id['id'], 'resource = ?' => 'Entry', 'role = ?' => 'default', 'privilege = ?' => 'get', 'permission = ?' => 'deny'));
        if ($deleted == 0) {
            throw new Rest_Model_NotFoundException();
        }

        $deleted = $dbTable->delete(array('id = ?' => $id['id'], 'resource = ?' => 'Entry', 'role = ?' => 'member', 'privilege = ?' => 'get', 'permission = ?' => 'allow'));
        if ($deleted == 0) {
            throw new Rest_Model_NotFoundException();
        }

        $dbTable->getDefaultAdapter()->commit();
    }

    public function _post(array $prop)
    {
        // creation is handled by put because there are no properties, shouldn't
        // be able to get here, how did you get here?!, go now and fix the
        // permission that someone messed up, go up there and remove allow
        // for post
        throw Exception('Unable to post into databse, not sure why');
    }
}
