<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\test\fixtures\elements;

use Craft;
use craft\base\ElementInterface;
use craft\errors\InvalidElementException;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\test\DbFixture;
use yii\test\FileFixtureTrait;

/**
 * BaseContentFixture is a base class for setting up fixtures for populating existing elements.
 *
 * Fixture classes that extend this class should set [[$elementType]] to the class name of an element type,
 * and [[$dataFile]] to the path to a data file.
 *
 * The data file should return an array, where each item is a sub-array containing the following  keys:
 *
 * - `criteria` – an array of element query param names/values that should be used to locate the element
 * - `attributes` – an array of attribute names/values that should be set on the element
 * - `fields` – an array of custom field handles/values that should be set on the element
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.6.0
 */
abstract class BaseContentFixture extends DbFixture
{
    use FileFixtureTrait;

    /**
     * @var string The element type this is for
     * @phpstan-var class-string<ElementInterface>
     */
    public string $elementType;

    /**
     * @var bool Whether to skip elements that can’t be found per the criteria in the [[$dataFile|data file]].
     *
     * If this is set to `false`, an exception will be thrown.
     */
    public bool $skipMissingElements = true;

    /**
     * @var ElementInterface[]|null[] The loaded elements
     */
    private array $_elements = [];

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        if (!isset($this->elementType) || !is_subclass_of($this->elementType, ElementInterface::class)) {
            throw new InvalidConfigException('$elementType must set to a valid element class name');
        }
    }

    /**
     * @inheritdoc
     */
    public function load(): void
    {
        foreach ($this->loadData($this->dataFile) as $key => $data) {
            $element = $this->findElement($data);

            if ($element === null) {
                if ($this->skipMissingElements) {
                    continue;
                }
                throw new Exception("Couldn’t find element of type $this->elementType with the given criteria");
            }

            $this->populateElement($element, $data);

            if (!$this->saveElement($element)) {
                throw new InvalidElementException($element, implode(' ', $element->getErrorSummary(true)));
            }

            $this->_elements[$key] = $element;
        }
    }

    /**
     * @inheritdoc
     */
    public function unload(): void
    {
        $this->_elements = [];
    }

    /**
     * Get element model.
     *
     * @param string $key The key of the element in the [[$dataFile|data file]].
     * @return ElementInterface|null
     */
    public function getElement(string $key): ?ElementInterface
    {
        return $this->_elements[$key] ?? null;
    }

    /**
     * Finds an element with the given criteria.
     *
     * @param array $data
     * @return ElementInterface|null
     */
    protected function findElement(array $data): ?ElementInterface
    {
        /** @var ElementInterface $class */
        $class = $this->elementType;
        $query = $class::find();
        if (isset($data['criteria'])) {
            Craft::configure($query, $data['criteria']);
        }
        return $query->one();
    }

    /**
     * Populates an element with the given attributes/custom field values.
     *
     * @param ElementInterface $element
     * @param array $data
     */
    protected function populateElement(ElementInterface $element, array $data): void
    {
        if (isset($data['attributes'])) {
            foreach ($data['attributes'] as $name => $value) {
                $element->$name = $value;
            }
        }

        if (isset($data['fields'])) {
            $element->setFieldValues($data['fields']);
        }
    }

    /**
     * Saves an element.
     *
     * @param ElementInterface $element The element to be saved
     * @return bool Whether the save was successful
     */
    protected function saveElement(ElementInterface $element): bool
    {
        return Craft::$app->getElements()->saveElement($element, true, true, false);
    }
}
