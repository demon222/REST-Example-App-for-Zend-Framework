<?php
require_once('Rest/Model/AclHandler/Abstract.php');
require_once('Util/Array.php');

class Default_Model_AclHandler_Entry
    extends Rest_Model_AclHandler_Abstract
{
    /**
     * @var Default_Model_Handler_Entry
     */
    protected $_modelHandler;

    /**
     * @var PDO
     */
    protected $_dbHandler;

    protected function _initAclRules()
    {
        $acl = $this->getAcl();

        $acl->addResource($this);

        $acl->addRole('default');

        $acl->allow('default', $this, array('get', 'post'));
        $acl->deny('default', $this, array('put', 'delete'));
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

            $sql = 'SELECT e.id, e.comment, e.created'
                . ' FROM entry AS e'
                . ' LEFT OUTER JOIN permission AS p ON p.resource = ("Entry=" || e.id)'
                . ' LEFT OUTER JOIN role AS r ON p.role = r.role AND p.resource = r.resource'
                . ' LEFT OUTER JOIN user AS u ON r.user_id = u.id'
                . ' WHERE p.id IS NULL OR ('
                . '     ('
                . '         u.username = :username'
                . '         OR p.role = "default"'
                . '     )'
                . '     AND p.privilege = "get"'
                . '     AND p.permission != "deny"'
                . ' )'
                . '';
        } else {
            // get based on whitelist
            // accept specific resource that are allow only

            $sql = 'SELECT e.id, e.comment, e.created'
                . ' FROM entry AS e'
                . ' LEFT OUTER JOIN permission AS p ON p.resource = ("Entry=" || e.id)'
                . ' LEFT OUTER JOIN role AS r ON p.role = r.role AND p.resource = r.resource'
                . ' LEFT OUTER JOIN user AS u ON r.user_id = u.id'
                . ' WHERE ('
                . '     u.username = :username'
                . '     OR p.role = "default"'
                . ' )'
                . ' AND p.privilege = "get"'
                . ' AND p.permission = "allow"'
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
    public function get(array $id)
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

        return $this->_getModelHandler()->delete($id);
    }

    /**
     * @param array $prop
     * @return array
     * @throws Zend_Acl_Exception
     */
    public function post(array $prop)
    {
        if ($this->getAcl() && !$this->isAllowed('post', $id)) {
            throw new Zend_Acl_Exception('post for ' . $this->getResourceId() . ' is not allowed');
        }

        return $this->_getModelHandler()->post($prop);
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
     * @return Default_Model_Handler_Interface
     */
    protected function _getModelHandler()
    {
        if ($this->_modelHandler === null) {
            $this->_modelHandler = new Default_Model_Handler_Entry();
        }
        return $this->_modelHandler;
    }

}
