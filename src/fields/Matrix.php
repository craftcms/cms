<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\fields;

use Craft;
use craft\base\EagerLoadingFieldInterface;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\FieldInterface;
use craft\db\Query;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\elements\db\MatrixBlockQuery;
use craft\elements\MatrixBlock;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\models\MatrixBlockType;
use craft\validators\ArrayValidator;
use craft\web\assets\matrix\MatrixAsset;
use craft\web\assets\matrixsettings\MatrixSettingsAsset;

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
    public static function displayName(): string
    {
        return Craft::t('app', 'Matrix');
    }

    /**
     * @inheritdoc
     */
    public static function supportedTranslationMethods(): array
    {
        // Don't ever automatically propagate values to other sites.
        return [
            self::TRANSLATION_METHOD_SITE,
        ];
    }

    // Properties
    // =========================================================================

    /**
     * @var int|null Max blocks
     */
    public $maxBlocks;

    /**
     * @var int Whether each site should get its own unique set of blocks
     */
    public $localizeBlocks = false;

    /**
     * @var MatrixBlockType[]|null The field’s block types
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
    public function getBlockTypes(): array
    {
        if ($this->_blockTypes !== null) {
            return $this->_blockTypes;
        }

        if ($this->getIsNew()) {
            return [];
        }

        return $this->_blockTypes = Craft::$app->getMatrix()->getBlockTypesByFieldId($this->id);
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
                $blockType->id = (int)$key;
                $blockType->fieldId = $this->id;
                $blockType->name = $config['name'];
                $blockType->handle = $config['handle'];

                $fields = [];

                if (!empty($config['fields'])) {
                    foreach ($config['fields'] as $fieldId => $fieldConfig) {
                        /** @noinspection SlowArrayOperationsInLoopInspection */
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
    public function validate($attributeNames = null, $clearErrors = true): bool
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
    public static function hasContentColumn(): bool
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

        $view = Craft::$app->getView();

        $view->registerAssetBundle(MatrixSettingsAsset::class);
        $view->registerJs(
            'new Craft.MatrixConfigurator('.
            Json::encode($fieldTypeInfo, JSON_UNESCAPED_UNICODE).', '.
            Json::encode(Craft::$app->getView()->getNamespace(), JSON_UNESCAPED_UNICODE).
            ');'
        );

        $view->registerTranslations('app', [
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

        $fieldsService = Craft::$app->getFields();
        $fieldTypeOptions = [];

        foreach ($fieldsService->getAllFieldTypes() as $class) {
            // No Matrix-Inception, sorry buddy.
            /** @var Field|string $class */
            if ($class !== self::class) {
                $fieldTypeOptions['new'][] = [
                    'value' => $class,
                    'label' => $class::displayName()
                ];
            }
        }

        // Sort them by name
        ArrayHelper::multisort($fieldTypeOptions['new'], 'label');

        if (!$this->getIsNew()) {
            foreach ($this->getBlockTypes() as $blockType) {
                foreach ($blockType->getFields() as $field) {
                    /** @var Field $field */
                    if (!$field->getIsNew()) {
                        $fieldTypeOptions[$field->id] = [];
                        foreach ($fieldsService->getCompatibleFieldTypes($field, true) as $class) {
                            // No Matrix-Inception, sorry buddy.
                            /** @var Field|string $class */
                            if ($class !== self::class) {
                                $fieldTypeOptions[$field->id][] = [
                                    'value' => $class,
                                    'label' => $class::displayName()
                                ];
                            }
                        }

                        // Sort them by name
                        ArrayHelper::multisort($fieldTypeOptions[$field->id], 'label');
                    }
                }
            }
        }

        // Look for any missing fields and convert to Plain Text
        foreach ($this->getBlockTypes() as $blockType) {
            /** @var Field[] $blockTypeFields */
            $blockTypeFields = $blockType->getFields();

            foreach ($blockTypeFields as $i => $field) {
                if ($field instanceof MissingField) {
                    $blockTypeFields[$i] = $field->createFallback(PlainText::class);
                    $blockTypeFields[$i]->addError('type', Craft::t('app', 'The field type “{type}” could not be found.', [
                        'type' => $field->expectedType
                    ]));
                    $blockType->hasFieldErrors = true;
                }
            }

            $blockType->setFields($blockTypeFields);
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
    public function normalizeValue($value, ElementInterface $element = null)
    {
        if ($value instanceof ElementQueryInterface) {
            return $value;
        }

        /** @var Element|null $element */
        $query = MatrixBlock::find();

        // Existing element?
        if ($element && $element->id) {
            $query->ownerId($element->id);
        } else {
            $query->id(false);
        }

        $query
            ->fieldId($this->id)
            ->siteId($element->siteId ?? null);

        // Set the initially matched elements if $value is already set, which is the case if there was a validation
        // error or we're loading an entry revision.
        if (is_array($value) || $value === '') {
            $query->status = null;
            $query->enabledForSite = false;
            $query->limit = null;
            $query->setCachedResult($this->_createBlocksFromSerializedData($value, $element));
        }

        return $query;
    }

    /**
     * @inheritdoc
     */
    public function serializeValue($value, ElementInterface $element = null)
    {
        /** @var MatrixBlockQuery $value */
        $serialized = [];

        foreach ($value->all() as $block) {
            $serialized[$block->id] = [
                'type' => $block->getType()->handle,
                'enabled' => $block->enabled,
                'collapsed' => $block->collapsed,
                'fields' => $block->getSerializedFieldValues(),
            ];
        }

        return $serialized;
    }

    /**
     * @inheritdoc
     */
    public function modifyElementsQuery(ElementQueryInterface $query, $value)
    {
        /** @var ElementQuery $query */
        if ($value === 'not :empty:') {
            $value = ':notempty:';
        }

        if ($value === ':notempty:' || $value === ':empty:') {
            $alias = 'matrixblocks_'.$this->handle;
            $operator = ($value === ':notempty:' ? '!=' : '=');

            $query->subQuery->andWhere(
                "(select count([[{$alias}.id]]) from {{%matrixblocks}} {{{$alias}}} where [[{$alias}.ownerId]] = [[elements.id]] and [[{$alias}.fieldId]] = :fieldId) {$operator} 0",
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
    public function getIsTranslatable(ElementInterface $element = null): bool
    {
        return $this->localizeBlocks;
    }

    /**
     * @inheritdoc
     */
    public function getInputHtml($value, ElementInterface $element = null): string
    {
        $id = Craft::$app->getView()->formatInputId($this->handle);

        // Get the block types data
        $blockTypeInfo = $this->_getBlockTypeInfoForInput($element);

        Craft::$app->getView()->registerAssetBundle(MatrixAsset::class);

        Craft::$app->getView()->registerJs('new Craft.MatrixInput('.
            '"'.Craft::$app->getView()->namespaceInputId($id).'", '.
            Json::encode($blockTypeInfo, JSON_UNESCAPED_UNICODE).', '.
            '"'.Craft::$app->getView()->namespaceInputName($this->handle).'", '.
            ($this->maxBlocks ?: 'null').
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

        /** @var Element $element */
        if ($element !== null && $element->hasEagerLoadedElements($this->handle)) {
            $value = $element->getEagerLoadedElements($this->handle);
        }

        if ($value instanceof MatrixBlockQuery) {
            $value = $value
                ->limit(null)
                ->status(null)
                ->enabledForSite(false)
                ->all();
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
    public function getElementValidationRules(): array
    {
        return [
            'validateBlocks',
            [
                ArrayValidator::class,
                'max' => $this->maxBlocks ?: null,
                'tooMany' => Craft::t('app', '{attribute} should contain at most {max, number} {max, plural, one{block} other{blocks}}.'),
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function isEmpty($value): bool
    {
        /** @var MatrixBlockQuery $value */
        return $value->count() === 0;
    }

    /**
     * Validates an owner element’s Matrix blocks.
     *
     * @param ElementInterface $element
     *
     * @return void
     */
    public function validateBlocks(ElementInterface $element)
    {
        /** @var Element $element */
        /** @var MatrixBlockQuery $value */
        $value = $element->getFieldValue($this->handle);
        $blocksValidate = true;

        foreach ($value->all() as $block) {
            /** @var MatrixBlock $block */
            if (!$block->validate()) {
                $blocksValidate = false;
            }
        }

        if (!$blocksValidate) {
            $element->addError($this->handle, Craft::t('app', 'Correct the errors listed above.'));
        }
    }

    /**
     * @inheritdoc
     */
    public function getSearchKeywords($value, ElementInterface $element): string
    {
        /** @var MatrixBlockQuery $value */
        /** @var MatrixBlock $block */
        $keywords = [];
        $contentService = Craft::$app->getContent();

        foreach ($value->all() as $block) {
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
    public function getStaticHtml($value, ElementInterface $element): string
    {
        $value = $value->all();

        if (empty($value)) {
            return '<p class="light">'.Craft::t('app', 'No blocks.').'</p>';
        }

        $id = StringHelper::randomString();

        return Craft::$app->getView()->renderTemplate('_components/fieldtypes/Matrix/input', [
            'id' => $id,
            'name' => $id,
            'blockTypes' => $this->getBlockTypes(),
            'blocks' => $value,
            'static' => true
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getEagerLoadingMap(array $sourceElements)
    {
        // Get the source element IDs
        $sourceElementIds = [];

        foreach ($sourceElements as $sourceElement) {
            $sourceElementIds[] = $sourceElement->id;
        }

        // Return any relation data on these elements, defined with this field
        $map = (new Query())
            ->select(['ownerId as source', 'id as target'])
            ->from(['{{%matrixblocks}}'])
            ->where([
                'fieldId' => $this->id,
                'ownerId' => $sourceElementIds,
            ])
            ->orderBy(['sortOrder' => SORT_ASC])
            ->all();

        return [
            'elementType' => MatrixBlock::class,
            'map' => $map,
            'criteria' => ['fieldId' => $this->id]
        ];
    }

    // Events
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    public function afterSave(bool $isNew)
    {
        Craft::$app->getMatrix()->saveSettings($this, false);
        parent::afterSave($isNew);
    }

    /**
     * @inheritdoc
     */
    public function beforeDelete(): bool
    {
        Craft::$app->getMatrix()->deleteMatrixField($this);

        return parent::beforeDelete();
    }

    /**
     * @inheritdoc
     */
    public function afterElementSave(ElementInterface $element, bool $isNew)
    {
        Craft::$app->getMatrix()->saveField($this, $element);
        parent::afterElementSave($element, $isNew);
    }

    /**
     * @inheritdoc
     */
    public function beforeElementDelete(ElementInterface $element): bool
    {
        // Delete any Matrix blocks that belong to this element(s)
        foreach (Craft::$app->getSites()->getAllSiteIds() as $siteId) {
            $matrixBlocksQuery = MatrixBlock::find();
            $matrixBlocksQuery->status(null);
            $matrixBlocksQuery->enabledForSite(false);
            $matrixBlocksQuery->siteId($siteId);
            $matrixBlocksQuery->owner($element);

            /** @var MatrixBlock[] $matrixBlocks */
            $matrixBlocks = $matrixBlocksQuery->all();

            foreach ($matrixBlocks as $matrixBlock) {
                Craft::$app->getElements()->deleteElement($matrixBlock);
            }
        }

        return parent::beforeElementDelete($element);
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns info about each field type for the configurator.
     *
     * @return array
     */
    private function _getFieldOptionsForConfigurator(): array
    {
        $fieldTypes = [];

        // Set a temporary namespace for these
        $originalNamespace = Craft::$app->getView()->getNamespace();
        $namespace = Craft::$app->getView()->namespaceInputName('blockTypes[__BLOCK_TYPE__][fields][__FIELD__][typesettings]', $originalNamespace);
        Craft::$app->getView()->setNamespace($namespace);

        foreach (Craft::$app->getFields()->getAllFieldTypes() as $class) {
            /** @var Field|string $class */
            // No Matrix-Inception, sorry buddy.
            if ($class === self::class) {
                continue;
            }

            Craft::$app->getView()->startJsBuffer();
            /** @var FieldInterface $field */
            $field = new $class();
            $settingsBodyHtml = Craft::$app->getView()->namespaceInputs((string)$field->getSettingsHtml());
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
     * @param ElementInterface|null $element
     *
     * @return array
     */
    private function _getBlockTypeInfoForInput(ElementInterface $element = null): array
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
            $block->fieldLayoutId = $blockType->fieldLayoutId;

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
                    'fields' => $fieldLayoutFields,
                    'element' => $block,
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
     * Creates an array of blocks based on the given serialized data.
     *
     * @param array|string          $value   The raw field value
     * @param ElementInterface|null $element The element the field is associated with, if there is one
     *
     * @return MatrixBlock[]
     */
    private function _createBlocksFromSerializedData($value, ElementInterface $element = null): array
    {
        /** @var Element $element */
        // Get the possible block types for this field
        /** @var MatrixBlockType[] $blockTypes */
        $blockTypes = ArrayHelper::index(Craft::$app->getMatrix()->getBlockTypesByFieldId($this->id), 'handle');

        if (!is_array($value)) {
            return [];
        }

        $oldBlocksById = [];

        // Get the old blocks that are still around
        if ($element && $element->id) {
            $ownerId = $element->id;

            $ids = [];

            foreach (array_keys($value) as $blockId) {
                if (is_numeric($blockId) && $blockId != 0) {
                    $ids[] = $blockId;
                }
            }

            if (!empty($ids)) {
                $oldBlocksQuery = MatrixBlock::find();
                $oldBlocksQuery->fieldId($this->id);
                $oldBlocksQuery->ownerId($ownerId);
                $oldBlocksQuery->id($ids);
                $oldBlocksQuery->limit(null);
                $oldBlocksQuery->status(null);
                $oldBlocksQuery->enabledForSite(false);
                $oldBlocksQuery->siteId($element->siteId);
                $oldBlocksQuery->indexBy('id');
                $oldBlocksById = $oldBlocksQuery->all();
            }
        } else {
            $ownerId = null;
        }

        $isLivePreview = Craft::$app->getRequest()->getIsLivePreview();
        $blocks = [];
        $sortOrder = 0;
        $prevBlock = null;

        foreach ($value as $blockId => $blockData) {
            if (!isset($blockData['type']) || !isset($blockTypes[$blockData['type']])) {
                continue;
            }

            // Skip disabled blocks on Live Preview requests
            if ($isLivePreview && empty($blockData['enabled'])) {
                continue;
            }

            $blockType = $blockTypes[$blockData['type']];

            // Is this new? (Or has it been deleted?)
            if (strpos($blockId, 'new') === 0 || !isset($oldBlocksById[$blockId])) {
                $block = new MatrixBlock();
                $block->fieldId = $this->id;
                $block->typeId = $blockType->id;
                $block->fieldLayoutId = $blockType->fieldLayoutId;
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
            $fieldNamespace = $element->getFieldParamNamespace();

            if ($fieldNamespace !== null) {
                $blockFieldNamespace = ($fieldNamespace ? $fieldNamespace.'.' : '').'.'.$this->handle.'.'.$blockId.'.fields';
                $block->setFieldParamNamespace($blockFieldNamespace);
            }

            if (isset($blockData['fields'])) {
                $block->setFieldValues($blockData['fields']);
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
