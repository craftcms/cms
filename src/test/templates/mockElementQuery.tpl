<?php
use {namespace}\db\{element}Query;

/**
 * MockElementQuery is used to mimic element query and help mocking element query results.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.5
 */
class {className} extends {element}Query
{
    /**
     * The "elements" to return when invoking `one()` or `all()`
     * @var array
     */
    protected $returnValues = [];

    /**
     * The element query properties
     * @var array
     */
    protected $properties = [];

    /**
     * MockElementQuery constructor.
     */
    public function __construct()
    {
        parent::__construct('MockElement', []);
    }

    /**
     * Set the return values.
     *
     * @param array $values
     * @return static
     */
    public function setReturnValues(array $values = []) {
        $this->returnValues = $values;

        return $this;
    }

    /**
     * Setter for mock query arguments.
     *
     * @param $name
     * @param $value
     */
    public function __set($name, $value) {
        $this->properties[$name] = $value;
    }

    /**
     * Check if a property has been set already.
     *
     * @param $name
     * @return bool
     */
    public function __isset($name)
    {
        return array_key_exists($name, $this->properties);
    }

    /**
     * Getter for mock query arguments.
     *
     * @param $name
     */
    public function __get($name) {
        return $this->properties[$name] ?? null;
    }

    /**
     * Mock setting query arguments via a method call.
     *
     * @param $method
     * @param $arguments
     * @return static
     */
    public function __call($method, $arguments) {
        $this->properties[$method] = reset($arguments);

        return $this;
    }

    /**
     * Return all the return values.
     *
     * @return array
     */
    public function all($db = null): array {
        return $this->returnValues;
    }

    /**
     * Return a return value.
     *
     * @return mixed|null
     */
    public function one($db = null) {
        return !empty($this->returnValues) ? reset($this->returnValues) : null;
    }
}
