<?php
require_once('Rest/Model/AclHandler/SimpleTableMapAbstract.php');
require_once('Rest/Model/EntourageImplementer/Interface.php');
require_once('Util/Sql.php');

class Default_Model_AclHandler_Email
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
        )
    );

    /**
     * Used mainly for testing property requests, where clauses and the like
     * @return array
     */
    public static function getPropertyKeys()
    {
        return array('id', 'user_id', 'email', 'primary');
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
        return null;
    }

    protected $_resourceName = 'Email';

    protected $_defaultListWhere = array('email', 'user_id');

    protected $_defaultListSort = array('user_id', 'primary asc', 'email');

    protected $_getListResourceSqlFragment = '
        SELECT resource.id AS id, user_id, email, (resource.id = primary_email_id) AS "primary"
        FROM email AS resource
        INNER JOIN user ON user.id = user_id
        ';

    /**
     * @param array $item
     */
    protected function _put_pre_persist(array &$item)
    {
        // disable transfering the email to a different user
        if (isset($item['user_id'])) {
            unset($item['user_id']);
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
            $this->_dbTable = new Default_Model_DbTable_Email();
        }
        return $this->_dbTable;
    }
}
