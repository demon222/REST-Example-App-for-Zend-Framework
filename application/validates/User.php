<?php
require_once('Rest/Validate/Abstract.php');

class Default_Validate_User extends Rest_Validate_Abstract
{
    const REQUIRE_NAME = 'requireName';
    const REQUIRE_USERNAME = 'requireUsername';
    const USERNAME_ALREADY_EXISTS = 'userNameAlreadyExists';

    /**
     * Validation failure message template definitions
     *
     * @var array
     */
    protected $_local_messageTemplates = array(
        self::REQUIRE_NAME => '"name" is a required property',
        self::REQUIRE_USERNAME => '"username" is a required property',
        self::USERNAME_ALREADY_EXISTS => '"%value%" username already exists',
    );

    public function __construct() {
        $this->_messageTemplates = array_merge(
            $this->_local_messageTemplates, $this->_messageTemplates
        );
    }

    /**
     * @param array $value
     * @return boolean indicating is any valid data of interest was passed
     */
    protected function _isValid($value)
    {
        $partialHappened = false;

        $isPost = 'post' == $this->getMethodContext();

        // validate that the name is set
        if (isset($value['name'])) {
            $partialHappened = true;

            $validate = new Zend_Validate_StringLength(array(1, 50));
            if (!$validate->isValid($value['name'])) {
                $this->_addValidateMessagesAndErrors($validate);
            }
        } elseif ($isPost) {
            $this->_error(self::REQUIRE_NAME);
        }

        // validate that the username is set, and doesn't already exist
        if (isset($value['username'])) {
            $partialHappened = true;

            $validate = new Zend_Validate_StringLength(array(1, 50));
            if (!$validate->isValid($value['username'])) {
                $this->_addValidateMessagesAndErrors($validate);
            }

            $userTable = new Default_Model_DbTable_User();
            if (false !== current($userTable->fetchRow(array('username = ?' => $value['username'])))) {
                $this->_error(self::USERNAME_ALREADY_EXISTS, $value['username']);
            }
        } elseif ($isPost) {
            $this->_error(self::REQUIRE_USERNAME);
        }

        return $partialHappened;
    }
}
