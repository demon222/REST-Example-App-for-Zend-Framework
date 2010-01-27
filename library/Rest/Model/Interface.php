<?php

/**
 * Rest_Model_Interface
 *
 * @category   Rest
 * @package    Rest_Model
 */
interface Rest_Model_Interface
{
    /**
     * Constructor
     *
     * @param  array|null $options
     * @return void
     */
    public function __construct(array $options = null);
    
    /**
     * Return a PHP array data structure, not necessarily flat, representing
     * this model. Can return differently dimensioned arrays based on context.
     * Context can include which permission the set user has
     *
     * @param mixed $element
     * @return array
     */
    public function toArray();

    /**
     * Provide a set of id key names. These values are commonly used to determine
     * what values are needed to uniquely identify a resource for get, put, or
     * delete methods
     *
     * @return array
     */
    public function getIdentityKeys();

    /**
     * Sets object state by taking an associative array and calling set methods
     * corresponding to the array's keys.
     *
     * @param array $options
     * @return Rest_Model_Interface
     */
    public function setOptions(array $options);

    /**
     * Calls the persistance layer to update the properities of this model
     */
    public function put();

    /**
     * Calls the persistance layer to create a new
     */
    public function post();

    /**
     * Calls the persistance layer to remove this object
     */
    public function delete();

    /**
     * load this object with the values from the persistance layer
     *
     * @param mixed $id
     */
    public function get();

    /**
     * Fetch all entries from the persistance layer. Returns an array of model
     * objects.
     *
     * @return array
     */
    public function fetchAll();

    /**
     * Fetch all entries from the persistance layer and return as array of
     * associative arrays (key-value pairs).
     *
     * @return array
     */
    public function fetchAllAsArrays();

    /**
     * Overloading: allow property access
     *
     * @param  string $name
     * @param  mixed $value
     * @return void
     */
    public function __set($name, $value);

    /**
     * Overloading: allow property access
     *
     * @param  string $name
     * @return mixed
     */
    public function __get($name);
}
