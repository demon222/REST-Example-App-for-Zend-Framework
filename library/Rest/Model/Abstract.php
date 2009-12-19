<?php

require_once('Rest/Model/Interface.php');

abstract class Rest_Model_Abstract implements Rest_Model_Interface
{

    /**
     * Constructor
     *
     * @param  array|null $options
     * @return void
     */
    public function __construct(array $options = null)
    {
        if (is_array($options)) {
            $this->setOptions($options);
        }
    }

    /**
     * Sets object state by taking an associative array and calling set methods
     * corresponding to the array's keys.
     *
     * @param array $options
     * @return Rest_Model_Interface
     */
    public function setOptions(array $options)
    {
        $methods = get_class_methods($this);
        foreach ($options as $key => $value) {
            $method = 'set' . ucfirst($key);
            if (in_array($method, $methods)) {
                $this->$method($value);
            }
        }
        return $this;
    }

    /**
     * Overloading: allow property access
     *
     * @param  string $name
     * @param  mixed $value
     * @return void
     */
    public function __set($name, $value)
    {
        $method = 'set' . $name;
        if (!method_exists($this, $method)) {
            throw Exception('Invalid property "' . $name . '" specified');
        }
        $this->$method($value);
    }

    /**
     * Overloading: allow property access
     *
     * @param  string $name
     * @return mixed
     */
    public function __get($name)
    {
        $method = 'get' . $name;
        if (!method_exists($this, $method)) {
            throw Exception('Invalid property "' . $name . '" specified');
        }
        return $this->$method();
    }
}
