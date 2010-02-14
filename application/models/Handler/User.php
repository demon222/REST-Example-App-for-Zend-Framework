<?php
require_once('Rest/Model/Handler/SimpleTableMapAbstract.php');
require_once('Util/Array.php');

class Default_Model_Handler_User extends Rest_Model_Handler_SimpleTableMapAbstract
{
    /**
     * Used mainly for testing property requests, where clauses and the like
     * @return array
     */
    public static function getPropertyKeys()
    {
        return array('id', 'username', 'name');
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
