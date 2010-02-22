<?php
require_once('Rest/Model/AclHandler/Abstract.php');
require_once('Rest/Model/EntourageImplementer/Interface.php');
require_once('Util/Sql.php');

class Default_Model_AclHandler_Entry_Owner
    extends Rest_Model_AclHandler_Abstract
    implements Rest_Model_EntourageImplementer_Interface
{

    /**
     * @var PDO
     */
    protected $_dbHandler;

    protected $_roles = array(
        'owner',
    );

    protected $_staticPermissions = array(
        'default' => array(
            'deny' => array('get', 'put', 'delete', 'post'),
        ),
        'owner' => array(
            'allow' => array('get', 'put', 'delete', 'post'),
        ),
    );

    public function getResourceId()
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

    /**
     * @param array $params
     * @return array
     */
    public function getList(array $params = null)
    {
        $params = is_array($params) ? $params : array();

        if (isset($params['entourage'])) {
            $entourageHandler = new Default_Model_AclHandler_Entourage($this->getAcl(), $this->getAclContextUser());
            $data = $entourageHandler->getList(array('Entry' => $params));
            return $data['Entry'];
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
            $params['sort'] = array('entry_id');
        }

        $whereAndSet = Util_Sql::generateSqlWheresAndParams($params['where'], $this->getPropertyKeys());
        $sortList = Util_Sql::generateSqlSort($params['sort'], $this->getPropertyKeys());

        if (!$this->getAcl()->isAllowed('owner', 'Entry', 'get')) {
            return array();
        }

        $sql = ''
            . ' SELECT resource.id, resource_id AS entry_id, user_id'
            . ' FROM resource_role AS resource'
            . ' INNER JOIN entry AS e ON resource.resource_id = e.id'
            . ' WHERE resource = :generalResource'
            . ' AND role = :role'
            . ' AND ' . implode(' AND ', array_merge($whereAndSet['sql'], array('1 = 1')))
            . ' ORDER BY ' . implode(', ', $sortList)
            . '';

        $query = $this->_getDbHandler()->prepare($sql);
        $query->execute(array_merge(array(':generalResource' => 'Entry', ':role' => 'owner'), $whereAndSet['param']));
        $rowSet = $query->fetchAll(PDO::FETCH_ASSOC);

        return $rowSet;
    }

    /**
     * @param array $id
     * @return array
     * @throws Rest_Model_NotFoundException, Zend_Acl_Exception
     */
    public function get(array $id, array $params = null)
    {
        if (isset($params['entourage'])) {
            $entourageHandler = new Default_Model_AclHandler_Entourage($this->getAcl(), $this->getAclContextUser());
            return $entourageHandler->get($id, array($this, $params['entourage']));
        }

        if ($this->getAcl() && !$this->isAllowed('get', $id)) {
            throw new Zend_Acl_Exception('get for ' . $this->getResourceId() . ' is not allowed');
        }

        return $this->_get($id);
    }

    public function _get(array $id, array $params = null)
    {
        $dbTable = new Default_Model_DbTable_ResourceRole();

        $result = $dbTable->find(array('id = ?' => $id['id'], 'resource = ?' => 'Entry', 'role = ?' => 'owner'));

        if (0 == count($result)) {
            throw new Rest_Model_NotFoundException();
        }

        // 1 to 1, same names
        $keys = $this->getPropertyKeys();
        $map = array_combine($keys, $keys);

        return Util_Array::mapIntersectingKeys($result->current()->toArray(), $map);
    }

    /**
     * @param array $id
     * @param array $prop
     * @return array
     * @throws Rest_Model_NotFoundException, Zend_Acl_Exception
     */
    public function put(array $id, array $prop = null)
    {
        if ($this->getAcl() && !$this->isAllowed('put', $id)) {
            throw new Zend_Acl_Exception('put for ' . $this->getResourceId() . ' is not allowed');
        }

        return $this->_put($id, $prop);
    }

    public function _put(array $id, array $prop = null)
    {
        $dbTable = new Default_Model_DbTable_ResourceRole();

        // if a seperate $prop list is not provided, use the $id list
        if ($prop === null) {
            $prop = $id;
        }

        // could probably implement renaming by having 'id' set by $prop but
        // not going to try to debug that right now
        // 1 to 1, same names
        $keys = array_diff($this->getPropertyKeys(), $this->getIdentityKeys());
        $map = array_combine($keys, $keys);

        $item = Util_Array::mapIntersectingKeys($prop, $map);

        $updated = $dbTable->update($item, array('id = ?' => $id['id'], 'resource = ?' => 'Entry', 'role = ?' => 'owner'));

        if ($updated <= 0) {
            throw new Rest_Model_NotFoundException();
        }

        return $item;
    }

    /**
     * @param array $id
     * @throws Rest_Model_NotFoundException, Zend_Acl_Exception
     */
    public function delete(array $id)
    {
        if ($this->getAcl() && !$this->isAllowed('delete', $id)) {
            throw new Zend_Acl_Exception('delete for ' . $this->getResourceId() . ' is not allowed');
        }

        $this->_delete($id);
    }

    public function _delete(array $id)
    {
        $dbTable = new Default_Model_DbTable_ResourceRole();

        $deleted = $dbTable->delete(array('id = ?' => $id['id'], 'resource = ?' => 'Entry', 'role = ?' => 'owner'));

        if ($deleted == 0) {
            throw new Rest_Model_NotFoundException();
        }
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

        return $this->_post($prop);
    }

    public function _post(array $prop)
    {
        $dbTable = new Default_Model_DbTable_ResourceRole();

        $keys = array_diff($this->getPropertyKeys(), $this->getIdentityKeys());
        $map = array_combine($keys, $keys);

        $item = Util_Array::mapIntersectingKeys($prop, $map);

        $item['resource'] = 'Entry';
        $item['role'] = 'owner';

        $id = $dbTable->insert($item);

        if ($id === null) {
            return Exception('Unable to post into databse, not sure why');
        }

        $item['id'] = $id;

        return $item;
    }

    /**
     * @return PDO
     */
    protected function _getDbHandler()
    {
        if (null === $this->_dbHandler) {
            $config = Zend_Registry::getInstance()->get('config');
            $this->_dbHandler = new PDO('sqlite:' . $config['resources']['db']['params']['dbname']);
        }
        return $this->_dbHandler;
    }
}
