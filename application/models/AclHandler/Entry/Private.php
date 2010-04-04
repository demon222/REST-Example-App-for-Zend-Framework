<?php
require_once('Rest/Model/AclHandler/SimpleTableMapAbstract.php');
require_once('Rest/Model/EntourageImplementer/Interface.php');
require_once('Util/Sql.php');

class Default_Model_AclHandler_Entry_Private
    extends Rest_Model_AclHandler_SimpleTableMapAbstract
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

    protected $_resourceName = 'Entry_Private';

    protected $_defaultListWhere = array('entry_id');

    protected $_defaultListSort = array('entry_id');

    protected $_getListResourceSqlFragment = '
        SELECT resource.id AS entry_id
        FROM entry AS resource
        INNER JOIN permission AS p1 ON p1.resource_id = resource.id AND p1.resource = "Entry" AND p1.role = "default" AND p1.privilege = "get" AND p1.permission = "deny"
        INNER JOIN permission AS p2 ON p2.resource_id = resource.id AND p2.resource = "Entry" AND p2.role = "member" AND p2.privilege = "get" AND p2.permission = "allow"
        ';

    public function _get(array $id, array $params = null)
    {
        $this->_assertValidId($id);

        $dbTable = $this->_getDbTable();

        $result = $dbTable->find(array('id = ?' => $id['id'], 'resource = ?' => 'Entry', 'role = ?' => 'default', 'privilege = ?' => 'get', 'permission = ?' => 'deny'));
        if (0 == count($result)) {
            throw new Rest_Model_NotFoundException();
        }

        $result = $dbTable->find(array('id = ?' => $id['id'], 'resource = ?' => 'Entry', 'role = ?' => 'member', 'privilege = ?' => 'get', 'permission = ?' => 'allow'));
        if (0 == count($result)) {
            throw new Rest_Model_NotFoundException();
        }

        return $id;
    }

    public function _put(array $id, array $prop = null)
    {
        $this->_assertValidId($id);

        $dbTable = $this->_getDbTable();

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
        $this->_assertValidId($id);

        $dbTable = $this->_getDbTable();

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

    /**
     * Get registered Zend_Db_Table instance, lazy load
     *
     * @return Zend_Db_Table_Abstract
     */
    protected function _getDbTable()
    {
        if (null === $this->_dbTable) {
            $this->_dbTable = new Default_Model_DbTable_Permission();
        }
        return $this->_dbTable;
    }
}
