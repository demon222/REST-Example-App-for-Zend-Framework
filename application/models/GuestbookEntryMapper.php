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
     * Save a guestbook entry
     * 
     * @param  Default_Model_GuestbookEntry $entry 
     * @return void
     */
    public function save(Default_Model_GuestbookEntry $entry)
    {
        $entry->setCreated(date('Y-m-d H:i:s'));
        
        $data = array(
            'email'   => $entry->getEmail(),
            'comment' => $entry->getComment(),
            'created' => $entry->getCreated(),
        );
        
        if (null === ($id = $entry->getId())) {
            unset($data['id']);
            $id = $this->getDbTable()->insert($data);
            $entry->setId($id);
        } else {
            $this->getDbTable()->update($data, array('id = ?' => $id));
        }
    }

    /**
     * Delete a guestbook entry
     *
     * @param Default_Model_GuestbookEntry $entry
     * @return void
     */
    public function delete(Default_Model_GuestbookEntry $entry)
    {
        if (null === ($id = $entry->getId())) {
            throw new Exception('Entry does not have an id');
        }

        $table = $this->getDbTable();

        $where = $table->getAdapter()->quoteInto('id = ?', $id);

        $table->delete($where);
    }

    /**
     * Find a guestbook entry by id
     * 
     * @param  int $id 
     * @param  Default_Model_GuestbookEntry $entry 
     * @return void
     */
    public function find($id, Default_Model_GuestbookEntry $entry)
    {
        $result = $this->getDbTable()->find($id);
        if (0 == count($result)) {
            return;
        }
        $row = $result->current();
        $entry->setId($row->id)
            ->setEmail($row->email)
            ->setComment($row->comment)
            ->setCreated($row->created);
    }

    /**
     * Fetch all guestbook entries
     * 
     * @return array
     */
    public function fetchAll()
    {
        $resultSet = $this->getDbTable()->fetchAll();
        $entries   = array();
        foreach ($resultSet as $row) {
            $entry = new Default_Model_GuestbookEntry();
            $entry->setId($row->id)
                ->setEmail($row->email)
                ->setComment($row->comment)
                ->setCreated($row->created)
                ->setMapper($this);
            $entries[] = $entry;
        }
        return $entries;
    }
}
