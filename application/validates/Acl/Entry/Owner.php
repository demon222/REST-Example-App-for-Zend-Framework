<?php
require_once('Rest/Validate/Acl/Abstract.php');

class Default_Validate_Acl_Entry_Owner extends Rest_Validate_Acl_Abstract
{
    const REQUIRE_COMMENT = 'requireComment';

    /**
     * Validation failure message template definitions
     *
     * @var array
     */
    protected $_local_messageTemplates = array(
        self::REQUIRE_COMMENT => '"comment" is a required property',
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
return true;
        $partialHappened = false;

        $isPost = 'post' == $this->getMethodContext();

        // comment is set optional for put, required for post
        if (isset($value['comment'])) {
            $partialHappened = true;

            $validate = new Zend_Validate_StringLength(array(1, 1000));
            if (!$validate->isValid($value['comment'])) {
                $this->_addValidateMessagesAndErrors($validate);
            }
        } elseif ('post' == $this->getMethodContext()) {
            $this->_error(self::REQUIRE_COMMENT);
        }

        // note: unlike regular entry validation, the acl assigns creator_user_id
        // in the model layer, so no check for its validity is needed

        return $partialHappened;
    }
}
