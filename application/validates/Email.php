<?php
require_once('Rest/Validate/Abstract.php');

class Default_Validate_Email extends Rest_Validate_Abstract
{
    const REQUIRE_EMAIL = 'requireEmail';
    const USERNAME_ALREADY_EXISTS = 'userNameAlreadyExists';
    const NOT_VALID_USER_ID = 'notValidUserId';
    const REQUIRE_USER_ID = 'requireUserId';

    /**
     * Validation failure message template definitions
     *
     * @var array
     */
    protected $_local_messageTemplates = array(
        self::REQUIRE_EMAIL => '"email" is a required property',
        self::EMAIL_ALREADY_EXISTS => '"%value%" email already exists',
        self::NOT_VALID_USER_ID => '"%value%" is not a valid "user_id" property',
        self::REQUIRE_USER_ID => '"user_id" is a required property',
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

        $isPost = $this->getMethodContext();

        // validate that the email is correct
        if (isset($value['email'])) {
            $partialHappened = true;

            $filter = new Zend_Filter_StringTrim();
            $value['email'] = $filter->filter($value['email']);

            $validate = new Zend_Validate_EmailAddress();
            if (!$validate->isValid($value['email'])) {
                $this->_addValidateMessagesAndErrors($validate);
            }

            $emailTable = new Default_Model_DbTable_Email();
            if (current($emailTable->fetchRow(array('email = ?' => $value['email'])))) {
                $this->_error(self::EMAIL_ALREADY_EXISTS, $value['email']);
            }
        } elseif ($isPost) {
            $this->_error(self::REQUIRE_EMAIL);
        }

        // ensure that user_id is provided with post, ignore if not post.
        // Ignoring put disables transfering the email to a different user
        if (isset($value['user_id']) && $isPost) {
            $userTable = new Default_Model_DbTable_User();
            if (false === current($userTable->find($value['user_id']))) {
                $this->_error(self::NOT_VALID_USER_ID, $value['user_id']);
            }
        } elseif ($isPost) {
            $this->_error(self::REQUIRE_USER_ID);
        }

        return $partialHappened;
    }
}
