<?php

require_once('Rest/Model/Handler/Abstract.php');
require_once('Rest/Model/NotFoundException.php');
require_once('Util/Array.php');

class Default_Model_Handler_Role extends Rest_Model_Handler_Abstract
{
    /**
     * @var Zend_Db_Table_Abstract
     */
    protected $_dbTable;

    public function get(array $id)
    {
        $result = $this->_getDbTable()->find(array('id' => $id['id']));

        if (0 == count($result)) {
            throw new Rest_Model_NotFoundException();
        }

        $map = array(
            'id' => 'id',
            'user_id' => 'user_id',
            'resource' => 'resource',
            'role' => 'role',
        );

        return Util_Array::mapIntersectingKeys($result->current()->toArray(), $map);
    }

    public function put(array $id, array $prop = null)
    {
        // if a seperate $prop list is not provided, use the $id list
        if ($prop === null) {
            $prop = $id;
        }

        // could probably implement renaming by having 'id' set by $prop but
        // not going to try to debug that right now
        $map = array(
            'user_id' => 'user_id',
            'resource' => 'resource',
            'role' => 'role',
        );
        $item = Util_Array::mapIntersectingKeys($prop, $map);

        $updated = $this->_getDbTable()->update($item, array('id' => $id['id']));

        if ($updated <= 0) {
            throw new Rest_Model_NotFoundException();
        }

        return $item;
    }

    public function delete(array $id)
    {
        $deleted = $this->_getDbTable()->delete(array('id' => $id['id']));

        if ($deleted == 0) {
            throw new Rest_Model_NotFoundException();
        }
    }

    public function post(array $prop)
    {
        $map = array(
            'user_id' => 'user_id',
            'resource' => 'resource',
            'role' => 'role',
        );
        $item = Util_Array::mapIntersectingKeys($prop, $map);
        $item['created'] = date('Y-m-d H:i:s');

        $id = $this->_getDbTable()->insert($item);

        if ($id === null) {
            return Exception('Unable to post into databse, not sure why');
        }

        $item['id'] = $id;

        return $item;
    }

    public function getList(array $params = null)
    {
        $resultSet = $this->_getDbTable()->fetchAll();

        $map = array(
            'id' => 'id',
            'user_id' => 'user_id',
            'resource' => 'resource',
            'role' => 'role',
        );

        $items = array();
        foreach ($resultSet as $row) {
            $items[] = Util_Array::mapIntersectingKeys($row->toArray(), $map);
        }
        return $items;
    }

    /**
     * Get registered Zend_Db_Table instance, lazy load
     *
     * @return Zend_Db_Table_Abstract
     */
    protected function _getDbTable()
    {
        if (null === $this->_dbTable) {
            $this->_dbTable = new Default_Model_DbTable_Role();
        }
        return $this->_dbTable;
    }
}
