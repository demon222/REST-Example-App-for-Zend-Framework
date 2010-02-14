<?php
require_once('Rest/Model/AclHandler/SimpleTableMapAbstract.php');
require_once('Rest/Model/EntourageImplementer/Interface.php');
require_once('Util/Sql.php');

class Default_Model_AclHandler_Email
    extends Rest_Model_AclHandler_SimpleTableMapAbstract
    implements Rest_Model_EntourageImplementer_Interface
{

    /**
     * @return Rest_Model_Handler_Interface
     */
    protected static function _createModelHandler()
    {
        return new Default_Model_Handler_Email();
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

        $acl->allow('default', $this, array('get', 'post'));
        $acl->deny('default', $this, array('put', 'delete'));
    }

    /**
     * Used mainly for testing property requests, where clauses and the like
     * @return array
     */
    public static function getPropertyKeys()
    {
        return Default_Model_Handler_Email::getPropertyKeys();
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
            return $data['Entry'];
        }

        if (isset($params['where'])) {
            // use default properties to search against if none are provided
            if (!is_array($params['where'])) {
                $params['where'] = array('email user_id' => $params['where']);
            }
        } else {
            $params['where'] = array();
        }

        if (!isset($params['sort']) || !is_array($params['sort'])) {
            $params['sort'] = array('modified');
        }

        $whereAndSet = Util_Sql::generateSqlWheresAndParams($params['where'], $this->getPropertyKeys());
        $sortList = Util_Sql::generateSqlSort($params['sort'], $this->getPropertyKeys());

        $sql = ''
            // RESOURCE
            . ' SELECT id, user_id, email'
            . ' FROM email'

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
}
