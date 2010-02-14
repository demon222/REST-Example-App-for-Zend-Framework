<?php
require_once('Rest/Model/Handler/SimpleTableMapAbstract.php');
require_once('Rest/Model/BadRequestException.php');
require_once('Util/Array.php');

class Default_Model_Handler_Entry extends Rest_Model_Handler_SimpleTableMapAbstract
{
    /**
     * Used mainly for testing property requests, where clauses and the like
     * @return array
     */
    public static function getPropertyKeys()
    {
        return array('id', 'comment', 'creator_user_id', 'modified');
    }

    /**
     * @param array $item
     */
    protected function _put_pre_persist(array &$item)
    {
        $item['modified'] = date('Y-m-d H:i:s');

        // not allowing creator to be changed
        if (isset($item['creator_user_id'])) {
            unset($item['creator_user_id']);
        }
    }

    /**
     * @param array $item
     */
    protected function _post_pre_persist(array &$item)
    {
        $item['modified'] = date('Y-m-d H:i:s');

        $userTable = new Default_Model_DbTable_User();
        $resultSet = $userTable->find($item['creator_user_id']);
        if (false === current($resultSet)) {
            throw new Rest_Model_BadRequestException('creator_user_id does not match with an existing user');
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
            $this->_dbTable = new Default_Model_DbTable_Entry();
        }
        return $this->_dbTable;
    }
}
