<?php

require_once('Rest/Model/Handler/Interface.php');
require_once('Rest/Model/NotFoundException.php');

class Default_Model_Handler_Entry implements Rest_Model_Handler_Interface
{
    /**
     * @var Zend_Db_Table_Abstract
     */
    protected $_dbTable;

    /**
     * Used mainly to ensure that the required keys have been passed to
     * controllers that inturn implement model handlers
     *
     * @return array
     */
    public static function getIdentityKeys()
    {
        return array('id');
    }

    public function get(array $id)
    {
        $result = $this->getDbTable()->find(array('id' => $id['id']));

        if (0 == count($result)) {
            throw new Rest_Model_NotFoundException();
        }

        $row = $result->current();

        return array(
            'id' => $row->id,
            'email'   => $row->email,
            'comment' => $row->comment,
            'created' => $row->created,
        );
    }

    public function put(array $id, array $prop = null)
    {
        // if a seperate $prop list is not provided, use the $id list
        if ($prop === null) {
            $prop = $id;
        }

        // could probably implement renaming by having 'id' set by $prop not $id
        // but not going to try to debug that right now
        $item = array(
            'id'      => $id['id'],
            'email'   => $prop['email'],
            'comment' => $prop['comment'],
            'created' => date('Y-m-d H:i:s'),
        );

        $updated = $this->getDbTable()->update($item, array('id' => $id['id']));

        if ($updated <= 0) {
            throw new Rest_Model_NotFoundException();
        }

        return $item;
    }

    public function delete(array $id)
    {
        $deleted = $this->getDbTable()->delete(array('id' => $id['id']));

        if ($deleted == 0) {
            throw new Rest_Model_NotFoundException();
        }
    }

    public function post(array $prop)
    {
        $item = array(
            'email'   => $prop['email'],
            'comment' => $prop['comment'],
            'created' => date('Y-m-d H:i:s'),
        );

        $id = $this->getDbTable()->insert($item);

        if ($id === null) {
            return Exception('Unable to post into databse, not sure why');
        }

        $item['id'] = $id;

        return $item;
    }

    public function getList(array $params = null)
    {
        $resultSet = $this->getDbTable()->fetchAll();
        $items = array();
        foreach ($resultSet as $row) {
            $item = array(
                'id' => $row->id,
                'email'   => $row->email,
                'comment' => $row->comment,
                'created' => $row->created,
            );
            $items[] = $item;
        }
        return $items;
    }

    /**
     * Specify Zend_Db_Table instance to use for data operations
     *
     * @param  Zend_Db_Table_Abstract $dbTable
     * @return Default_Model_GuestbookEntryMapper
     */
    public function setDbTable($dbTable)
    {
        if (is_string($dbTable)) {
            $dbTable = new $dbTable();
        }
        if (!$dbTable instanceof Zend_Db_Table_Abstract) {
            throw new Exception('Invalid table data gateway provided');
        }
        $this->_dbTable = $dbTable;
        return $this;
    }

    /**
     * Get registered Zend_Db_Table instance
     *
     * Lazy loads Default_Model_DbTable_GuestbookEntry if no instance registered
     *
     * @return Zend_Db_Table_Abstract
     */
    public function getDbTable()
    {
        if (null === $this->_dbTable) {
            $this->setDbTable('Default_Model_DbTable_GuestbookEntry');
        }
        return $this->_dbTable;
    }
}
