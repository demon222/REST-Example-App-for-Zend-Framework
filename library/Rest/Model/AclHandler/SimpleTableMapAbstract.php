<?php
require_once('Rest/Model/AclHandler/StandardAbstract.php');

abstract class Rest_Model_AclHandler_SimpleTableMapAbstract
    extends Rest_Model_AclHandler_StandardAbstract
{
    /**
     * @var Zend_Db_Table_Abstract
     */
    protected $_dbTable;

    /**
     * @param array $id
     * @return array
     * @throws Rest_Model_NotFoundException
     */
    protected function _get(array $id)
    {
        $result = $this->_getDbTable()->find(array('id = ?' => $id['id']));

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
     * @throws Rest_Model_NotFoundException
     */
    public function _put(array $id, array $prop = null)
    {
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

        $this->_putPrePersist($item);

        $updated = $this->_getDbTable()->update($item, array('id = ?' => $id['id']));

        // if it didn't exists, could create the resource at that id... but no
        if ($updated <= 0) {
            throw new Rest_Model_NotFoundException();
        }

        $this->_putPostPersist($item);

        return $item;
    }

    /**
     * @param array $item
     */
    protected function _putPrePersist(array &$item) {}

    /**
     * @param array $item
     */
    protected function _putPostPersist(array $item) {}

    /**
     * @param array $id
     * @throws Rest_Model_NotFoundException
     */
    public function _delete(array $id)
    {
        $this->_deletePrePersist($id);

        $deleted = $this->_getDbTable()->delete(array('id = ?' => $id['id']));

        $this->_deletePostPersist($id);

        if ($deleted == 0) {
            throw new Rest_Model_NotFoundException();
        }
    }

    /**
     * @param array $id
     */
    protected function _deletePrePersist(array &$id) {}

    /**
     * @param array $id
     */
    protected function _deletePostPersist(array $id) {}

    /**
     * @param array $prop
     * @return array
     */
    public function _post(array $prop)
    {
        $keys = array_diff($this->getPropertyKeys(), $this->getIdentityKeys());
        $map = array_combine($keys, $keys);

        $item = Util_Array::mapIntersectingKeys($prop, $map);

        $this->_post_pre_persist($item);

        $id = $this->_getDbTable()->insert($item);

        if ($id === null) {
            return Exception('Unable to post into databse, not sure why');
        }

        $item['id'] = $id;

        $this->_post_post_persist($item);

        return $item;
    }

    /**
     * @param array $item
     */
    protected function _postPrePersist(array &$item) {}

    /**
     * @param array $item
     */
    protected function _postPostPersist(array $item) {}

    /**
     * Get registered Zend_Db_Table instance, lazy load
     *
     * @return Zend_Db_Table_Abstract
     */
    abstract protected function _getDbTable();
}
