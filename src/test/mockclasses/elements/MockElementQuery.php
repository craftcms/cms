<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */

namespace craft\test\mockclasses\elements;

use Craft;
use craft\elements\db\ElementQuery;
use craft\helpers\StringHelper;

/**
 * MockElementQuery is used to mimic element query and help mocking element query results.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.5
 */
class MockElementQuery extends ElementQuery
{
    const CLASS_TEMPLATE_FILE = '../../templates/mockElementQuery.tpl';

    /**
     * The "elements" to return when invoking `one()` or `all()`
     *
     * @var array
     */
    protected $returnValues = [];

    /**
     * The element query properties
     *
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
     * Generate a more specific query class for the provided element type class.
     *
     * @param $elementClass
     */
    public static function generateSpecificQueryClass($elementClass): ElementQuery
    {
        $parts = explode('\\', $elementClass);

        // Split out the relevant parts and generate a prefix
        $element = array_pop($parts);
        $namespace = implode('\\', $parts);
        $prefix = StringHelper::randomStringWithChars('abcdefghijklmnopqrstuvwxyz', 20);
        $className = $prefix . 'MockElementQuery';

        // Load template and fill it with the relevant values
        $template = file_get_contents(__DIR__ . '/' . self::CLASS_TEMPLATE_FILE);
        $classData = str_replace(['{element}', '{namespace}', '{className}'], [$element, $namespace, $className], $template);

        // Include the class and return an instance of it
        $tempPath = Craft::$app->getPath()->getTempPath() . DIRECTORY_SEPARATOR . $prefix . '.php';
        file_put_contents($tempPath, $classData);
        include($tempPath);
        $instance = new $className;
        unlink($tempPath);

        return $instance;
    }

    /**
     * Set the return values.
     *
     * @param array $values
     * @return static
     */
    public function setReturnValues(array $values = [])
    {
        $this->returnValues = $values;

        return $this;
    }

    /**
     * Setter for mock query arguments.
     *
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
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
    public function __get($name)
    {
        return $this->properties[$name] ?? null;
    }

    /**
     * Mock setting query arguments via a method call.
     *
     * @param $method
     * @param $arguments
     * @return static
     */
    public function __call($method, $arguments)
    {
        $this->properties[$method] = reset($arguments);

        return $this;
    }

    /**
     * Return all the return values.
     *
     * @return array
     */
    public function all($db = null): array
    {
        return $this->returnValues;
    }

    /**
     * Return a return value.
     *
     * @return mixed|null
     */
    public function one($db = null)
    {
        return !empty($this->returnValues) ? reset($this->returnValues) : null;
    }
}
