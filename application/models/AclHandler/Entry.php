<?php
require_once('Rest/Model/AclHandler/Abstract.php');
require_once('Rest/Model/EntourageImplementer/Interface.php');
require_once('Util/Array.php');

class Default_Model_AclHandler_Entry
    extends Rest_Model_AclHandler_Abstract
    implements Rest_Model_EntourageImplementer_Interface
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
     * @param string $alias
     * @return array
     */
    public function expandEntourageAlias($alias)
    {
        if ('Creator' == $alias) {
            return array(
                'entourageModel' => 'User',
                'entourageIdKey' => 'id',
                'resourceIdKey' => 'creator_user_id',
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
        if (isset($params) && isset($params['entourage'])) {
            $entourageHandler = new Default_Model_AclHandler_Entourage($this->getAcl(), $this->getAclContextUser());
            $data = $entourageHandler->getList(array('Entry' => $params));
            return $data['Entry'];
        }

        $sql = ''
            // RESOURCE
            . ' SELECT id, comment, creator_user_id, modified'
            . ' FROM entry'
            
            // ACL
            . $this->_getGenericAclListJoins()

            // RESOURCE
            . 'WHERE 1 = 1'

            // ACL
            . $this->_getGenericAclListWheres()
            . '';

        $query = $this->_getDbHandler()->prepare($sql);
        $query->execute(array(
            ':username' => $this->getAclContextUser(),
            ':generalResource' => $this->getResourceId(),
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

// getAclContextUser is getting the username and not the user_id
// must be changed
        $prop['creator_user_id'] = $this->getAclContextUser();

        $item = $this->_getModelHandler()->post($prop);

        // NEED TO CREATE PERMISSION AND ROLE FOR THE NEW ENTRY
//        Default_Model_Handler_Entry

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
