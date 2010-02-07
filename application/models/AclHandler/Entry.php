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

        if (!$acl->hasRole('default')) {
            $acl->addRole('default');
        }

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
                . ' GROUP BY e.id'
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
                . ' GROUP BY e.id'
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

        return $data = $this->_getModelHandler()->delete($id);
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

// NEED TO CREATE PERMISSION AND ROLE FOR THE NEW ENTRY

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
     * @return Default_Model_Handler_Entry
     */
    protected function _getModelHandler()
    {
        if ($this->_modelHandler === null) {
            $this->_modelHandler = new Default_Model_Handler_Entry();
        }
        return $this->_modelHandler;
    }

}
