<?php
require_once('Rest/Model/AclHandler/SimpleTableMapAbstract.php');
require_once('Rest/Model/EntourageImplementer/Interface.php');
require_once('Util/Sql.php');

class Default_Model_AclHandler_User
    extends Rest_Model_AclHandler_SimpleTableMapAbstract
    implements Rest_Model_EntourageImplementer_Interface
{

    /**
     * @return Rest_Model_Handler_Interface
     */
    protected static function _createModelHandler()
    {
        return new Default_Model_Handler_User();
    }

    protected function _initAclRules()
    {
        $acl = $this->getAcl();

        if (!$acl->has($this)) {
            $acl->addResource($this);
        }

        if (!$acl->hasRole('default')) {
            $acl->addRole('default');
        }

        $acl->allow('default', $this, array('get'));
        $acl->deny('default', $this, array('put', 'delete', 'post'));
    }

    /**
     * Used mainly for testing property requests, where clauses and the like
     * @return array
     */
    public static function getPropertyKeys()
    {
        return Default_Model_Handler_User::getPropertyKeys();
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
            $data = $entourageHandler->getList(array('User' => $params));
            return $data['User'];
        }

        if (isset($params['where'])) {
            // use default properties to search against if none are provided
            if (!is_array($params['where'])) {
                $params['where'] = array('comment creator_user_id' => $params['where']);
            }
        } else {
            $params['where'] = array();
        }

        if (!isset($params['sort']) || !is_array($params['sort'])) {
            $params['sort'] = array('name');
        }

        $whereAndSet = Util_Sql::generateSqlWheresAndParams($params['where'], $this->getPropertyKeys());
        $sortList = Util_Sql::generateSqlSort($params['sort'], $this->getPropertyKeys());

        $sql = ''
            // RESOURCE
            . ' SELECT id, username, name'
            . ' FROM user'

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
}
