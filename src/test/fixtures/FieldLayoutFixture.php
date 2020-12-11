<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */

namespace craft\test\fixtures;

use Craft;
use craft\base\FieldInterface;
use craft\base\Model;
use craft\fieldlayoutelements\CustomField;
use craft\helpers\ArrayHelper;
use craft\helpers\Component;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\test\ActiveFixture;
use craft\test\DbFixtureTrait;
use Throwable;
use yii\base\Exception as YiiBaseException;
use yii\base\InvalidArgumentException;
use yii\base\NotSupportedException;

/**
 * Class FieldLayoutFixture.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since  3.2
 */
abstract class FieldLayoutFixture extends ActiveFixture
{
    use DbFixtureTrait;

    /**
     * @var FieldLayout[]
     */
    private $_layouts = [];

    /**
     * @var FieldInterface[]
     */
    private $_fields = [];

    /**
     * @throws Throwable
     * @throws YiiBaseException
     */
    public function load()
    {
        $fieldsService = Craft::$app->getFields();

        foreach ($this->getData() as $layoutConfig) {
            // Get the tabs from the $fieldLayout value and unset the tabs (for later)
            $tabConfigs = ArrayHelper::remove($layoutConfig, 'tabs') ?? [];

            $layout = $this->_layouts[] = new FieldLayout($layoutConfig);
            $tabs = [];

            foreach ($tabConfigs as $tabIndex => $tabConfig) {
                $fieldConfigs = ArrayHelper::remove($tabConfig, 'fields') ?? [];

                $tab = $tabs[] = new FieldLayoutTab($tabConfig);
                $tab->sortOrder = $tabIndex + 1;
                $tab->elements = [];

                foreach ($fieldConfigs as $fieldConfig) {
                    // config[field] + config[layout-link] -> config
                    if (isset($fieldConfig['field'])) {
                        $fieldConfig = array_merge($fieldConfig['field'], $fieldConfig['layout-link']);
                    }

                    // fieldType -> type
                    if (isset($fieldConfig['fieldType'])) {
                        $fieldConfig['type'] = ArrayHelper::remove($fieldConfig, 'fieldType');
                    }

                    $required = ArrayHelper::remove($fieldConfig, 'required') ?? false;
                    /** @var FieldInterface $field */
                    $field = $this->_fields[] = Component::createComponent($fieldConfig, FieldInterface::class);

                    if (!$fieldsService->saveField($field)) {
                        $this->throwModelError($field);
                    }

                    $tab->elements[] = new CustomField($field, [
                        'required' => $required,
                    ]);
                }
            }

            $layout->setTabs($tabs);
            $fieldsService->saveLayout($layout);
        }
    }

    /**
     * @inheritdoc
     */
    public function unload()
    {
        $this->checkIntegrity(true);

        $fieldsService = Craft::$app->getFields();

        foreach ($this->_fields as $field) {
            if (!$fieldsService->deleteField($field)) {
                $this->throwModelError($field);
            }
        }

        foreach ($this->_layouts as $layout) {
            if (!$fieldsService->deleteLayout($layout)) {
                $this->throwModelError($layout);
            }
        }

        $this->_layouts = [];
        $this->_fields = [];

        $this->hardDelete();
        $this->checkIntegrity(false);
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
