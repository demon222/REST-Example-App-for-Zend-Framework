<?php
require_once('Rest/Validate/Abstract.php');

class Default_Validate_Entry extends Rest_Validate_Abstract
{
    const REQUIRE_COMMENT = 'requireComment';
    const REQUIRE_CREATOR_USER_ID = 'requireCreatorUserId';
    const INVALID_CREATOR_USER_ID = 'invalidCreatorUserId';

    /**
     * Validation failure message template definitions
     *
     * @var array
     */
    protected $_local_messageTemplates = array(
        self::REQUIRE_COMMENT => '"comment" is a required property',
        self::REQUIRE_CREATOR_USER_ID => '"creator_user_id" is a required property',
        self::INVALID_CREATOR_USER_ID => '%value% is not a valid "creator_user_id" property',
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

        // comment is set optional for put, required for post
        if (isset($value['comment'])) {
            $partialHappened = true;

            $validate = new Zend_Validate_StringLength(array(1, 1000));
            if (!$validate->isValid($value['comment'])) {
                $this->_addValidateMessagesAndErrors($validate);
            }
        } elseif ($isPost) {
            $this->_error(self::REQUIRE_COMMENT);
        }

        // creator_user_id required for post
        // creator can't be changed for put, so if not post, then don't bother
        // checking this, it will be ignored later
        if (isset($value['creator_user_id']) && $isPost) {
            $userTable = new Default_Model_DbTable_User();
            if (false === current($userTable->find($value['creator_user_id']))) {
                $this->_error(self::INVALID_CREATOR_USER_ID, $value['creator_user_id']);
            }
        } elseif ($isPost) {
            $this->_error(self::REQUIRE_CREATOR_USER_ID);
        }

        return $partialHappened;
    }
}
