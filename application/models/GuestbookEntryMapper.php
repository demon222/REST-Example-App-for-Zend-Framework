<?php

/**
 * GuestbookEntry data mapper
 *
 * Implements the Data Mapper design pattern:
 * http://www.martinfowler.com/eaaCatalog/dataMapper.html
 * 
 * @uses       Default_Model_DbTable_GuestbookEntry
 * @package    QuickStart
 * @subpackage Model
 */
class Default_Model_GuestbookEntryMapper
{
    /**
     * @var Zend_Db_Table_Abstract
     */
    protected $_dbTable;

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

    /**
     * Put/Update a guestbook entry. Can change the id of a object
     * by passing 2nd param of object which will be used for id information
     * 
     * @param  Default_Model_GuestbookEntry $entry
     * @param  Default_Model_GuestbookEntry $orig
     * @return boolean resource found
     */
    public function put(Default_Model_GuestbookEntry $entry, Default_Model_GuestbookEntry $orig = null)
    {
        $entry->setCreated(date('Y-m-d H:i:s'));

        $data = array(
            'id'      => $entry->getId(),
            'email'   => $entry->getEmail(),
            'comment' => $entry->getComment(),
            'created' => $entry->getCreated(),
        );

        if ($orig instanceof Default_Model_GuestbookEntry) {
            $idPairs = array('id = ?' => $orig->id);
        } else {
            $idPairs = array('id = ?' => $entry->id);
        }

        $updated = $this->getDbTable()->update($data, $idPairs);

        return $updated > 0;
    }

    /**
     * Post/Create a guestbook entry
     * 
     * @param Default_Model_GuestbookEntry $entry
     */
    public function post(Default_Model_GuestbookEntry $entry)
    {
        $entry->setCreated(date('Y-m-d H:i:s'));

        $data = array(
            'email'   => $entry->getEmail(),
            'comment' => $entry->getComment(),
            'created' => $entry->getCreated(),
        );

        $id = $this->getDbTable()->insert($data);
        $entry->setId($id);
    }

    /**
     * Delete a guestbook entry
     *
     * @param Default_Model_GuestbookEntry $entry
     * @return boolean resource found
     */
    public function delete(Default_Model_GuestbookEntry $entry)
    {
        if (null === ($id = $entry->getId())) {
            throw new Exception('Entry does not have an id');
        }

        $table = $this->getDbTable();

        $where = $table->getAdapter()->quoteInto('id = ?', $id);

        $deleted = $table->delete($where);

        return $deleted > 0;
    }

    /**
     * Find a guestbook entry by id
     * 
     * @param  Default_Model_GuestbookEntry $entry
     * @return boolean resource found
     */
    public function get(Default_Model_GuestbookEntry $entry)
    {
        $result = $this->getDbTable()->find($entry->getId());
        if (0 == count($result)) {
            return false;
        }
        $row = $result->current();
        $entry->setEmail($row->email)
            ->setComment($row->comment)
            ->setCreated($row->created);

        return true;
    }

    /**
     * Fetch all guestbook entries
     * 
     * @return array
     */
    public function fetchAll($identity)
    {
        $resultSet = $this->getDbTable()->fetchAll();
        $entries   = array();
        foreach ($resultSet as $row) {
            $entry = new Default_Model_GuestbookEntry();
            $entry->setId($row->id)
                ->setEmail($row->email)
                ->setComment($row->comment)
                ->setCreated($row->created);
            $entry->setMapper($this);
            $entries[] = $entry;
        }
        return $entries;
    }
}
