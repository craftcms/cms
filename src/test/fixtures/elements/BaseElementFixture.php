<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */

namespace craft\test\fixtures\elements;

use Craft;
use craft\base\ElementInterface;
use craft\db\Table;
use craft\errors\InvalidElementException;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\test\DbFixtureTrait;
use yii\test\DbFixture;
use yii\test\FileFixtureTrait;

/**
 * Class BaseElementFixture is a base class for setting up fixtures for element types.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Robuust digital | Bob Olde Hampsink <bob@robuust.digital>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since  3.6.0
 */
abstract class BaseElementFixture extends DbFixture
{
    use FileFixtureTrait;
    use DbFixtureTrait;

    /**
     * @var array
     */
    protected $siteIds = [];

    /**
     * @var ElementInterface[] The loaded elements
     */
    private $_elements = [];

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        foreach (Craft::$app->getSites()->getAllSites() as $site) {
            $this->siteIds[$site->handle] = $site->id;
        }
    }

    /**
     * @inheritdoc
     */
    public function load()
    {
        foreach ($this->loadData($this->dataFile) as $key => $attributes) {
            $element = $this->createElement();

            // If they want to add a date deleted. Store it but dont set that as an element property
            $dateDeleted = ArrayHelper::remove($attributes, 'dateDeleted');

            // Set the field layout
            $fieldLayoutType = ArrayHelper::remove($attributes, 'fieldLayoutType');
            if ($fieldLayoutType) {
                $fieldLayout = Craft::$app->getFields()->getLayoutByType($fieldLayoutType);
                if ($fieldLayout) {
                    $element->fieldLayoutId = $fieldLayout->id;
                } else {
                    codecept_debug("Field layout with type: $fieldLayoutType could not be found");
                }
            }

            $this->populateElement($element, $attributes);

            if (!$this->saveElement($element)) {
                throw new InvalidElementException($element, implode(' ', $element->getErrorSummary(true)));
            }

            $this->afterSaveElement($element, $attributes);

            if ($dateDeleted) {
                // Now that the element exists, update its dateDeleted value
                Db::update(Table::ELEMENTS, [
                    'dateDeleted' => Db::prepareDateForDb($dateDeleted),
                ], ['id' => $element->id], [], false);
            } else {
                // Only need to index the search keywords if it's not deleted
                Craft::$app->getSearch()->indexElementAttributes($element);
            }

            $this->populateLoadedElements($element, $key, $attributes);
        }
    }

    /**
     * @inheritdoc
     */
    public function unload()
    {
        $this->checkIntegrity(true);

        foreach ($this->_elements as $element) {
            $this->deleteElement($element);
        }

        $this->checkIntegrity(false);
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
     * Creates an element.
     */
    abstract protected function createElement(): ElementInterface;

    /**
     * Populates an element’s attributes.
     *
     * @param ElementInterface $element
     * @param array $attributes
     */
    protected function populateElement(ElementInterface $element, array $attributes): void
    {
        foreach ($attributes as $name => $value) {
            $element->$name = $value;
        }
    }

    /**
     * @param ElementInterface $element
     * @param array $attributes
     */
    protected function populateLoadedElements(ElementInterface $element, int|string $key, array $attributes): void
    {
        $this->_elements[$key] = $element;
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

    /**
     * Performs actions after an element is saved.
     *
     * @param ElementInterface $element
     * @param array $attributes
     */
    protected function afterSaveElement(ElementInterface $element, array $attributes): void
    {
        // Do nothing by default
    }

    /**
     * Deletes an element.
     *
     * @param ElementInterface $element The element to be deleted
     * @return bool Whether the deletion was successful
     */
    protected function deleteElement(ElementInterface $element): bool
    {
        return Craft::$app->getElements()->deleteElement($element, true);
    }
}
