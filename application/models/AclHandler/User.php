<?php
require_once('Rest/Model/AclHandler/Abstract.php');
require_once('Util/Array.php');

class Default_Model_AclHandler_User
    extends Rest_Model_AclHandler_Abstract
{
    /**
     * @var Default_Model_Handler_User
     */
    protected $_modelHandler;

    /**
     * @var PDO
     */
    protected $_dbHandler;

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
     * @param array $params
     * @return array
     */
    public function getList(array $params = null)
    {
        $username = $this->getAclContextUser();

        if ($this->isAllowed('get')) {
            // get excluding the blacklist
            // accept specific resources that are allow or unspecified.
            // IE: not denied

            $sql = 'SELECT u.id AS id, u.username AS username, u.name AS name'
                . ' FROM user AS u'
                . ' LEFT OUTER JOIN permission AS acl_p ON acl_p.resource = ("User=" || u.id)'
                . ' LEFT OUTER JOIN resource_role AS acl_rr ON acl_p.role = acl_rr.role AND acl_p.resource = acl_rr.resource'
                . ' LEFT OUTER JOIN user AS acl_u ON acl_rr.user_id = acl_u.id'
                . ' WHERE acl_p.id IS NULL OR ('
                . '     ('
                . '         acl_u.username = :username'
                . '         OR acl_p.role = "default"'
                . '     )'
                . '     AND acl_p.privilege = "get"'
                . '     AND acl_p.permission != "deny"'
                . ' )'
                . ' GROUP BY u.id'
                . '';
        } else {
            // get based on whitelist
            // accept specific resource that are allow only

            $sql = 'SELECT u.id AS id, u.username AS username, u.name AS name'
                . ' FROM user AS u'
                . ' LEFT OUTER JOIN permission AS acl_p ON acl_p.resource = ("User=" || u.id)'
                . ' LEFT OUTER JOIN resource_role AS acl_rr ON acl_p.role = acl_rr.role AND acl_p.resource = acl_rr.resource'
                . ' LEFT OUTER JOIN user AS acl_u ON acl_rr.user_id = acl_u.id'
                . ' WHERE ('
                . '     acl_u.username = :username'
                . '     OR acl_p.role = "default"'
                . ' )'
                . ' AND acl_p.privilege = "get"'
                . ' AND acl_p.permission = "allow"'
                . ' GROUP BY u.id'
                . '';
        }

        $query = $this->_getDbHandler()->prepare($sql);
        $query->execute(array(
            ':username' => $username,
        ));
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
        if ($this->getAcl() && !$this->isAllowed('get', $id)) {
            throw new Zend_Acl_Exception('get for ' . $this->getResourceId() . ' is not allowed');
        }

        return $this->_getModelHandler()->get($id);
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

        return $this->_getModelHandler()->put($id, $prop);
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

        $this->_getModelHandler()->delete($id);
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
//        Default_Model_Handler_User

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

    /**
     * @return Default_Model_Handler_User
     */
    protected function _getModelHandler()
    {
        if ($this->_modelHandler === null) {
            $this->_modelHandler = new Default_Model_Handler_User();
        }
        return $this->_modelHandler;
    }

}
