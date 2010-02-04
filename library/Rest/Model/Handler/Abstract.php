<?php
require_once('Rest/Model/Handler/Interface.php');

abstract class Rest_Model_Handler_Abstract implements Rest_Model_Handler_Interface
{
    /**
     * Used mainly to ensure that the required keys have been passed to
     * controllers that inturn implement model handlers
     *
     * @return array
     */
    public static function getIdentityKeys()
    {
        return array('id');
    }
}
