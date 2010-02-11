<?php

require_once('Rest/Model/Handler/Abstract.php');
require_once('Rest/Model/NotFoundException.php');
require_once('Util/Array.php');

class Default_Model_Handler_User extends Rest_Model_Handler_Abstract
{
    /**
     * @var Zend_Db_Table_Abstract
     */
    protected $_dbTable;

    /**
     * Used mainly for testing property requests, where clauses and the like
     * @return array
     */
    public static function getPropertyKeys()
    {
        return array('id', 'username', 'name');
    }

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
        $keys = $this->getIdentityKeys();
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
        $item['modified'] = date('Y-m-d H:i:s');

        $updated = $this->_getDbTable()->update($item, array('id = ?' => $id['id']));

        if ($updated <= 0) {
            throw new Rest_Model_NotFoundException();
        }

        return $item;
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
        $item['modified'] = date('Y-m-d H:i:s');

        $id = $this->_getDbTable()->insert($item);

        if ($id === null) {
            return Exception('Unable to post into databse, not sure why');
        }

        $item['id'] = $id;

        return $item;
    }

    /**
     * Get registered Zend_Db_Table instance, lazy load
     *
     * @return Zend_Db_Table_Abstract
     */
    protected function _getDbTable()
    {
        if (null === $this->_dbTable) {
            $this->_dbTable = new Default_Model_DbTable_User();
        }
        return $this->_dbTable;
    }
}
