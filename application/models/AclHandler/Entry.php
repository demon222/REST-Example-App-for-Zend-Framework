<?php
require_once('Rest/Model/AclHandler/Abstract.php');

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


function dump($role, $resource)
{
    $acl = $this->getAcl();
    $resource = $resource instanceof Zend_Acl_Resource_Interface ? $resource->getResourceId() : (string) $resource;

    return $role . ", " . $resource . ": "
        . ($acl->isAllowed($role, $resource, 'get') ? 'true' : 'false') . ", "
        . ($acl->isAllowed($role, $resource, 'put') ? 'true' : 'false') . ", "
        . ($acl->isAllowed($role, $resource, 'delete') ? 'true' : 'false') . ", "
        . ($acl->isAllowed($role, $resource, 'post') ? 'true' : 'false') . "\n";
}

    /**
     * @param array $id
     * @return array
     * @throws Rest_Model_NotFoundException, Zend_Acl_Exception
     */
    public function get(array $id)
    {
        $acl = $this->getAcl();
        $rId = $this->getResourceSpecificId($id);
        if ($acl) {
            $acl->addResource($rId, $this);
        }

if ($id['id'] == 123) {
    $acl->addRole('owner', 'default');
    $acl->allow('owner', 'Entry=123', array('get', 'put', 'delete'));
    $acl->deny('default', 'Entry=123', array('get', 'post'));
}

$acl->addRole('admin');
$acl->allow('admin', $this, array('get', 'put', 'delete', 'post'));

$acl->addRole('selected', 'default');
$acl->allow('selected', $this->getResourceSpecificId($id), array('get'));

echo $this->dump('default', $this);
echo $this->dump('default', $this->getResourceSpecificId($id));
if ($id['id'] == 123) {
    echo $this->dump('owner', $this);
    echo $this->dump('owner', $this->getResourceSpecificId($id));
}
echo $this->dump('admin', $this);
echo $this->dump('admin', $this->getResourceSpecificId($id));
echo $this->dump('selected', $this);
echo $this->dump('selected', $this->getResourceSpecificId($id));
exit();

        $roleHandler = new Default_Model_Handler_Role();
        $roleHandler->getList(array('user_id' => 789, 'resource' => ''));
// NEED TO DETERMINE ROLES FOR THE SPECIFIC AND THE GENERAL


        if ($this->getAcl() && !$this->isAllowed('get')) {
            throw Zend_Acl_Exception('get for ' . $this->getResourceId() . ' is not allowed');
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
        return $this->_getModelHandler()->put($id, $prop);
    }

    /**
     * @param array $id
     * @throws Rest_Model_NotFoundException, Zend_Acl_Exception
     */
    public function delete(array $id)
    {
        return $this->_getModelHandler()->delete($id);
    }

    /**
     * @param array $prop
     * @return array
     * @throws Zend_Acl_Exception
     */
    public function post(array $prop)
    {
        return $this->_getModelHandler()->post($prop);
    }


    /**
     * @param array $params
     * @return array
     */
    public function getList(array $params = null)
    {
        return $this->_getModelHandler()->getList($params);
    }

/*
        $sql = 'SELECT * FROM entry WHERE id = ' . (int) $id['id'];
        $row = $this->_getDbHandler()->query($sql)->fetch(PDO::FETCH_ASSOC);

        if (false === $row) {
            throw new Rest_Model_NotFoundException();
        }

        return array(
            'id' => $row['id'],
            'comment' => $row['comment'],
            'created' => $row['created'],
        );
 */

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
