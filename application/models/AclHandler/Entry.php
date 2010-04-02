<?php
require_once('Rest/Model/AclHandler/SimpleTableMapAbstract.php');
require_once('Rest/Model/EntourageImplementer/Interface.php');
require_once('Util/Sql.php');

class Default_Model_AclHandler_Entry
    extends Rest_Model_AclHandler_SimpleTableMapAbstract
    implements Rest_Model_EntourageImplementer_Interface
{
    protected $_roles = array(
        'member',
        'owner',
    );

    protected $_staticPermissions = array(
        'default' => array(
            'allow' => array('get', 'post'),
            'deny' => array('put', 'delete'),
        ),
        'member' => array(
            'allow' => array('get'),
        ),
        'owner' => array(
            'allow' => array('get', 'put', 'delete'),
            'deny' => array('post'),
        ),
    );

    protected $_permissionDependency = array(
        'Discussion' => array('id' => 'discussion_id')
    );

    /**
     * Used mainly for testing property requests, where clauses and the like
     * @return array
     */
    public static function getPropertyKeys()
    {
        return array('id', 'discussion_id', 'comment', 'creator_user_id', 'modified');
    }

    /**
     * @param string $alias
     * @return array
     */
    public function expandEntourageAlias($alias)
    {
        if ('Creator' == $alias) {
            return array(
                'entourageModel' => 'User',
                'entourageIdKey' => 'id',
                'resourceIdKey' => 'creator_user_id',
                'singleOnly' => true,
            );
        }
        if ('Discussion' == $alias) {
            return array(
                'entourageModel' => 'Discussion',
                'entourageIdKey' => 'id',
                'resourceIdKey' => 'discussion_id',
                'singleOnly' => true,
            );
        }
        if ('DiscussionWithCommunity' == $alias) {
            return array(
                'entourageName' => 'Discussion',
                'entourageModel' => 'Discussion',
                'entourageIdKey' => 'id',
                'resourceIdKey' => 'discussion_id',
                'singleOnly' => true,
                'entourage' => 'Community',
            );
        }
        return null;
    }

    protected $_resourceName = 'Entry';

    protected $_defaultListWhere = array('comment', 'creator_user_id');

    protected $_defaultListSort = array('modified');

    protected $_getListResourceSqlFragment = '
        SELECT id, discussion_id, comment, creator_user_id, modified
        FROM entry AS resource
        ';

    /**
     * @param array $item
     */
    protected function _put_pre_persist(array &$item)
    {
        $item['modified'] = date('Y-m-d H:i:s');

        // not allowing creator to be changed
        if (isset($item['creator_user_id'])) {
            unset($item['creator_user_id']);
        }
    }

    /**
     * @param array $item
     */
    protected function _post_pre_persist(array &$item)
    {
        // force the user to be that from the acl context
        $item['creator_user_id'] = $this->getAclContextUser();

        $item['modified'] = date('Y-m-d H:i:s');

        // make sure that the user is valid
        $userTable = new Default_Model_DbTable_User();
        $resultSet = $userTable->find($item['creator_user_id']);
        if (false === current($resultSet)) {
            throw new Rest_Model_BadRequestException('creator_user_id does not match with an existing user');
        }
    }

    /**
     * Get registered Zend_Db_Table instance, lazy load
     *
     * @return Zend_Db_Table_Abstract
     */
    protected function _getDbTable()
    {
        if (null === $this->_dbTable) {
            $this->_dbTable = new Default_Model_DbTable_Entry();
        }
        return $this->_dbTable;
    }
}
