<?php
require_once('Rest/Model/AclHandler/SimpleTableMapAbstract.php');
require_once('Rest/Model/EntourageImplementer/Interface.php');
require_once('Util/Sql.php');

class Default_Model_AclHandler_Community
    extends Rest_Model_AclHandler_SimpleTableMapAbstract
    implements Rest_Model_EntourageImplementer_Interface
{

    protected $_roles = array(
        'owner',
        'member',
    );

    protected $_staticPermissions = array(
        'default' => array(
            'allow' => array('get'),
        ),
        'owner' => array(
            'allow' => array('get', 'put', 'delete', 'post'),
        ),
        'member' => array(
            'allow' => array('get'),
        ),
    );

    /**
     * Used mainly for testing property requests, where clauses and the like
     * @return array
     */
    public static function getPropertyKeys()
    {
        return array('id', 'title', 'pic');
    }

    /**
     * @param string $alias
     * @return array
     */
    public function expandEntourageAlias($alias)
    {
        if ('Discussion' == $alias) {
            return array(
                'entourageModel' => 'Discussion',
                'entourageIdKey' => 'community_id',
                'resourceIdKey' => 'title',
            );
        }
        return null;
    }

    protected $_resourceName = 'Community';

    protected $_defaultListWhere = array('title', 'id');

    protected $_defaultListSort = array('title');

    protected function _getListResourceSqlFragment()
    {
        return ''
            . ' SELECT resource.id AS id, title, pic'
            . ' FROM community AS resource'
            . '';
    }

    /**
     * @param array $id
     */
    protected function _delete_pre_persist(array &$id)
    {
        // check for dependents
        $entriesHandler = new Default_Model_AclHandler_Entry();
        $children = $entriesHandler->getList(array('where' => array('community_id' => $id['id'])));
        if ($childer) {
// ******************
// TODO, NOT DONE YET
// ******************
        }

        if (true) {
            throw new Rest_Model_ConflictException('');
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
            $this->_dbTable = new Default_Model_DbTable_Community();
        }
        return $this->_dbTable;
    }
}
