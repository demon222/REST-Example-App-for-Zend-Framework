<?php
require_once('Rest/Model/Handler/SimpleTableMapAbstract.php');
require_once('Util/Array.php');

class Default_Model_Handler_Email extends Rest_Model_Handler_SimpleTableMapAbstract
{
    /**
     * Used mainly for testing property requests, where clauses and the like
     * @return array
     */
    public static function getPropertyKeys()
    {
        return array('id', 'user_id', 'email', 'primary');
    }

    /**
     * @param array $item
     */
    protected function _put_pre_persist(array &$item)
    {
        // disable transfering the email to a different user
        if (isset($item['user_id'])) {
            unset($item['user_id']);
        }
    }

    /**
     * Get registered Zend_Db_Table instance, lazy load
     *
     * @return Zend_Db_Table_Abstract
     */
    protected function _getDbTable()
    {
        if (null === $this->_dbTable) {
            $this->_dbTable = new Default_Model_DbTable_Email();
        }
        return $this->_dbTable;
    }
}
