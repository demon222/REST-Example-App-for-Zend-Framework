<?php

/**
 * @uses       Zend_Validate
 * @package    QuickStart
 * @subpackage Validate
 */
class Default_Validate_Entry extends Zend_Validate_Abstract
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

        // validate that the email is correct
        if (isset($value['email'])) {
            $partialOk = true;

            $filter = new Zend_Filter_StringTrim();
            $value['email'] = $filter->filter($value['email']);
            
            $validator = new Zend_Validate_EmailAddress();
            if (!$validator->isValid($value['email'])) {
                $this->setMessages($validator->getMessages());
            }
        }

        // validate that the comment is set
        if (isset($value['comment'])) {
            $partialOk = true;

            $validator = new Zend_Validate_StringLength(array(0, 20));
            if (!$validator->isValid($value['comment'])) {
                $this->setMessages($validator->getMessages());
            }
        }

        // not enough data for even a partial validation
        if (false === $partialOk) {
            $this->_error(self::NOT_ENOUGH_DATA);
        }

        // report failure if there are any messages
        if ($this->getMessageLength() > 0) {
            return false;
        }

        // validation passed!
        return true;
    }

}
