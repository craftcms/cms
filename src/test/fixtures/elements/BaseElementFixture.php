<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\test\fixtures\elements;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\db\Table;
use craft\elements\Entry;
use craft\errors\InvalidElementException;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\models\FieldLayout;
use craft\test\DbFixtureTrait;
use yii\log\Logger;
use yii\test\DbFixture;
use yii\test\FileFixtureTrait;

/**
 * Class BaseElementFixture is a base class for setting up fixtures for element types.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Robuust digital | Bob Olde Hampsink <bob@robuust.digital>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.6.0
 */
abstract class BaseElementFixture extends DbFixture
{
    use FileFixtureTrait;
    use DbFixtureTrait;

    /**
     * @var array
     */
    protected array $siteIds = [];

    /**
     * @var ElementInterface[] The loaded elements
     */
    private array $_elements = [];

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        foreach (Craft::$app->getSites()->getAllSites() as $site) {
            $this->siteIds[$site->handle] = $site->id;
        }
    }

    /**
     * @inheritdoc
     */
    public function load(): void
    {
        $fieldsService = Craft::$app->getFields();

        foreach ($this->loadData($this->dataFile) as $key => $data) {
            $element = $this->createElement();

            // If they want to add a dateDeleted, store it but don't set it on the element
            $dateDeleted = ArrayHelper::remove($data, 'dateDeleted');

            // Set the field layout
            $fieldLayout = null;
            if (isset($data['fieldLayoutType'])) {
                $fieldLayoutType = ArrayHelper::remove($data, 'fieldLayoutType');
                $fieldLayout = $fieldsService->getLayoutByType($fieldLayoutType);
                if ($fieldLayout->id === null) {
                    if (function_exists('codecept_debug')) {
                        codecept_debug("Field layout with type: $fieldLayoutType could not be found");
                    } else {
                        Craft::getLogger()->log(
                            "Field layout with type: $fieldLayoutType could not be found",
                            Logger::LEVEL_WARNING,
                            'testing'
                        );
                    }
                }
            } elseif (isset($data['fieldLayoutUid'])) {
                $fieldLayoutUid = ArrayHelper::remove($data, 'fieldLayoutUid');
                $fieldLayout = $fieldsService->getLayoutByUid($fieldLayoutUid);
                if (!$fieldLayout) {
                    $fieldLayout = new FieldLayout([
                        'type' => Entry::class,
                        'uid' => $fieldLayoutUid,
                    ]);
                    $fieldsService->saveLayout($fieldLayout);
                }
            }
            if ($fieldLayout?->id !== null) {
                $element->fieldLayoutId = $fieldLayout->id;
            }

            $this->populateElement($element, $data);

            if ($element->enabled && $element->getIsCanonical() && !$element->isProvisionalDraft) {
                $element->setScenario(Element::SCENARIO_LIVE);
            }

            if (!$this->saveElement($element)) {
                throw new InvalidElementException($element, implode(' ', $element->getErrorSummary(true)));
            }

            if ($dateDeleted) {
                // Now that the element exists, update its dateDeleted value
                Db::update(Table::ELEMENTS, [
                    'dateDeleted' => Db::prepareDateForDb($dateDeleted),
                ], ['id' => $element->id], [], false);
            } else {
                // Only need to index the search keywords if it’s not deleted
                Craft::$app->getSearch()->indexElementAttributes($element);
            }

            $this->_elements[$key] = $element;
        }
    }

    /**
     * @inheritdoc
     */
    public function unload(): void
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
