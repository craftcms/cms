<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\test\mockclasses\elements;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\elements\db\ElementQuery;
use craft\helpers\StringHelper;

/**
 * MockElementQuery is used to mimic element queries and mock their results
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5
 */
class MockElementQuery extends ElementQuery
{
    public const CLASS_TEMPLATE_FILE = '../../templates/mockElementQuery.tpl';

    /**
     * The "elements" to return when invoking `one()` or `all()`
     *
     * @var array
     */
    protected array $returnValues = [];

    /**
     * The element query properties
     *
     * @var array
     */
    protected array $properties = [];

    /**
     * MockElementQuery constructor.
     */
    public function __construct()
    {
        parent::__construct(ExampleElement::class, []);
    }

    /**
     * Generate a more specific query class for the provided element type class.
     *
     * @param string $elementClass
     * @phpstan-param class-string<ElementInterface> $elementClass
     * @return ElementQuery
     */
    public static function generateSpecificQueryClass(string $elementClass): ElementQuery
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
        $instance = new $className();
        unlink($tempPath);

        return $instance;
    }

    /**
     * Set the return values.
     *
     * @param array $values
     * @return self
     */
    public function setReturnValues(array $values = []): self
    {
        $this->returnValues = $values;
        return $this;
    }

    /**
     * Setter for mock query arguments.
     *
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        $this->properties[$name] = $value;
    }

    /**
     * Check if a property has been set already.
     *
     * @param string $name
     * @return bool
     */
    public function __isset($name): bool
    {
        return array_key_exists($name, $this->properties);
    }

    /**
     * Getter for mock query arguments.
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->properties[$name] ?? null;
    }

    /**
     * Mock setting query arguments via a method call.
     *
     * @param string $name
     * @param array $params
     * @return self
     */
    public function __call($name, $params): self
    {
        $this->properties[$name] = reset($params);
        return $this;
    }

    /**
     * Return all the return values.
     *
     * @param mixed $db
     * @return array
     */
    public function all($db = null): array
    {
        return $this->returnValues;
    }

    /**
     * @inheritdoc
     */
    public function one($db = null): mixed
    {
        return !empty($this->returnValues) ? reset($this->returnValues) : null;
    }
}
