<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\test\fixtures;

use Craft;
use craft\base\ModelInterface;
use craft\helpers\Component;
use craft\test\DbFixtureTrait;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Field;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\FieldLayoutTab;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Typecast;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use PDO;
use Throwable;
use yii\base\Exception as YiiBaseException;
use yii\base\NotSupportedException;
use yii\test\DbFixture;
use yii\test\FileFixtureTrait;

/**
 * Class FieldLayoutFixture.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
abstract class FieldLayoutFixture extends DbFixture
{
    use DbFixtureTrait;
    use FileFixtureTrait;

    /**
     * @var \CraftCms\Cms\FieldLayout\FieldLayout[]
     */
    private array $_layouts = [];

    /**
     * @var FieldInterface[]
     */
    private array $_fields = [];

    /**
     * @throws Throwable
     * @throws YiiBaseException
     */
    public function load(): void
    {
        $fieldsService = app(Fields::class);

        foreach ($this->getData() as $layoutConfig) {
            // Get the tabs from the $fieldLayout value and unset the tabs (for later)
            $tabConfigs = Arr::pull($layoutConfig, 'tabs' ,[]);

            $layout = null;
            if (isset($layoutConfig['uid'])) {
                $layout = $fieldsService->getLayoutByUid($layoutConfig['uid']);
            }
            $layout ??= new FieldLayout();
            Typecast::configure($layout, $layoutConfig);
            $this->_layouts[] = $layout;

            $tabs = [];

            foreach ($tabConfigs as $tabIndex => $tabConfig) {
                $fieldConfigs = Arr::pull($tabConfig, 'fields', []);

                $tab = $tabs[] = new FieldLayoutTab(['layout' => $layout] + $tabConfig);
                $tab->sortOrder = $tabIndex + 1;
                $layoutElements = [];

                foreach ($fieldConfigs as $fieldConfig) {
                    // config[field] + config[layout-link] -> config
                    if (isset($fieldConfig['field'])) {
                        $fieldConfig = array_merge($fieldConfig['field'], $fieldConfig['layout-link']);
                    }

                    // fieldType -> type
                    if (isset($fieldConfig['fieldType'])) {
                        $fieldConfig['type'] = Arr::pull($fieldConfig, 'fieldType');
                    }

                    $required = Arr::pull($fieldConfig, 'required', false);

                    $fieldClass = new ($fieldConfig['type']);
                    // if the field config type indicates that it's a custom field - proceed as before;
                    // create field component, save it and add to layout elements
                    if ($fieldClass instanceof FieldInterface) {
                        /** @var FieldInterface|Field $field */
                        $field = $this->_fields[] = Component::createComponent($fieldConfig, FieldInterface::class);

                        if (!$fieldsService->saveField($field)) {
                            $this->throwModelError($field);
                        }

                        $layoutElements[] = new CustomField($field, [
                            'required' => $required,
                        ]);
                    } else {
                        // otherwise it's a native field, so add it to the layout element]
                        $layoutElements[] = $fieldClass;
                    }
                }

                $tab->setElements($layoutElements);
            }

            $layout->setTabs($tabs);
            $fieldsService->saveLayout($layout);
        }
    }

    /**
     * Returns the fixture data.
     *
     * The default implementation will try to return the fixture data by including the external file specified by [[dataFile]].
     * The file should return an array of data rows (column name => column value), each corresponding to a row in the table.
     *
     * If the data file does not exist, an empty array will be returned.
     *
     * @return array the data rows to be inserted into the database table.
     */
    protected function getData(): array
    {
        return $this->loadData($this->dataFile);
    }

    /**
     * @inheritdoc
     */
    public function unload(): void
    {
        $this->checkIntegrity(true);
        DB::connection()->getPdo()->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

        $fieldsService = app(Fields::class);

        foreach ($this->_fields as $field) {
            /** @var FieldInterface|Field $field */
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
    public function afterUnload(): void
    {
        $this->db->getSchema()->refresh();
    }

    /**
     * @param ModelInterface $model
     * @throws InvalidArgumentException
     */
    protected function throwModelError(ModelInterface $model): void
    {
        throw new InvalidArgumentException(
            implode(
                ' ',
                $model->getErrorSummary(true)
            )
        );
    }
}
