<?php
require_once('Rest/Model/AclHandler/SimpleTableMapAbstract.php');
require_once('Rest/Model/EntourageImplementer/Interface.php');
require_once('Util/Sql.php');

class Default_Model_AclHandler_Entry_Selected
    extends Rest_Model_AclHandler_SimpleTableMapAbstract
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
        return 'Entry_Selected';
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

    protected $_resourceName = 'Entry_Selected';

    protected $_defaultListWhere = array('entry_id', 'user_id');

    protected $_defaultListSort = array('entry_id');

    protected $_getListResourceSqlFragment = '
        SELECT rr.id, rr.resource_id AS entry_id, rr.user_id
        FROM entry AS resource
        INNER JOIN resource_role AS rr ON rr.resource_id = resource.id AND rr.role = "selected" AND rr.resource = "Entry"
        ';

    protected function _getPrePersist(array &$id)
    {
        $id['resource = ?'] = 'Entry';
        $id['role = ?'] = 'selected';
    }

    protected function _getPostPersist(array &$item)
    {
        // map between internal and external property names
        $item['entry_id'] = $item['resource_id'];
        unset($item['resource_id']);
    }

    protected function _putPrePersist(array &$id, array &$item)
    {
        $id['resource = ?'] = 'Entry';
        $id['role = ?'] = 'selected';

        // map between external and internal property names
        $item['resource_id'] = $item['entry_id'];
        unset($item['entry_id']);
    }

    protected function _putPostPersist(array &$item)
    {
        // map between internal and external property names
        $item['entry_id'] = $item['resource_id'];
        unset($item['resource_id']);
    }

    protected function _deletePrePersist(array &$id)
    {
        $id['resource = ?'] = 'Entry';
        $id['role = ?'] = 'selected';
    }

    protected function _postPrePersist(array &$item)
    {
        $item['resource'] = 'Entry';
        $item['role'] = 'selected';

        // map between external and internal property names
        $item['resource_id'] = $item['entry_id'];
        unset($item['entry_id']);
    }

    protected function _postPostPersist(array &$item)
    {
        // map between internal and external property names
        $item['entry_id'] = $item['resource_id'];
        unset($item['resource_id']);
    }

    protected function _getDbTable()
    {
        if (null === $this->_dbTable) {
            $this->_dbTable = new Default_Model_DbTable_ResourceRole();
        }
        return $this->_dbTable;
    }
}
