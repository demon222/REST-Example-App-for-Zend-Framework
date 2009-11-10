<?php

/**
 * Guestbook table data gateway
 *
 * @uses       Zend_Db_Table_Abstract
 * @package    QuickStart
 * @subpackage Model
 */
class Default_Model_DbTable_GuestbookEntry extends Zend_Db_Table_Abstract
{
    /**
     * @var string Name of the database table
     */
    protected $_name = 'guestbook_entry';
}
