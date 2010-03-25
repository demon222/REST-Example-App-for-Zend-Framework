<?php
require_once('Rest/Model/AclHandler/StandardAbstract.php');

abstract class Rest_Model_AclHandler_SimpleTableMapAbstract
    extends Rest_Model_AclHandler_StandardAbstract
{
    /**
     * @var Default_Model_Handler_Entry
     */
    protected $_modelHandler;

    /**
     * @return Rest_Model_Handler_Interface
     */
    abstract protected static function _createModelHandler();

    /**
     * @param array $id
     * @return array
     * @throws Rest_Model_NotFoundException
     */
    protected function _get(array $id)
    {
        return $this->_getModelHandler()->get($id);
    }

    /**
     * @param array $id
     * @param array $prop
     * @return array
     * @throws Rest_Model_NotFoundException
     */
    protected function _put(array $id, array $prop = null)
    {
        return $this->_getModelHandler()->put($id, $prop);
    }

    /**
     * @param array $id
     * @throws Rest_Model_NotFoundException
     */
    protected function _delete(array $id)
    {
        $this->_getModelHandler()->delete($id);
    }

    /**
     * @param array $prop
     * @return array
     */
    protected function _post(array $prop)
    {
        return $this->_getModelHandler()->post($prop);
    }

    /**
     * @return Default_Model_Handler_Entry
     */
    protected function _getModelHandler()
    {
        if ($this->_modelHandler === null) {
            $this->_modelHandler = $this->_createModelHandler();
        }
        return $this->_modelHandler;
    }

}
