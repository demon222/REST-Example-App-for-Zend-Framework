<?php
require_once('Rest/Model/AclHandler/StandardAbstract.php');
require_once('Rest/Model/EntourageImplementer/Interface.php');
require_once('Util/Sql.php');

class Default_Model_AclHandler_Entry_Owner
    extends Rest_Model_AclHandler_StandardAbstract
    implements Rest_Model_EntourageImplementer_Interface
{

    protected $_roles = array(
        'owner',
        'member',
    );

    protected $_staticPermissions = array(
        'default' => array(
            'deny' => array('get', 'put', 'delete', 'post'),
        ),
        'owner' => array(
            'allow' => array('get', 'put', 'delete', 'post'),
        ),
    );

    /**
     * @return string
     */
    public function getResourceId()
    {
        return 'Entry_Owner';
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

    private static function _getInternalPropertyKeys()
    {
        return array('id', 'resource_id', 'user_id');
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
            $data = $entourageHandler->getList(array('Entry_Owner' => $params));
            return $data['Entry_Owner'];
        }

        if (isset($params['where'])) {
            // use default properties to search against if none are provided
            if (!is_array($params['where'])) {
                $params['where'] = array('entry_id user_id' => $params['where']);
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
        $query->execute(array_merge($this->_getGenericAclListParams(), $whereAndSet['param']));
        $rowSet = $query->fetchAll(PDO::FETCH_ASSOC);

        return $rowSet;
    }

    protected function _get(array $id, array $params = null)
    {
        $dbTable = new Default_Model_DbTable_ResourceRole();

        $result = $dbTable->find(array('id = ?' => $id['id'], 'resource = ?' => 'Entry', 'role = ?' => 'owner'));

        if (0 == count($result)) {
            throw new Rest_Model_NotFoundException();
        }

        // map from db properties to public resource properties
        $map = array_combine($this->_getInternalPropertyKeys(), $this->getPropertyKeys());

        return Util_Array::mapIntersectingKeys($result->current()->toArray(), $map);
    }

    protected function _put(array $id, array $prop = null)
    {
        $dbTable = new Default_Model_DbTable_ResourceRole();

        // if a seperate $prop list is not provided, use the $id list
        if ($prop === null) {
            $prop = $id;
        }

// currently the map of keys is messed up for PUT

        // could probably implement renaming by having 'id' set by $prop but
        // not going to try to debug that right now
        // 1 to 1, same names
        $keys = array_diff($this->getPropertyKeys(), $this->getIdentityKeys());
        $map = array_combine($keys, $keys);

        $item = Util_Array::mapIntersectingKeys($prop, $map);

        $updated = $dbTable->update($item, array('id = ?' => $id['id'], 'resource = ?' => 'Entry', 'role = ?' => 'owner'));

        // if it didn't exists, could create the resource at that id... but no
        if ($updated <= 0) {
            throw new Rest_Model_NotFoundException();
        }

        return $item;
    }

    protected function _delete(array $id)
    {
        $dbTable = new Default_Model_DbTable_ResourceRole();

        $deleted = $dbTable->delete(array('id = ?' => $id['id'], 'resource = ?' => 'Entry', 'role = ?' => 'owner'));

        if ($deleted == 0) {
            throw new Rest_Model_NotFoundException();
        }
    }

    protected function _post(array $prop)
    {
        $dbTable = new Default_Model_DbTable_ResourceRole();

        $keys = array_diff($this->getPropertyKeys(), $this->getIdentityKeys());
        $map = array_combine($keys, $keys);

        $item = Util_Array::mapIntersectingKeys($prop, $map);

        $item['resource'] = 'Entry';
        $item['role'] = 'owner';

        $id = $dbTable->insert($item);

        if ($id === null) {
            throw Exception('Unable to post into databse, not sure why');
        }

        $item['id'] = $id;

        return $item;
    }
}
