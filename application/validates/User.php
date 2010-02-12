<?php

require_once('ZendPatch/Validate/Abstract.php');

/**
 * @uses       Zend_Validate
 * @package    QuickStart
 * @subpackage Validate
 */
class Default_Validate_User extends ZendPatch_Validate_Abstract
{
    const NOT_DATA = 'notData';
    const NOT_ENOUGH_DATA = 'notEnoughData';

    /**
     * Validation failure message template definitions
     *
     * @var array
     */
    protected $_messageTemplates = array(
        self::NOT_DATA => "Data structure not supplied to validator",
        self::NOT_ENOUGH_DATA => "Not enough data for validation",
    );

    /**
     * Returns true if and only if $value meets the validation requirements
     *
     * If $value fails validation, then this method returns false, and
     * getMessages() will return an array of messages that explain why the
     * validation failed.
     *
     * @param  mixed $value
     * @return boolean
     * @throws Zend_Valid_Exception If validation of $value is impossible
     */
    public function isValid($value, $context = null)
    {
        // $value is expected to be an array
        if (!is_array($value)) {
            $this->_error(self::NOT_DATA);
            return false;
        }

        $partialOk = false;
/*
        // validate that the email is correct
        if (isset($value['email'])) {
            $partialOk = true;

            $filter = new Zend_Filter_StringTrim();
            $value['email'] = $filter->filter($value['email']);
            
            $validate = new Zend_Validate_EmailAddress();
            if (!$validate->isValid($value['email'])) {
                $this->_addValidateMessagesAndErrors($validate);
            }
        }
 */

        // validate that the name is set
        if (isset($value['name'])) {
            $partialOk = true;

            $validate = new Zend_Validate_StringLength(array(1, 50));
            if (!$validate->isValid($value['name'])) {
                $this->_addValidateMessagesAndErrors($validate);
            }
        }

        // validate that the username is set
        if (isset($value['username'])) {
            $partialOk = true;

            $validate = new Zend_Validate_StringLength(array(1, 50));
            if (!$validate->isValid($value['username'])) {
                $this->_addValidateMessagesAndErrors($validate);
            }
        }

        // not enough data for even a partial validation
        if (false === $partialOk) {
            $this->_error(self::NOT_ENOUGH_DATA);
        }

        // report failure if there are any messages
        if (count($this->_messages)) {
            return false;
        }

        // validation passed!
        return true;
    }

}
