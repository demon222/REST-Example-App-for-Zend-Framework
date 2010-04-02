<?php
require_once('Rest/Model/AclHandler/SimpleTableMapAbstract.php');
require_once('Rest/Model/EntourageImplementer/Interface.php');
require_once('Util/Sql.php');

class Default_Model_AclHandler_User
    extends Rest_Model_AclHandler_SimpleTableMapAbstract
    implements Rest_Model_EntourageImplementer_Interface
{

    protected $_roles = array(
        'owner',
    );

    protected $_staticPermissions = array(
        'default' => array(
            'allow' => array('get'),
            'deny' => array('put', 'delete', 'post'),
        ),
        'owner' => array(
            'allow' => array('get', 'put', 'delete', 'post'),
        )
    );

    /**
     * Used mainly for testing property requests, where clauses and the like
     * @return array
     */
    public static function getPropertyKeys()
    {
        return array('id', 'username', 'name');
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
                'entourageIdKey' => 'creator_user_id',
                'resourceIdKey' => 'id',
            );
        }
        if ('PrimaryEmail' == $alias) {
            return array(
                'entourageModel' => 'Email',
                'entourageIdKey' => 'user_id',
                'resourceIdKey' => 'id',
                'singleOnly' => true,
                // doesn work because primary isn't a real column and SQLite
                // isn't going to where by that. Because this is singleOnly
                // the sort below works fine
                //'where' => array('primary' => 1),
                'sort' => array('primary desc'),
            );
        }
        if ('Email' == $alias) {
            return array(
                'entourageModel' => 'Email',
                'entourageIdKey' => 'user_id',
                'resourceIdKey' => 'id',
            );
        }
        return null;
    }

    protected $_resourceName = 'User';

    protected $_defaultListWhere = array('comment', 'creator_user_id');

    protected $_defaultListSort = array('name');

    protected $_getListResourceSqlFragment = '
        SELECT id, username, name
        FROM user AS resource
        ';

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

        $item = $this->_getModelHandler()->post($prop);

        // NEED TO CREATE PERMISSION AND ROLE FOR THE NEW USER

        return $item;
    }

    /**
     * Get registered Zend_Db_Table instance, lazy load
     *
     * @return Zend_Db_Table_Abstract
     */
    protected function _getDbTable()
    {
        if (null === $this->_dbTable) {
            $this->_dbTable = new Default_Model_DbTable_User();
        }
        return $this->_dbTable;
    }
}
