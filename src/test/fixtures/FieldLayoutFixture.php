<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */

namespace craft\test\fixtures;

use Craft;
use craft\base\Field;
use craft\base\Model;
use craft\db\Query;
use craft\db\Table;
use craft\fields\Matrix;
use craft\helpers\ArrayHelper;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\services\Fields;
use craft\test\Fixture;
use Throwable;
use yii\base\Exception as YiiBaseException;
use yii\base\InvalidArgumentException;
use yii\base\NotSupportedException;
use yii\db\Exception as YiiDbException;

/**
 * Class FieldLayoutFixture.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since  3.2
 */
abstract class FieldLayoutFixture extends Fixture
{
    // Public Methods
    // =========================================================================

    /**
     * @throws Throwable
     * @throws YiiBaseException
     */
    public function load()
    {
        $fieldsService = Craft::$app->getFields();

        foreach ($this->getData() as $fieldLayout) {
            // Get the tabs from the $fieldLayout value and unset the tabs (for later)
            $tabs = $this->extractTabsFromFieldLayout($fieldLayout);

            // Get the tabs setup in such a way they can be set with $fieldLayout->setTabs()
            $tabsToAdd = $this->getTabsForFieldLayout($tabs);

            // Setup the field layout and set the tabs
            $fieldLayout = new FieldLayout($fieldLayout);
            $fieldLayout->setTabs($tabsToAdd);

            // Save the field layout (Including all the tabs)
            if (!$fieldsService->saveLayout($fieldLayout)) {
                $this->throwModelError($fieldLayout);
            }

            // Loop through the saved tabs (Which now have an id param)
            foreach ($fieldLayout->getTabs() as $tab) {
                // Get the content from our fields from the original data array (from $this->dataFile)
                $tabContent = ArrayHelper::firstWhere($tabs, 'name', $tab->name);

                $fieldSortOrder = 1;

                // Loop and add.
                foreach ($tabContent['fields'] as $fieldData) {
                    $field = $fieldData['field'];

                    // Get the class to setup. Then remove it.
                    $class = $field['fieldType'];
                    unset($field['fieldType']);

                    $blockTypes = [];
                    if (($class instanceof Matrix) && isset($field['blockTypes'])) {
                        $blockTypes = $field['blockTypes'];
                        unset($field['blockTypes']);
                    }

                    // Create and add a field.
                    /* @var Field $field*/
                    $field = new $class($field);
                    if (!Craft::$app->getFields()->saveField($field)) {
                        $this->throwModelError($field);
                    }

                    // Set any block types to the matrix.
                    if ($field instanceof Matrix && $blockTypes) {
                        $field->setBlockTypes($blockTypes);
                    }

                    // Link it
                    $link = $fieldData['layout-link'];
                    $link['sortOrder'] = $fieldSortOrder;
                    $this->linkFieldToLayout($link, $field, $fieldLayout, $tab);

                    $fieldSortOrder++;
                }
            }
        }

        Craft::$app->set('fields', new Fields());
    }

    /**
     * @return bool
     * @throws Throwable
     */
    public function unload()
    {
        foreach ($this->getData() as $fieldLayout) {
            foreach ($fieldLayout['tabs'] as $tab) {
                foreach ($tab['fields'] as $fieldData) {
                    $field = $fieldData['field'];
                    if ($this->deleteAllByFieldHandle($field['handle'])) {
                        // Its deleted. On-to the next field layout.
                        continue 3;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Unloading fixtures removes fields and possible tables - so we need to refresh the DB Schema before our parent calls.
     * Craft::$app->getDb()->createCommand()->checkIntegrity(true);
     *
     * @throws NotSupportedException
     */
    public function afterUnload()
    {
        $this->db->getSchema()->refresh();

        parent::afterUnload();
    }

    /**
     * Attempt to delete all fields and field layout by a field handle.
     *
     * 1. Get a field by handle
     * 2. Get its layout
     * 3. Traverse down the data (getTabs() and then on each tab getFields()
     * 4. Delete all fields.
     * 5. Delete the field layout.
     *
     * @param string $fieldHandle
     * @return bool
     * @throws Throwable
     * @todo Can we use `craft\test\Craft`:getFieldLayoutByFieldHandle()?
     */
    public function deleteAllByFieldHandle(string $fieldHandle): bool
    {
        if (!$field = Craft::$app->getFields()->getFieldByHandle($fieldHandle)) {
            return false;
        }

        /** @var Field $field */
        $layoutId = (new Query())
            ->select(['layoutId'])
            ->from([Table::FIELDLAYOUTFIELDS])
            ->where(['fieldId' => $field->id])
            ->column();

        if ($layoutId) {
            $layoutId = ArrayHelper::firstValue($layoutId);

            foreach (Craft::$app->getFields()->getLayoutById($layoutId)->getTabs() as $tab) {
                foreach ($tab->getFields() as $field) {
                    if (!Craft::$app->getFields()->deleteField($field)) {
                        $this->throwModelError($field);
                    }
                }
            }
            return Craft::$app->getFields()->deleteLayoutById($layoutId);
        }

        return false;
    }

    // Protected Methods
    // =========================================================================

    /**
     * @param array $tabs
     * @return array
     */
    protected function getTabsForFieldLayout(array $tabs): array
    {
        $tabSortOrder = 1;
        $tabsToAdd = [];

        foreach ($tabs as $tab) {
            if (isset($tab['fields'])) {
                unset($tab['fields']);
            }

            $tab['sortOrder'] = $tabSortOrder;
            $tabsToAdd[] = $tab;

            $tabSortOrder++;
        }

        return $tabsToAdd;
    }

    /**
     * @param array $fieldLayout
     * @return array
     */
    protected function extractTabsFromFieldLayout(array $fieldLayout): array
    {
        $tabs = [];

        if (isset($fieldLayout['tabs'])) {
            $tabs = $fieldLayout['tabs'];
            unset($fieldLayout['tabs']);
        }

        return $tabs;
    }

    /**
     * @param array $link
     * @param Field $field
     * @param FieldLayout $fieldLayout
     * @param FieldLayoutTab $tab
     * @return bool
     * @throws YiiDbException
     */
    protected function linkFieldToLayout(array $link, Field $field, FieldLayout $fieldLayout, FieldLayoutTab $tab): bool
    {
        $link['fieldId'] = $field->id;
        $link['tabId'] = $tab->id;
        $link['layoutId'] = $fieldLayout->id;

        $executed = Craft::$app->getDb()->createCommand()
            ->insert(Table::FIELDLAYOUTFIELDS,
                $link
            )->execute();

        if (!$executed) {
            throw new InvalidArgumentException("Unable to link field $field->handle to field layout $fieldLayout->type");
        }

        return true;
    }

    /**
     * @param Model $model
     * @throws InvalidArgumentException
     */
    protected function throwModelError(Model $model)
    {
        throw new InvalidArgumentException(
            implode(
                ' ',
                $model->getErrorSummary(true)
            )
        );
    }
}
