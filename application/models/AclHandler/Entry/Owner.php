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

    protected $_resourceName = 'Entry_Owner';

    protected $_defaultListWhere = array('entry_id', 'user_id');

    protected $_defaultListSort = array('entry_id');

    protected $_getListResourceSqlFragment = '
        SELECT rr.id, rr.resource_id AS entry_id, rr.user_id
        FROM entry AS resource
        INNER JOIN resource_role AS rr ON rr.resource_id = resource.id AND rr.role = "owner" AND rr.resource = "Entry"
        ';

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
