<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields;

use Craft;
use craft\base\EagerLoadingFieldInterface;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\FieldInterface;
use craft\base\GqlInlineFragmentFieldInterface;
use craft\base\GqlInlineFragmentInterface;
use craft\db\Query;
use craft\db\Table as TableName;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\elements\db\MatrixBlockQuery;
use craft\elements\MatrixBlock;
use craft\elements\MatrixBlock as MatrixBlockElement;
use craft\events\BlockTypesEvent;
use craft\gql\arguments\elements\MatrixBlock as MatrixBlockArguments;
use craft\gql\GqlEntityRegistry;
use craft\gql\resolvers\elements\MatrixBlock as MatrixBlockResolver;
use craft\gql\types\generators\MatrixBlockType as MatrixBlockTypeGenerator;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\ElementHelper;
use craft\helpers\Gql as GqlHelper;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\models\MatrixBlockType;
use craft\queue\jobs\ResaveElements;
use craft\services\Elements;
use craft\validators\ArrayValidator;
use craft\web\assets\matrix\MatrixAsset;
use craft\web\assets\matrixsettings\MatrixSettingsAsset;
use GraphQL\Type\Definition\Type;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\base\UnknownPropertyException;

/**
 * Matrix represents a Matrix field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Matrix extends Field implements EagerLoadingFieldInterface, GqlInlineFragmentFieldInterface
{
    // Constants
    // =========================================================================

    /**
     * @event SectionEvent The event that is triggered before a section is saved.
     */
    const EVENT_SET_FIELD_BLOCK_TYPES = 'setFieldBlockTypes';

    const PROPAGATION_METHOD_NONE = 'none';
    const PROPAGATION_METHOD_SITE_GROUP = 'siteGroup';
    const PROPAGATION_METHOD_LANGUAGE = 'language';
    const PROPAGATION_METHOD_ALL = 'all';

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

    /**
     * @inheritdoc
     */
    public static function valueType(): string
    {
        return MatrixBlockQuery::class;
    }

    // Properties
    // =========================================================================

    /**
     * @var int|null Min blocks
     */
    public $minBlocks;

    /**
     * @var int|null Max blocks
     */
    public $maxBlocks;

    /**
     * @var string Content table name
     * @since 3.0.23
     */
    public $contentTable;

    /**
     * @var string Propagation method
     *
     * This will be set to one of the following:
     *
     * - `none` – Only save b locks in the site they were created in
     * - `siteGroup` – Save  blocks to other sites in the same site group
     * - `language` – Save blocks to other sites with the same language
     * - `all` – Save blocks to all sites supported by the owner element
     *
     * @since 3.2
     */
    public $propagationMethod = self::PROPAGATION_METHOD_ALL;

    /**
     * @var int Whether each site should get its own unique set of blocks
     * @deprecated in 3.2. Use [[$propagationMethod]] instead
     */
    public $localizeBlocks = false;

    /**
     * @var MatrixBlockType[]|null The field’s block types
     */
    private $_blockTypes;

    /**
     * @var MatrixBlockType[]|null The block types' fields
     */
    private $_blockTypeFields;

    /**
     * @var string The old propagation method for this field
     */
    private $_oldPropagationMethod;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        if (array_key_exists('localizeBlocks', $config)) {
            $config['propagationMethod'] = $config['localizeBlocks'] ? 'none' : 'all';
            unset($config['localizeBlocks']);
        }

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        // todo: remove this in 4.0
        // Set localizeBlocks in case anything is still checking it
        $this->localizeBlocks = $this->propagationMethod === self::PROPAGATION_METHOD_NONE;

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function settingsAttributes(): array
    {
        return ArrayHelper::withoutValue(parent::settingsAttributes(), 'localizeBlocks');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();
        $rules[] = [
            ['propagationMethod'], 'in', 'range' => [
                self::PROPAGATION_METHOD_NONE,
                self::PROPAGATION_METHOD_SITE_GROUP,
                self::PROPAGATION_METHOD_LANGUAGE,
                self::PROPAGATION_METHOD_ALL
            ]
        ];
        $rules[] = [['minBlocks', 'maxBlocks'], 'integer', 'min' => 0];
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
     * Returns all of the block types' fields.
     *
     * @return FieldInterface[]
     */
    public function getBlockTypeFields(): array
    {
        if ($this->_blockTypeFields !== null) {
            return $this->_blockTypeFields;
        }

        if (empty($blockTypes = $this->getBlockTypes())) {
            return $this->_blockTypeFields = [];
        }

        // Get the fields & layout IDs
        $contexts = [];
        $layoutIds = [];
        foreach ($blockTypes as $blockType) {
            $contexts[] = 'matrixBlockType:' . $blockType->uid;
            $layoutIds[] = $blockType->fieldLayoutId;
        }

        /** @var Field[] $fieldsById */
        $fieldsById = ArrayHelper::index(Craft::$app->getFields()->getAllFields($contexts), 'id');

        // Get all the field IDs grouped by layout ID
        $fieldIdsByLayoutId = Craft::$app->getFields()->getFieldIdsByLayoutIds($layoutIds);

        // Assemble the fields
        $this->_blockTypeFields = [];

        foreach ($blockTypes as $blockType) {
            if (isset($fieldIdsByLayoutId[$blockType->fieldLayoutId])) {
                $fieldColumnPrefix = 'field_' . $blockType->handle . '_';

                foreach ($fieldIdsByLayoutId[$blockType->fieldLayoutId] as $fieldId) {
                    if (isset($fieldsById[$fieldId])) {
                        $fieldsById[$fieldId]->columnPrefix = $fieldColumnPrefix;
                        $this->_blockTypeFields[] = $fieldsById[$fieldId];
                    }
                }
            }
        }

        return $this->_blockTypeFields;
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
            'searchable' => true,
            'translationMethod' => Field::TRANSLATION_METHOD_NONE,
            'translationKeyFormat' => null,
            'typesettings' => null,
        ];

        foreach ($blockTypes as $key => $config) {
            if ($config instanceof MatrixBlockType) {
                $this->_blockTypes[] = $config;
            } else {
                $blockType = new MatrixBlockType();
                $blockType->fieldId = $this->id;
                $blockType->name = $config['name'];
                $blockType->handle = $config['handle'];

                // Existing block type?
                if (is_numeric($key)) {
                    $info = (new Query())
                        ->select(['uid', 'fieldLayoutId'])
                        ->from([TableName::MATRIXBLOCKTYPES])
                        ->where(['id' => $key])
                        ->one();

                    if ($info) {
                        $blockType->id = $key;
                        $blockType->uid = $info['uid'];
                        $blockType->fieldLayoutId = $info['fieldLayoutId'];
                    }
                }

                $fields = [];

                if (!empty($config['fields'])) {
                    foreach ($config['fields'] as $fieldId => $fieldConfig) {
                        /** @noinspection SlowArrayOperationsInLoopInspection */
                        $fieldConfig = array_merge($defaultFieldConfig, $fieldConfig);

                        $fields[] = Craft::$app->getFields()->createField([
                            'type' => $fieldConfig['type'],
                            'id' => is_numeric($fieldId) ? $fieldId : null,
                            'name' => $fieldConfig['name'],
                            'handle' => $fieldConfig['handle'],
                            'instructions' => $fieldConfig['instructions'],
                            'required' => (bool)$fieldConfig['required'],
                            'searchable' => (bool)$fieldConfig['searchable'],
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
            'new Craft.MatrixConfigurator(' .
            Json::encode($fieldTypeInfo, JSON_UNESCAPED_UNICODE) . ', ' .
            Json::encode(Craft::$app->getView()->getNamespace(), JSON_UNESCAPED_UNICODE) .
            ');'
        );

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

        $fieldsService = Craft::$app->getFields();
        /** @var string[]|FieldInterface[] $allFieldTypes */
        $allFieldTypes = $fieldsService->getAllFieldTypes();
        $fieldTypeOptions = [];

        foreach ($allFieldTypes as $class) {
            // No Matrix-Inception, sorry buddy.
            $enabled = $class !== self::class;
            $fieldTypeOptions['new'][] = [
                'value' => $class,
                'label' => $class::displayName(),
                'disabled' => !$enabled,
            ];
        }

        // Sort them by name
        ArrayHelper::multisort($fieldTypeOptions['new'], 'label');

        if (!$this->getIsNew()) {
            foreach ($this->getBlockTypes() as $blockType) {
                foreach ($blockType->getFields() as $field) {
                    /** @var Field $field */
                    if (!$field->getIsNew()) {
                        $fieldTypeOptions[$field->id] = [];
                        $compatibleFieldTypes = $fieldsService->getCompatibleFieldTypes($field, true);
                        foreach ($allFieldTypes as $class) {
                            // No Matrix-Inception, sorry buddy.
                            if ($class !== self::class && ($class === get_class($field) || $class::isSelectable())) {
                                $compatible = in_array($class, $compatibleFieldTypes, true);
                                $fieldTypeOptions[$field->id][] = [
                                    'value' => $class,
                                    'label' => $class::displayName() . ($compatible ? '' : ' ⚠️'),
                                ];
                            }
                        }

                        // Sort them by name
                        ArrayHelper::multisort($fieldTypeOptions[$field->id], 'label');
                    }
                }
            }
        }

        $blockTypes = [];
        $blockTypeFields = [];
        $totalNewBlockTypes = 0;

        foreach ($this->getBlockTypes() as $blockType) {
            $blockTypeId = (string)($blockType->id ?? 'new' . ++$totalNewBlockTypes);
            $blockTypes[$blockTypeId] = $blockType;

            $blockTypeFields[$blockTypeId] = [];
            $totalNewFields = 0;
            foreach ($blockType->getFields() as $field) {
                $fieldId = (string)($field->id ?? 'new' . ++$totalNewFields);
                $blockTypeFields[$blockTypeId][$fieldId] = $field;
            }
        }

        return Craft::$app->getView()->renderTemplate('_components/fieldtypes/Matrix/settings',
            [
                'matrixField' => $this,
                'fieldTypes' => $fieldTypeOptions,
                'blockTypes' => $blockTypes,
                'blockTypeFields' => $blockTypeFields,
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
        $new = 0;

        foreach ($value->all() as $block) {
            $blockId = $block->id ?? 'new' . ++$new;
            $serialized[$blockId] = [
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
            $ns = $this->handle . '_' . StringHelper::randomString(5);
            $condition = ['exists', (new Query())
                ->from(TableName::MATRIXBLOCKS . " matrixblocks_$ns")
                ->innerJoin(TableName::ELEMENTS . " elements_$ns", "[[elements_$ns.id]] = [[matrixblocks_$ns.id]]")
                ->where("[[matrixblocks_$ns.ownerId]] = [[elements.id]]")
                ->andWhere([
                    "matrixblocks_$ns.fieldId" => $this->id,
                    "elements_$ns.dateDeleted" => null,
                ])
            ];

            if ($value === ':notempty:') {
                $query->subQuery->andWhere($condition);
            } else {
                $query->subQuery->andWhere(['not', $condition]);
            }
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
        return $this->propagationMethod !== self::PROPAGATION_METHOD_ALL;
    }

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    public function getInputHtml($value, ElementInterface $element = null): string
    {
        /** @var Element $element */
        if ($element !== null && $element->hasEagerLoadedElements($this->handle)) {
            $value = $element->getEagerLoadedElements($this->handle);
        }

        if ($value instanceof MatrixBlockQuery) {
            $value = $value->getCachedResult() ?? $value->limit(null)->anyStatus()->all();
        }

        $id = Craft::$app->getView()->formatInputId($this->handle);

        // Let plugins/modules override which block types should be available for this field
        $event = new BlockTypesEvent([
            'blockTypes' => $this->getBlockTypes(),
            'element' => $element,
            'value' => $value,
        ]);
        $this->trigger(self::EVENT_SET_FIELD_BLOCK_TYPES, $event);
        $blockTypes = array_values($event->blockTypes);

        if (empty($blockTypes)) {
            throw new InvalidConfigException('At least one block type is required.');
        }

        // Get the block types data
        $blockTypeInfo = $this->_getBlockTypeInfoForInput($element, $blockTypes);
        $createDefaultBlocks = $this->minBlocks != 0 && count($blockTypeInfo) === 1;
        $staticBlocks = (
            $createDefaultBlocks &&
            $this->minBlocks == $this->maxBlocks &&
            $this->maxBlocks >= count($value)
        );

        Craft::$app->getView()->registerAssetBundle(MatrixAsset::class);

        Craft::$app->getView()->registerJs('new Craft.MatrixInput(' .
            '"' . Craft::$app->getView()->namespaceInputId($id) . '", ' .
            Json::encode($blockTypeInfo, JSON_UNESCAPED_UNICODE) . ', ' .
            '"' . Craft::$app->getView()->namespaceInputName($this->handle) . '", ' .
            ($this->maxBlocks ?: 'null') .
            ');');

        // Safe to set the default blocks?
        if ($createDefaultBlocks) {
            $blockType = $blockTypes[0];

            for ($i = count($value); $i < $this->minBlocks; $i++) {
                $block = new MatrixBlock();
                $block->fieldId = $this->id;
                $block->typeId = $blockType->id;
                $block->siteId = $element->siteId ?? Craft::$app->getSites()->getCurrentSite()->id;
                $value[] = $block;
            }
        }

        return Craft::$app->getView()->renderTemplate('_components/fieldtypes/Matrix/input',
            [
                'id' => $id,
                'name' => $this->handle,
                'blockTypes' => $blockTypes,
                'blocks' => $value,
                'static' => false,
                'staticBlocks' => $staticBlocks,
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
                'min' => $this->minBlocks ?: null,
                'max' => $this->maxBlocks ?: null,
                'tooFew' => Craft::t('app', '{attribute} should contain at least {min, number} {min, plural, one{block} other{blocks}}.'),
                'tooMany' => Craft::t('app', '{attribute} should contain at most {max, number} {max, plural, one{block} other{blocks}}.'),
                'skipOnEmpty' => false,
                'on' => Element::SCENARIO_LIVE,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function isValueEmpty($value, ElementInterface $element): bool
    {
        /** @var MatrixBlockQuery $value */
        return $value->count() === 0;
    }

    /**
     * Validates an owner element’s Matrix blocks.
     *
     * @param ElementInterface $element
     */
    public function validateBlocks(ElementInterface $element)
    {
        /** @var Element $element */
        /** @var MatrixBlockQuery $value */
        $value = $element->getFieldValue($this->handle);

        foreach ($value->all() as $i => $block) {
            /** @var MatrixBlock $block */
            if ($block->enabled && $element->getScenario() === Element::SCENARIO_LIVE) {
                $block->setScenario(Element::SCENARIO_LIVE);
            }

            if (!$block->validate()) {
                $element->addModelErrors($block, "{$this->handle}[{$i}]");
            }
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
            $fields = Craft::$app->getFields()->getAllFields($block->getFieldContext());
            foreach ($fields as $field) {
                /** @var Field $field */
                if ($field->searchable) {
                    $fieldValue = $block->getFieldValue($field->handle);
                    $keywords[] = $field->getSearchKeywords($fieldValue, $element);
                }
            }
        }

        return parent::getSearchKeywords($keywords, $element);
    }

    /**
     * @inheritdoc
     */
    public function getStaticHtml($value, ElementInterface $element): string
    {
        /** @var MatrixBlockQuery $value */
        $value = $value->all();

        /** @var MatrixBlock[] $value */
        if (empty($value)) {
            return '<p class="light">' . Craft::t('app', 'No blocks.') . '</p>';
        }

        $id = StringHelper::randomString();

        return Craft::$app->getView()->renderTemplate('_components/fieldtypes/Matrix/input', [
            'id' => $id,
            'name' => $id,
            'blockTypes' => $this->getBlockTypes(),
            'blocks' => $value,
            'static' => true,
            'staticBlocks' => true,
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
            ->from([TableName::MATRIXBLOCKS])
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

    /**
     * @inheritdoc
     * @since 3.3.0
     */
    public function getContentGqlType()
    {
        $typeArray = MatrixBlockTypeGenerator::generateTypes($this);
        $typeName = $this->handle . '_MatrixField';
        $resolver = function (MatrixBlockElement $value) {
            return GqlEntityRegistry::getEntity($value->getGqlTypeName());
        };

        return [
            'name' => $this->handle,
            'type' => Type::listOf(GqlHelper::getUnionType($typeName, $typeArray, $resolver)),
            'args' => MatrixBlockArguments::getArguments(),
            'resolve' => MatrixBlockResolver::class . '::resolve',
        ];
    }

    /**
     * @inheritdoc
     * @throws InvalidArgumentException
     * @since 3.3.0
     */
    public function getGqlFragmentEntityByName(string $fragmentName): GqlInlineFragmentInterface
    {
        $blockTypeHandle = StringHelper::removeLeft(StringHelper::removeRight($fragmentName, '_BlockType'), $this->handle . '_');

        $blockType = ArrayHelper::firstWhere($this->getBlockTypes(), 'handle', $blockTypeHandle);

        if (!$blockType) {
            throw new InvalidArgumentException('Invalid fragment name: ' . $fragmentName);
        }

        return $blockType;
    }

    // Events
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    public function beforeSave(bool $isNew): bool
    {
        if (!parent::beforeSave($isNew)) {
            return false;
        }

        // Prep the block types & fields for save
        $fieldsService = Craft::$app->getFields();
        foreach ($this->getBlockTypes() as $blockType) {
            // Ensure the block type has a UID
            if ($blockType->getIsNew()) {
                $blockType->uid = StringHelper::UUID();
            } else if (!$blockType->uid) {
                $blockType->uid = Db::uidById(TableName::MATRIXBLOCKTYPES, $blockType->id);
            }

            foreach ($blockType->getFields() as $field) {
                /** @var Field $field */
                // Hack to allow blank field names
                if (!$field->name) {
                    $field->name = '__blank__';
                }

                $field->context = 'matrixBlockType:' . $blockType->uid;
                $fieldsService->prepFieldForSave($field);

                if (!$field->beforeSave($field->getIsNew())) {
                    return false;
                }
            }
        }

        // Set the content table name and remember the original propagation method
        if ($this->id) {
            $oldField = $fieldsService->getFieldById($this->id);
            if ($oldField instanceof self) {
                $this->contentTable = $oldField->contentTable;
                $this->_oldPropagationMethod = $oldField->propagationMethod;
            }
        }

        $this->contentTable = Craft::$app->getMatrix()->defineContentTableName($this);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function afterSave(bool $isNew)
    {
        Craft::$app->getMatrix()->saveSettings($this, false);

        // If the propagation method just changed, resave all the Matrix blocks
        if ($this->_oldPropagationMethod && $this->propagationMethod !== $this->_oldPropagationMethod) {
            Craft::$app->getQueue()->push(new ResaveElements([
                'elementType' => MatrixBlock::class,
                'criteria' => [
                    'fieldId' => $this->id,
                    'siteId' => '*',
                    'unique' => true,
                    'status' => null,
                    'enabledForSite' => false,
                ]
            ]));
            $this->_oldPropagationMethod = null;
        }

        parent::afterSave($isNew);
    }

    /**
     * @inheritdoc
     */
    public function beforeApplyDelete()
    {
        Craft::$app->getMatrix()->deleteMatrixField($this);
        parent::beforeApplyDelete();
    }

    /**
     * @inheritdoc
     */
    public function afterElementPropagate(ElementInterface $element, bool $isNew)
    {
        $matrixService = Craft::$app->getMatrix();

        /** @var Element $element */
        if ($element->duplicateOf !== null) {
            $matrixService->duplicateBlocks($this, $element->duplicateOf, $element, true);
        } else {
            $matrixService->saveField($this, $element);
        }

        // Reset the field value if this is a new element
        if ($element->duplicateOf || $isNew) {
            $element->setFieldValue($this->handle, null);
        }

        parent::afterElementPropagate($element, $isNew);
    }

    /**
     * @inheritdoc
     */
    public function beforeElementDelete(ElementInterface $element): bool
    {
        if (!parent::beforeElementDelete($element)) {
            return false;
        }

        /** @var Element $element */
        // Delete any Matrix blocks that belong to this element(s)
        foreach (Craft::$app->getSites()->getAllSiteIds() as $siteId) {
            $matrixBlocksQuery = MatrixBlock::find();
            $matrixBlocksQuery->anyStatus();
            $matrixBlocksQuery->siteId($siteId);
            $matrixBlocksQuery->ownerId($element->id);

            /** @var MatrixBlock[] $matrixBlocks */
            $matrixBlocks = $matrixBlocksQuery->all();
            $elementsService = Craft::$app->getElements();

            foreach ($matrixBlocks as $matrixBlock) {
                $matrixBlock->deletedWithOwner = true;
                $elementsService->deleteElement($matrixBlock, $element->hardDelete);
            }
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function afterElementRestore(ElementInterface $element)
    {
        /** @var Element $element */
        // Also restore any Matrix blocks for this element
        $elementsService = Craft::$app->getElements();
        foreach (ElementHelper::supportedSitesForElement($element) as $siteInfo) {
            $blocks = MatrixBlock::find()
                ->anyStatus()
                ->siteId($siteInfo['siteId'])
                ->ownerId($element->id)
                ->trashed()
                ->andWhere(['matrixblocks.deletedWithOwner' => true])
                ->all();

            foreach ($blocks as $block) {
                $elementsService->restoreElement($block);
            }
        }

        parent::afterElementRestore($element);
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

        // Sort them by name
        ArrayHelper::multisort($fieldTypes, 'name');

        Craft::$app->getView()->setNamespace($originalNamespace);

        return $fieldTypes;
    }

    /**
     * Returns info about each block type and their field types for the Matrix field input.
     *
     * @param ElementInterface|null $element
     * @param MatrixBlockType[] $blockTypes
     * @return array
     */
    private function _getBlockTypeInfoForInput(ElementInterface $element = null, array $blockTypes): array
    {
        /** @var Element $element */
        $blockTypeInfo = [];

        // Set a temporary namespace for these
        $originalNamespace = Craft::$app->getView()->getNamespace();
        $namespace = Craft::$app->getView()->namespaceInputName($this->handle . '[__BLOCK__][fields]', $originalNamespace);
        Craft::$app->getView()->setNamespace($namespace);

        foreach ($blockTypes as $blockType) {
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
                    'fields' => $fieldLayoutFields,
                    'element' => $block,
                ]));

            // Reset $_isFresh's
            foreach ($fieldLayoutFields as $field) {
                $field->setIsFresh(null);
            }

            $footHtml = Craft::$app->getView()->clearJsBuffer();

            $blockTypeInfo[] = [
                'handle' => $blockType->handle,
                'name' => Craft::t('site', $blockType->name),
                'bodyHtml' => $bodyHtml,
                'footHtml' => $footHtml,
            ];
        }

        Craft::$app->getView()->setNamespace($originalNamespace);

        return $blockTypeInfo;
    }

    /**
     * Creates an array of blocks based on the given serialized data.
     *
     * @param array|string $value The raw field value
     * @param ElementInterface|null $element The element the field is associated with, if there is one
     * @return MatrixBlock[]
     */
    private function _createBlocksFromSerializedData($value, ElementInterface $element = null): array
    {
        if (!is_array($value)) {
            return [];
        }

        /** @var Element $element */
        // Get the possible block types for this field
        /** @var MatrixBlockType[] $blockTypes */
        $blockTypes = ArrayHelper::index(Craft::$app->getMatrix()->getBlockTypesByFieldId($this->id), 'handle');

        $oldBlocksById = [];

        // Get the old blocks that are still around
        if ($element && $element->id) {
            $ownerId = $element->id;

            $ids = [];

            foreach (array_keys($value) as $blockId) {
                if (is_numeric($blockId) && $blockId != 0) {
                    $ids[] = $blockId;

                    // If that block was duplicated earlier in this request, check for that as well.
                    if (isset(Elements::$duplicatedElementIds[$blockId])) {
                        $ids[] = Elements::$duplicatedElementIds[$blockId];
                    }
                }
            }

            if (!empty($ids)) {
                $oldBlocksQuery = MatrixBlock::find();
                $oldBlocksQuery->fieldId($this->id);
                $oldBlocksQuery->ownerId($ownerId);
                $oldBlocksQuery->id($ids);
                $oldBlocksQuery->anyStatus();
                $oldBlocksQuery->siteId($element->siteId);
                $oldBlocksQuery->indexBy('id');
                $oldBlocksById = $oldBlocksQuery->all();
            }
        } else {
            $ownerId = null;
        }

        // Should we ignore disabled blocks?
        $request = Craft::$app->getRequest();
        $hideDisabledBlocks = !$request->getIsConsoleRequest() && (
                $request->getToken() !== null ||
                $request->getIsLivePreview()
            );

        $blocks = [];
        $sortOrder = 0;
        $prevBlock = null;

        foreach ($value as $blockId => $blockData) {
            if (!isset($blockData['type']) || !isset($blockTypes[$blockData['type']])) {
                continue;
            }

            // Skip disabled blocks on Live Preview requests
            if ($hideDisabledBlocks && empty($blockData['enabled'])) {
                continue;
            }

            $blockType = $blockTypes[$blockData['type']];

            // If this is a preexisting block but we don't have a record of it,
            // check to see if it was recently duplicated.
            if (
                strpos($blockId, 'new') !== 0 &&
                !isset($oldBlocksById[$blockId]) &&
                isset(Elements::$duplicatedElementIds[$blockId])
            ) {
                $blockId = Elements::$duplicatedElementIds[$blockId];
            }

            // Is this new? (Or has it been deleted?)
            if (strpos($blockId, 'new') === 0 || !isset($oldBlocksById[$blockId])) {
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
            $fieldNamespace = $element->getFieldParamNamespace();

            if ($fieldNamespace !== null) {
                $blockFieldNamespace = ($fieldNamespace ? $fieldNamespace . '.' : '') . $this->handle . '.' . $blockId . '.fields';
                $block->setFieldParamNamespace($blockFieldNamespace);
            }

            if (isset($blockData['fields'])) {
                foreach ($blockData['fields'] as $fieldHandle => $fieldValue) {
                    try {
                        $block->setFieldValue($fieldHandle, $fieldValue);
                    } catch (UnknownPropertyException $e) {
                        // the field was probably deleted
                    }
                }
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
