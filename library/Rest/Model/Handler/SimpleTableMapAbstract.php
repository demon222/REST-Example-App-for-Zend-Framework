<?php

require_once('Rest/Model/Handler/Abstract.php');
require_once('Rest/Model/NotFoundException.php');
require_once('Util/Array.php');

abstract class Rest_Model_Handler_SimpleTableMapAbstract extends Rest_Model_Handler_Abstract
{
    /**
     * @var Zend_Db_Table_Abstract
     */
    protected $_dbTable;

    /**
     * @param array $params
     * @return array
     */
    public function getList(array $params = null)
    {
        $resultSet = $this->_getDbTable()->fetchAll();

        // 1 to 1, same names
        $keys = $this->getIdentityKeys();
        $map = array_combine($keys, $keys);

        $items = array();
        foreach ($resultSet as $row) {
            $items[] = Util_Array::mapIntersectingKeys($row->toArray(), $map);
        }
        return $items;
    }

    /**
     * @param array $id
     * @return array
     * @throws Rest_Model_NotFoundException
     */
    public function get(array $id, array $params = null)
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
    public function put(array $id, array $prop = null)
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

        $this->_put_pre_persist($item);

        $updated = $this->_getDbTable()->update($item, array('id = ?' => $id['id']));

        // if it didn't exists, could create the resource at that id... but no
        if ($updated <= 0) {
            throw new Rest_Model_NotFoundException();
        }

        $this->_put_post_persist($item);

        return $item;
    }

    /**
     * @param array $item
     */
    protected function _put_pre_persist(array &$item)
    {
    }

    /**
     * @param array $item
     */
    protected function _put_post_persist(array $item)
    {
    }

    /**
     * @param array $id
     * @throws Rest_Model_NotFoundException
     */
    public function delete(array $id)
    {
        $deleted = $this->_getDbTable()->delete(array('id = ?' => $id['id']));

        if ($deleted == 0) {
            throw new Rest_Model_NotFoundException();
        }
    }

    /**
     * @param array $prop
     * @return array
     */
    public function post(array $prop)
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
    protected function _post_pre_persist(array &$item)
    {
    }

    /**
     * @param array $item
     */
    protected function _post_post_persist(array $item)
    {
    }
}
