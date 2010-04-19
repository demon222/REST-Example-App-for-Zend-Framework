<?php
require_once('Rest/Model/AclHandler/SimpleTableMapAbstract.php');
require_once('Rest/Model/EntourageImplementer/Interface.php');
require_once('Util/Sql.php');

class Default_Model_AclHandler_Discussion
    extends Rest_Model_AclHandler_SimpleTableMapAbstract
    implements Rest_Model_EntourageImplementer_Interface
{

    protected $_roles = array(
        'owner',
        'member',
    );

    protected $_staticPermissions = array(
        'default' => array(
//            'allow' => array('get'),
        ),
        'owner' => array(
            'allow' => array('get', 'put', 'delete'),
        ),
        'member' => array(
            'allow' => array('get', 'post'),
        ),
    );

    protected $_permissionDependency = array(
        'Community' => array('id' => 'community_id')
    );

    /**
     * Used mainly for testing property requests, where clauses and the like
     * @return array
     */
    public static function getPropertyKeys()
    {
        return array('id', 'community_id', 'title', 'comment');
    }

    /**
     * @param string $alias
     * @return array
     */
    public function expandEntourageAlias($alias)
    {
        if ('Community' == $alias) {
            return array(
                'entourageModel' => 'Community',
                'entourageIdKey' => 'id',
                'resourceIdKey' => 'community_id',
                'singleOnly' => true,
            );
        }
        if ('Entries' == $alias) {
            return array(
                'entourageModel' => 'Entry',
                'entourageIdKey' => 'discussion_id',
                'resourceIdKey' => 'id',
            );
        }
        if ('EntriesWithCreator' == $alias) {
            return array(
                'entourageName' => 'Entries',
                'entourageModel' => 'Entry',
                'entourageIdKey' => 'discussion_id',
                'resourceIdKey' => 'id',
                'entourage' => 'Creator'
            );
        }
        if ('RecentEntry' == $alias) {
            return array(
                'entourageModel' => 'Entry',
                'entourageIdKey' => 'discussion_id',
                'resourceIdKey' => 'id',
                'sort' => 'modified DESC',
                'singleOnly' => true,
            );
        }
        return null;
    }

    protected $_resourceName = 'Discussion';

    protected $_defaultListWhere = array('title', 'id');

    protected $_defaultListSort = array('title');

    protected function _getListResourceSqlFragment()
    {
        return ''
            . ' SELECT resource.id AS id, community_id, title, comment'
            . ' FROM discussion AS resource'
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
            $this->_dbTable = new Default_Model_DbTable_Discussion();
        }
        return $this->_dbTable;
    }
}
