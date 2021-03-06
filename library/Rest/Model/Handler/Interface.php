<?php

interface Rest_Model_Handler_Interface
{
    /**
     * Used mainly to ensure that the required keys have been passed to
     * controllers that inturn implement model handlers
     *
     * @return array
     */
    public static function getIdentityKeys();

    /**
     * Used mainly for testing property requests, where clauses and the like
     * @return array
     */
    public static function getPropertyKeys();

    /**
     * @param array $params
     * @return array
     */
    public function getList(array $params = null);

    /**
     * @param array $id
     * @return array
     * @throws Rest_Model_NotFoundException
     */
    public function get(array $id, array $params = null);

    /**
     * @param array $id
     * @param array $prop
     * @return array
     * @throws Rest_Model_NotFoundException
     */
    public function put(array $id, array $prop = null);

    /**
     * @param array $id
     * @throws Rest_Model_NotFoundException
     */
    public function delete(array $id);

    /**
     * @param array $prop
     * @return array
     */
    public function post(array $prop);
}
