<?php

abstract class ZendPatch_Validate_Abstract extends Zend_Validate_Abstract {

    /**
     * Used to bring in messages and errors from aggregated validates
     *
     * @param Zend_Validate_Interface $validate
     * return Zend_Validate_Interface
     */
    protected function _absorbValidateMessagesAndErrors($validate)
    {
        foreach ($validate->getMessages() as $code => $message) {
            $this->_messages[$code] = $message;
        }
        foreach ($validate->getErrors() as $error) {
            $this->_errors[] = $error;
        }
        return $this;
    }
}
