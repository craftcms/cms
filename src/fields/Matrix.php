<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\fields;

use Craft;
use craft\app\base\EagerLoadingFieldInterface;
use craft\app\base\Element;
use craft\app\base\ElementInterface;
use craft\app\base\Field;
use craft\app\base\FieldInterface;
use craft\app\db\Query;
use craft\app\elements\db\ElementQuery;
use craft\app\elements\db\ElementQueryInterface;
use craft\app\elements\db\MatrixBlockQuery;
use craft\app\helpers\Json;
use craft\app\helpers\StringHelper;
use craft\app\elements\MatrixBlock;
use craft\app\models\MatrixBlockType;

/**
 * Matrix represents a Matrix field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Matrix extends Field implements EagerLoadingFieldInterface
{
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName()
    {
        return Craft::t('app', 'Matrix');
    }

    // Properties
    // =========================================================================

    /**
     * @var integer Max blocks
     */
    public $maxBlocks;

    /**
     * @var integer Whether each site should get its own unique set of blocks
     */
    public $localizeBlocks = false;

    /**
     * @var MatrixBlockType[] The field’s block types
     */
    private $_blockTypes;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();
        $rules[] = [['maxBlocks'], 'integer', 'min' => 0];

        return $rules;
    }

    /**
     * Returns the block types.
     *
     * @return MatrixBlockType[]
     */
    public function getBlockTypes()
    {
        if (!isset($this->_blockTypes)) {
            if (!empty($this->id)) {
                $this->_blockTypes = Craft::$app->getMatrix()->getBlockTypesByFieldId($this->id);
            } else {
                $this->_blockTypes = [];
            }
        }

        return $this->_blockTypes;
    }

    /**
     * Sets the block types.
     *
     * @param MatrixBlockType|array $blockTypes The block type settings or actual MatrixBlockType model instances
     */
    public function setBlockTypes($blockTypes)
    {
        $this->_blockTypes = [];
        $defaultFieldConfig = [
            'type' => null,
            'name' => null,
            'handle' => null,
            'instructions' => null,
            'required' => false,
            'translationMethod' => Field::TRANSLATION_METHOD_NONE,
            'translationKeyFormat' => null,
            'typesettings' => null,
        ];

        foreach ($blockTypes as $key => $config) {
            if ($config instanceof MatrixBlockType) {
                $this->_blockTypes[] = $config;
            } else {
                $blockType = new MatrixBlockType();
                $blockType->id = $key;
                $blockType->fieldId = $this->id;
                $blockType->name = $config['name'];
                $blockType->handle = $config['handle'];

                $fields = [];

                if (!empty($config['fields'])) {
                    foreach ($config['fields'] as $fieldId => $fieldConfig) {
                        $fieldConfig = array_merge($defaultFieldConfig, $fieldConfig);

                        $fields[] = Craft::$app->getFields()->createField([
                            'type' => $fieldConfig['type'],
                            'id' => $fieldId,
                            'name' => $fieldConfig['name'],
                            'handle' => $fieldConfig['handle'],
                            'instructions' => $fieldConfig['instructions'],
                            'required' => (bool)$fieldConfig['required'],
                            'translationMethod' => $fieldConfig['translationMethod'],
                            'translationKeyFormat' => $fieldConfig['translationKeyFormat'],
                            'settings' => $fieldConfig['typesettings'],
                        ]);
                    }
                }

                $blockType->setFields($fields);
                $this->_blockTypes[] = $blockType;
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function validate($attributeNames = null, $clearErrors = true)
    {
        // Run basic model validation first
        $validates = parent::validate($attributeNames, $clearErrors);

        // Run Matrix field validation as well
        if (!Craft::$app->getMatrix()->validateFieldSettings($this)) {
            $validates = false;
        }

        return $validates;
    }

    /**
     * @inheritdoc
     */
    public static function hasContentColumn()
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml()
    {
        // Get the available field types data
        $fieldTypeInfo = $this->_getFieldOptionsForConfigurator();

        Craft::$app->getView()->registerJsResource('js/MatrixConfigurator.js');
        Craft::$app->getView()->registerJs(
            'new Craft.MatrixConfigurator('.
            Json::encode($fieldTypeInfo, JSON_UNESCAPED_UNICODE).', '.
            Json::encode(Craft::$app->getView()->getNamespace(), JSON_UNESCAPED_UNICODE).
            ');'
        );

        Craft::$app->getView()->registerTranslations('app', [
            'Are you sure you want to delete this block type?',
            'Are you sure you want to delete this field?',
            'Field Type',
            'How you’ll refer to this block type in the templates.',
            'This field is required',
            'Translation Method',
            'Not translatable',
            'Translate for each language',
            'Translate for each site',
            'Custom…',
            'Translation Key Format',
            'What this block type will be called in the CP.',
        ]);

        $fieldTypeOptions = [];

        foreach (Craft::$app->getFields()->getAllFieldTypes() as $class) {
            // No Matrix-Inception, sorry buddy.
            if ($class !== static::class) {
                $fieldTypeOptions[] = [
                    'value' => $class,
                    'label' => $class::displayName()
                ];
            }
        }

        return Craft::$app->getView()->renderTemplate('_components/fieldtypes/Matrix/settings',
            [
                'matrixField' => $this,
                'fieldTypes' => $fieldTypeOptions
            ]);
    }

    /**
     * @inheritdoc
     */
    public function afterSave()
    {
        Craft::$app->getMatrix()->saveSettings($this, false);
        parent::afterSave();
    }

    /**
     * @inheritdoc
     */
    public function beforeDelete()
    {
        Craft::$app->getMatrix()->deleteMatrixField($this);

        return parent::beforeDelete();
    }

    /**
     * @inheritdoc
     */
    public function prepareValue($value, $element)
    {
        /** @var Element $element */
        $query = MatrixBlock::find();

        // Existing element?
        if (!empty($element->id)) {
            $query->ownerId($element->id);
        } else {
            $query->id(false);
        }

        $query
            ->fieldId($this->id)
            ->siteId($element->siteId);

        // Set the initially matched elements if $value is already set, which is the case if there was a validation
        // error or we're loading an entry revision.
        if (is_array($value) || $value === '') {
            $query->status = null;
            $query->enabledForSite = false;
            $query->limit = null;
            $query->setCachedResult($this->_createBlocksFromPost($value, $element));
        }

        return $query;
    }

    /**
     * @inheritdoc
     */
    public function modifyElementsQuery(ElementQueryInterface $query, $value)
    {
        /** @var ElementQuery $query */
        if ($value == 'not :empty:') {
            $value = ':notempty:';
        }

        if ($value == ':notempty:' || $value == ':empty:') {
            $alias = 'matrixblocks_'.$this->handle;
            $operator = ($value == ':notempty:' ? '!=' : '=');

            $query->subQuery->andWhere(
                "(select count({$alias}.id) from {{matrixblocks}} {$alias} where {$alias}.ownerId = elements.id and {$alias}.fieldId = :fieldId) {$operator} 0",
                [':fieldId' => $this->id]
            );
        } else if ($value !== null) {
            return false;
        }

        return null;
    }


    /**
     * @inheritdoc
     */
    public function getInputHtml($value, $element)
    {
        $id = Craft::$app->getView()->formatInputId($this->handle);

        // Get the block types data
        $blockTypeInfo = $this->_getBlockTypeInfoForInput($element);

        Craft::$app->getView()->registerJsResource('js/MatrixInput.js');

        Craft::$app->getView()->registerJs('new Craft.MatrixInput('.
            '"'.Craft::$app->getView()->namespaceInputId($id).'", '.
            Json::encode($blockTypeInfo, JSON_UNESCAPED_UNICODE).', '.
            '"'.Craft::$app->getView()->namespaceInputName($this->handle).'", '.
            ($this->maxBlocks ? $this->maxBlocks : 'null').
            ');');

        Craft::$app->getView()->registerTranslations('app', [
            'Actions',
            'Add a block',
            'Add {type} above',
            'Are you sure you want to delete the selected blocks?',
            'Collapse',
            'Disable',
            'Disabled',
            'Enable',
            'Expand',
        ]);

        if ($value instanceof MatrixBlockQuery) {
            /** @var MatrixBlockQuery $value */
            $value
                ->limit(null)
                ->status(null)
                ->enabledForSite(false);
        }

        return Craft::$app->getView()->renderTemplate('_components/fieldtypes/Matrix/input',
            [
                'id' => $id,
                'name' => $this->handle,
                'blockTypes' => $this->getBlockTypes(),
                'blocks' => $value,
                'static' => false
            ]);
    }

    /**
     * @inheritdoc
     */
    public function validateValue($value, $element)
    {
        $errors = parent::validateValue($value, $element);
        $blocksValidate = true;

        foreach ($value as $block) {
            if (!Craft::$app->getMatrix()->validateBlock($block)) {
                $blocksValidate = false;
            }
        }

        if (!$blocksValidate) {
            $errors[] = Craft::t('app', 'Correct the errors listed above.');
        }

        if ($this->maxBlocks && count($value) > $this->maxBlocks) {
            if ($this->maxBlocks == 1) {
                $errors[] = Craft::t('app', 'There can’t be more than one block.');
            } else {
                $errors[] = Craft::t('app', 'There can’t be more than {max} blocks.', ['max' => $this->maxBlocks]);
            }
        }

        return $errors;
    }

    /**
     * @inheritdoc
     *
     * @param MatrixBlockQuery $value
     *
     * @return string
     */
    public function getSearchKeywords($value, $element)
    {
        /** @var MatrixBlockQuery $value */
        /** @var MatrixBlock $block */
        $keywords = [];
        $contentService = Craft::$app->getContent();

        foreach ($value as $block) {
            $originalContentTable = $contentService->contentTable;
            $originalFieldColumnPrefix = $contentService->fieldColumnPrefix;
            $originalFieldContext = $contentService->fieldContext;

            $contentService->contentTable = $block->getContentTable();
            $contentService->fieldColumnPrefix = $block->getFieldColumnPrefix();
            $contentService->fieldContext = $block->getFieldContext();

            foreach (Craft::$app->getFields()->getAllFields() as $field) {
                /** @var Field $field */
                $fieldValue = $block->getFieldValue($field->handle);
                $keywords[] = $field->getSearchKeywords($fieldValue, $element);
            }

            $contentService->contentTable = $originalContentTable;
            $contentService->fieldColumnPrefix = $originalFieldColumnPrefix;
            $contentService->fieldContext = $originalFieldContext;
        }

        return parent::getSearchKeywords($keywords, $element);
    }

    /**
     * @inheritdoc
     */
    public function afterElementSave(ElementInterface $element)
    {
        Craft::$app->getMatrix()->saveField($this, $element);
    }

    /**
     * @inheritdoc
     */
    public function getStaticHtml($value, $element)
    {
        if ($value) {
            $id = StringHelper::randomString();

            return Craft::$app->getView()->renderTemplate('_components/fieldtypes/Matrix/input',
                [
                    'id' => $id,
                    'name' => $id,
                    'blockTypes' => $this->getBlockTypes(),
                    'blocks' => $value,
                    'static' => true
                ]);
        } else {
            return '<p class="light">'.Craft::t('app', 'No blocks.').'</p>';
        }
    }

    /**
     * @inheritdoc
     */
    public function getEagerLoadingMap($sourceElements)
    {
        // Get the source element IDs
        $sourceElementIds = [];

        foreach ($sourceElements as $sourceElement) {
            $sourceElementIds[] = $sourceElement->id;
        }

        // Return any relation data on these elements, defined with this field
        $map = (new Query())
            ->select('ownerId as source, id as target')
            ->from('{{%matrixblocks}}')
            ->where(
                [
                    'and',
                    'fieldId=:fieldId',
                    ['in', 'ownerId', $sourceElementIds]
                ],
                [':fieldId' => $this->id])
            ->orderBy('sortOrder')
            ->all();

        return [
            'elementType' => MatrixBlock::class,
            'map' => $map,
            'criteria' => ['fieldId' => $this->id]
        ];
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function isValueEmpty($value, $element)
    {
        /** @var MatrixBlockQuery $value */
        return $value->count() === 0;
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns info about each field type for the configurator.
     *
     * @return array
     */
    private function _getFieldOptionsForConfigurator()
    {
        $fieldTypes = [];

        // Set a temporary namespace for these
        $originalNamespace = Craft::$app->getView()->getNamespace();
        $namespace = Craft::$app->getView()->namespaceInputName('blockTypes[__BLOCK_TYPE__][fields][__FIELD__][typesettings]', $originalNamespace);
        Craft::$app->getView()->setNamespace($namespace);

        foreach (Craft::$app->getFields()->getAllFieldTypes() as $class) {
            // No Matrix-Inception, sorry buddy.
            if ($class === static::class) {
                continue;
            }

            Craft::$app->getView()->startJsBuffer();
            /** @var FieldInterface $field */
            $field = new $class();
            $settingsBodyHtml = Craft::$app->getView()->namespaceInputs($field->getSettingsHtml());
            $settingsFootHtml = Craft::$app->getView()->clearJsBuffer();

            $fieldTypes[] = [
                'type' => $class,
                'name' => $class::displayName(),
                'settingsBodyHtml' => $settingsBodyHtml,
                'settingsFootHtml' => $settingsFootHtml,
            ];
        }

        Craft::$app->getView()->setNamespace($originalNamespace);

        return $fieldTypes;
    }

    /**
     * Returns info about each block type and their field types for the Matrix field input.
     *
     * @param ElementInterface $element
     *
     * @return array
     */
    private function _getBlockTypeInfoForInput($element)
    {
        /** @var Element $element */
        $blockTypes = [];

        // Set a temporary namespace for these
        $originalNamespace = Craft::$app->getView()->getNamespace();
        $namespace = Craft::$app->getView()->namespaceInputName($this->handle.'[__BLOCK__][fields]', $originalNamespace);
        Craft::$app->getView()->setNamespace($namespace);

        foreach ($this->getBlockTypes() as $blockType) {
            // Create a fake MatrixBlock so the field types have a way to get at the owner element, if there is one
            $block = new MatrixBlock();
            $block->fieldId = $this->id;
            $block->typeId = $blockType->id;

            if ($element) {
                $block->setOwner($element);
                $block->siteId = $element->siteId;
            }

            $fieldLayoutFields = $blockType->getFieldLayout()->getFields();

            foreach ($fieldLayoutFields as $field) {
                $field->setIsFresh(true);
            }

            Craft::$app->getView()->startJsBuffer();

            $bodyHtml = Craft::$app->getView()->namespaceInputs(Craft::$app->getView()->renderTemplate('_includes/fields',
                [
                    'namespace' => null,
                    'fields' => $fieldLayoutFields
                ]));

            // Reset $_isFresh's
            foreach ($fieldLayoutFields as $field) {
                $field->setIsFresh(null);
            }

            $footHtml = Craft::$app->getView()->clearJsBuffer();

            $blockTypes[] = [
                'handle' => $blockType->handle,
                'name' => Craft::t('site', $blockType->name),
                'bodyHtml' => $bodyHtml,
                'footHtml' => $footHtml,
            ];
        }

        Craft::$app->getView()->setNamespace($originalNamespace);

        return $blockTypes;
    }

    /**
     * Creates an array of blocks based on the given post data
     *
     * @param mixed                 $value   The raw field value
     * @param ElementInterface|null $element The element the field is associated with, if there is one
     *
     * @return MatrixBlock[]
     */
    private function _createBlocksFromPost($value, $element)
    {
        /** @var Element $element */
        // Get the possible block types for this field
        $blockTypes = Craft::$app->getMatrix()->getBlockTypesByFieldId($this->id, 'handle');

        if (!is_array($value)) {
            return [];
        }

        $oldBlocksById = [];

        // Get the old blocks that are still around
        if (!empty($element->id)) {
            $ownerId = $element->id;

            $ids = [];

            foreach (array_keys($value) as $blockId) {
                if (is_numeric($blockId) && $blockId != 0) {
                    $ids[] = $blockId;
                }
            }

            if ($ids) {
                $oldBlocksById = MatrixBlock::find()
                    ->fieldId($this->id)
                    ->ownerId($ownerId)
                    ->id($ids)
                    ->limit(null)
                    ->status(null)
                    ->enabledForSite(false)
                    ->siteId($element->siteId)
                    ->indexBy('id')
                    ->all();
            }
        } else {
            $ownerId = null;
        }

        $blocks = [];
        $sortOrder = 0;
        $prevBlock = null;

        foreach ($value as $blockId => $blockData) {
            if (!isset($blockData['type']) || !isset($blockTypes[$blockData['type']])) {
                continue;
            }

            $blockType = $blockTypes[$blockData['type']];

            // Is this new? (Or has it been deleted?)
            if (strncmp($blockId, 'new',
                    3) === 0 || !isset($oldBlocksById[$blockId])
            ) {
                $block = new MatrixBlock();
                $block->fieldId = $this->id;
                $block->typeId = $blockType->id;
                $block->ownerId = $ownerId;
                $block->siteId = $element->siteId;

                // Preserve the collapsed state, which the browser can't remember on its own for new blocks
                $block->collapsed = !empty($blockData['collapsed']);
            } else {
                $block = $oldBlocksById[$blockId];
            }

            $block->setOwner($element);
            $block->enabled = (isset($blockData['enabled']) ? (bool)$blockData['enabled'] : true);

            // Set the content post location on the block if we can
            $ownerContentPostLocation = $element->getContentPostLocation();

            if ($ownerContentPostLocation) {
                $block->setContentPostLocation("{$ownerContentPostLocation}.{$this->handle}.{$blockId}.fields");
            }

            if (isset($blockData['fields'])) {
                $block->setFieldValuesFromPost($blockData['fields']);
            }

            $sortOrder++;
            $block->sortOrder = $sortOrder;

            // Set the prev/next blocks
            if ($prevBlock) {
                /** @var ElementInterface $prevBlock */
                $prevBlock->setNext($block);
                /** @var ElementInterface $block */
                $block->setPrev($prevBlock);
            }
            $prevBlock = $block;

            $blocks[] = $block;
        }

        return $blocks;
    }
}
