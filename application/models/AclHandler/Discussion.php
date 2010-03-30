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
                'singleOnly' => false,
            );
        }
        if ('RecentEntry' == $alias) {
            return array(
                'entourageModel' => 'Entry',
                'entourageIdKey' => 'discussion_id',
                'resourceIdKey' => 'id',
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
            $data = $entourageHandler->getList(array('Discussion' => $params));
            return $data['Discussion'];
        }

        if (isset($params['where'])) {
            // use default properties to search against if none are provided
            if (!is_array($params['where'])) {
                $params['where'] = array('title id' => $params['where']);
            }
        } else {
            $params['where'] = array();
        }

        if (!isset($params['sort']) || !is_array($params['sort'])) {
            $params['sort'] = array('title');
        }

        $whereAndSet = Util_Sql::generateSqlWheresAndParams($params['where'], $this->getPropertyKeys());
        $sortList = Util_Sql::generateSqlSort($params['sort'], $this->getPropertyKeys());

        $sql = ''
            // RESOURCE
            . ' SELECT resource.id AS id, community_id, title, comment'
            . ' FROM discussion AS resource'

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
