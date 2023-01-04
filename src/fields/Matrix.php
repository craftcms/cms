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
use craft\db\Table as DbTable;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\elements\db\MatrixBlockQuery;
use craft\elements\ElementCollection;
use craft\elements\MatrixBlock;
use craft\events\BlockTypesEvent;
use craft\fieldlayoutelements\CustomField;
use craft\fields\conditions\EmptyFieldConditionRule;
use craft\gql\arguments\elements\MatrixBlock as MatrixBlockArguments;
use craft\gql\resolvers\elements\MatrixBlock as MatrixBlockResolver;
use craft\gql\types\generators\MatrixBlockType as MatrixBlockTypeGenerator;
use craft\gql\types\input\Matrix as MatrixInputType;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\ElementHelper;
use craft\helpers\Gql;
use craft\helpers\Json;
use craft\helpers\Queue;
use craft\helpers\StringHelper;
use craft\i18n\Locale;
use craft\i18n\Translation;
use craft\models\FieldLayoutTab;
use craft\models\MatrixBlockType;
use craft\queue\jobs\ApplyNewPropagationMethod;
use craft\services\Elements;
use craft\validators\ArrayValidator;
use craft\web\assets\matrix\MatrixAsset;
use craft\web\assets\matrixsettings\MatrixSettingsAsset;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Collection;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\db\Expression;

/**
 * Matrix represents a Matrix field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Matrix extends Field implements EagerLoadingFieldInterface, GqlInlineFragmentFieldInterface
{
    /**
     * @event SectionEvent The event that is triggered before a section is saved.
     * @since 3.1.27
     */
    public const EVENT_SET_FIELD_BLOCK_TYPES = 'setFieldBlockTypes';

    public const PROPAGATION_METHOD_NONE = 'none';
    public const PROPAGATION_METHOD_SITE_GROUP = 'siteGroup';
    public const PROPAGATION_METHOD_LANGUAGE = 'language';
    /**
     * @since 3.7.0
     */
    public const PROPAGATION_METHOD_CUSTOM = 'custom';
    public const PROPAGATION_METHOD_ALL = 'all';

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
        return sprintf('\\%s|\\%s<\\%s>', MatrixBlockQuery::class, ElementCollection::class, MatrixBlock::class);
    }

    /**
     * @var int|null Min blocks
     */
    public ?int $minBlocks = null;

    /**
     * @var int|null Max blocks
     */
    public ?int $maxBlocks = null;

    /**
     * @var string Content table name
     * @since 3.0.23
     */
    public string $contentTable;

    /**
     * @var string Propagation method
     * @phpstan-var self::PROPAGATION_METHOD_NONE|self::PROPAGATION_METHOD_SITE_GROUP|self::PROPAGATION_METHOD_LANGUAGE|self::PROPAGATION_METHOD_ALL
     *
     * This will be set to one of the following:
     *
     * - `none` – Only save b locks in the site they were created in
     * - `siteGroup` – Save  blocks to other sites in the same site group
     * - `language` – Save blocks to other sites with the same language
     * - `all` – Save blocks to all sites supported by the owner element
     *
     * @since 3.2.0
     */
    public string $propagationMethod = self::PROPAGATION_METHOD_ALL;

    /**
     * @var string|null The field’s propagation key format, if [[propagationMethod]] is `custom`
     * @since 3.7.0
     */
    public ?string $propagationKeyFormat = null;

    /**
     * @var MatrixBlockType[]|null The field’s block types
     */
    private ?array $_blockTypes = null;

    /**
     * @var MatrixBlockType[]|null The block types' fields
     */
    private ?array $_blockTypeFields = null;

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        // Config normalization
        if (($config['contentTable'] ?? null) === '') {
            unset($config['contentTable']);
        }
        if (array_key_exists('localizeBlocks', $config)) {
            $config['propagationMethod'] = $config['localizeBlocks'] ? 'none' : 'all';
            unset($config['localizeBlocks']);
        }

        if (isset($config['blockTypes']) && $config['blockTypes'] === '') {
            $config['blockTypes'] = [];
        }

        parent::__construct($config);
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
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [
            ['propagationMethod'], 'in', 'range' => [
                self::PROPAGATION_METHOD_NONE,
                self::PROPAGATION_METHOD_SITE_GROUP,
                self::PROPAGATION_METHOD_LANGUAGE,
                self::PROPAGATION_METHOD_CUSTOM,
                self::PROPAGATION_METHOD_ALL,
            ],
        ];
        $rules[] = [['blockTypes'], ArrayValidator::class, 'min' => 1, 'skipOnEmpty' => false];
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
        if (isset($this->_blockTypes)) {
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
     * @param int[]|null $typeIds The Matrix block type IDs to return fields for.
     * If null, all block type fields will be returned.
     * @return FieldInterface[]
     */
    public function getBlockTypeFields(?array $typeIds = null): array
    {
        if (!isset($this->_blockTypeFields)) {
            $this->_blockTypeFields = [];

            if (!empty($blockTypes = $this->getBlockTypes())) {
                // Get the fields & layout IDs
                $contexts = [];
                $layoutIds = [];
                foreach ($blockTypes as $blockType) {
                    $contexts[] = 'matrixBlockType:' . $blockType->uid;
                    $layoutIds[] = $blockType->fieldLayoutId;
                }

                /** @var FieldInterface[] $fieldsById */
                $fieldsById = ArrayHelper::index(Craft::$app->getFields()->getAllFields($contexts), 'id');

                // Get all the field IDs grouped by layout ID
                $fieldIdsByLayoutId = Craft::$app->getFields()->getFieldIdsByLayoutIds($layoutIds);

                // Assemble the fields
                foreach ($blockTypes as $blockType) {
                    if (isset($fieldIdsByLayoutId[$blockType->fieldLayoutId])) {
                        $fieldColumnPrefix = 'field_' . $blockType->handle . '_';

                        foreach ($fieldIdsByLayoutId[$blockType->fieldLayoutId] as $fieldId) {
                            if (isset($fieldsById[$fieldId])) {
                                $fieldsById[$fieldId]->columnPrefix = $fieldColumnPrefix;
                                $this->_blockTypeFields[$blockType->id][] = $fieldsById[$fieldId];
                            }
                        }
                    }
                }
            }
        }

        $fields = [];

        foreach ($this->_blockTypeFields as $blockTypeId => $blockTypeFields) {
            if ($typeIds === null || in_array($blockTypeId, $typeIds)) {
                array_push($fields, ...$blockTypeFields);
            }
        }

        return $fields;
    }

    /**
     * Sets the block types.
     *
     * @param array|MatrixBlockType $blockTypes The block type settings or actual MatrixBlockType model instances
     */
    public function setBlockTypes(array|MatrixBlockType $blockTypes): void
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
                        ->from([DbTable::MATRIXBLOCKTYPES])
                        ->where(['id' => $key])
                        ->one();

                    if ($info) {
                        $blockType->id = $key;
                        $blockType->uid = $info['uid'];
                        $blockType->fieldLayoutId = $info['fieldLayoutId'];
                    }
                }

                $fieldLayout = $blockType->getFieldLayout();
                if (($fieldLayoutTab = $fieldLayout->getTabs()[0] ?? null) === null) {
                    $fieldLayoutTab = new FieldLayoutTab();
                    $fieldLayoutTab->name = 'Content';
                    $fieldLayoutTab->sortOrder = 1;
                    $fieldLayout->setTabs([$fieldLayoutTab]);
                }

                $fields = [];
                $layoutElements = [];

                if (!empty($config['fields'])) {
                    foreach ($config['fields'] as $fieldId => $fieldConfig) {
                        // If the field doesn't specify a type, then it probably wasn't meant to be submitted
                        if (!isset($fieldConfig['type'])) {
                            continue;
                        }

                        $fieldConfig = array_merge($defaultFieldConfig, $fieldConfig);
                        $field = $fields[] = Craft::$app->getFields()->createField([
                            'type' => $fieldConfig['type'],
                            'id' => is_numeric($fieldId) ? $fieldId : null,
                            'name' => $fieldConfig['name'],
                            'handle' => $fieldConfig['handle'],
                            'columnSuffix' => $fieldConfig['columnSuffix'] ?? null,
                            'instructions' => $fieldConfig['instructions'],
                            'required' => (bool)$fieldConfig['required'],
                            'searchable' => (bool)$fieldConfig['searchable'],
                            'translationMethod' => $fieldConfig['translationMethod'],
                            'translationKeyFormat' => $fieldConfig['translationKeyFormat'],
                            'settings' => $fieldConfig['typesettings'],
                        ]);

                        $layoutElements[] = Craft::createObject([
                            'class' => CustomField::class,
                            'uid' => $fieldConfig['uid'] ?? null,
                            'required' => (bool)$fieldConfig['required'],
                            'width' => (int)($fieldConfig['width'] ?? 0) ?: 100,
                        ], [$field]);
                    }
                }

                $fieldLayoutTab->setElements($layoutElements);
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
    public function getSettingsHtml(): ?string
    {
        // Get the available field types data
        $fieldTypeInfo = $this->_getFieldOptionsForConfigurator();

        $view = Craft::$app->getView();
        $view->registerAssetBundle(MatrixSettingsAsset::class);

        $placeholderKey = StringHelper::randomString(10);
        $view->registerJs(
            'new Craft.MatrixConfigurator(' .
            Json::encode($fieldTypeInfo, JSON_UNESCAPED_UNICODE) . ', ' .
            Json::encode($view->getNamespace(), JSON_UNESCAPED_UNICODE) . ', ' .
            Json::encode($view->namespaceInputName("blockTypes[__BLOCK_TYPE_{$placeholderKey}__][fields][__FIELD_{$placeholderKey}__][typesettings]")) . ', ' .
            Json::encode($placeholderKey) .
            ');'
        );

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

        // Prepare block type field data
        $blockTypes = [];
        $blockTypeFields = [];
        $totalNewBlockTypes = 0;

        foreach ($this->getBlockTypes() as $blockType) {
            $blockTypeId = (string)($blockType->id ?? 'new' . ++$totalNewBlockTypes);
            $blockTypes[$blockTypeId] = $blockType;
            $blockTypeFields[$blockTypeId] = [];
            $totalNewFields = 0;
            $fieldLayout = $blockType->getFieldLayout();
            $tabs = $fieldLayout->getTabs();
            if (empty($tabs)) {
                continue;
            }
            $tab = $fieldLayout->getTabs()[0];

            foreach ($tab->getElements() as $layoutElement) {
                if ($layoutElement instanceof CustomField) {
                    $field = $layoutElement->getField();

                    // If it's a missing field, swap it with a Text field
                    if ($field instanceof MissingField) {
                        /** @var PlainText $fallback */
                        $fallback = $field->createFallback(PlainText::class);
                        $fallback->addError('type', Craft::t('app', 'The field type “{type}” could not be found.', [
                            'type' => $field->expectedType,
                        ]));
                        $field = $fallback;
                        $layoutElement->setField($field);
                        $blockType->hasFieldErrors = true;
                    }

                    $fieldId = (string)($field->id ?? 'new' . ++$totalNewFields);
                    $blockTypeFields[$blockTypeId][$fieldId] = $layoutElement;

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

        return $view->renderTemplate('_components/fieldtypes/Matrix/settings.twig',
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
    public function normalizeValue(mixed $value, ?ElementInterface $element = null): mixed
    {
        if ($value instanceof ElementQueryInterface) {
            return $value;
        }

        $query = MatrixBlock::find();
        $this->_populateQuery($query, $element);

        // Set the initially matched elements if $value is already set, which is the case if there was a validation
        // error or we're loading an entry revision.
        if ($value === '') {
            $query->setCachedResult([]);
        } elseif ($element && is_array($value)) {
            $query->setCachedResult($this->_createBlocksFromSerializedData($value, $element));
        }

        return $query;
    }

    /**
     * Populates the field’s [[MatrixBlockQuery]] value based on the owner element.
     *
     * @param MatrixBlockQuery $query
     * @param ElementInterface|null $element
     * @since 3.4.0
     */
    private function _populateQuery(MatrixBlockQuery $query, ?ElementInterface $element = null): void
    {
        // Existing element?
        if ($element && $element->id) {
            $query->ownerId = $element->id;

            // Clear out id=false if this query was populated previously
            if ($query->id === false) {
                $query->id = null;
            }

            // If the owner is a revision, allow revision blocks to be returned as well
            if ($element->getIsRevision()) {
                $query
                    ->revisions(null)
                    ->trashed(null);
            }
        } else {
            $query->id = false;
        }

        $query
            ->fieldId($this->id)
            ->siteId($element->siteId ?? null);
    }

    /**
     * @inheritdoc
     */
    public function serializeValue(mixed $value, ?ElementInterface $element = null): mixed
    {
        /** @var MatrixBlockQuery|Collection $value */
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
    public function copyValue(ElementInterface $from, ElementInterface $to): void
    {
        // We'll do it later from afterElementPropagate()
    }

    /**
     * @inheritdoc
     */
    public function getElementConditionRuleType(): array|string|null
    {
        return EmptyFieldConditionRule::class;
    }

    /**
     * @inheritdoc
     */
    public function modifyElementsQuery(ElementQueryInterface $query, mixed $value): void
    {
        /** @var ElementQuery $query */
        if ($value === null) {
            return;
        }

        $ns = $this->handle . '_' . StringHelper::randomString(5);
        $existsQuery = (new Query())
            ->from(["matrixblocks_$ns" => DbTable::MATRIXBLOCKS])
            ->innerJoin(["elements_$ns" => DbTable::ELEMENTS], "[[elements_$ns.id]] = [[matrixblocks_$ns.id]]")
            ->innerJoin(["matrixblocks_owners_$ns" => DbTable::MATRIXBLOCKS_OWNERS], "[[matrixblocks_owners_$ns.blockId]] = [[elements_$ns.id]]")
            ->andWhere([
                "matrixblocks_$ns.fieldId" => $this->id,
                "elements_$ns.enabled" => true,
                "elements_$ns.dateDeleted" => null,
                "[[matrixblocks_owners_$ns.ownerId]]" => new Expression('[[elements.id]]'),
            ]);

        if ($value === 'not :empty:') {
            $value = ':notempty:';
        }

        if ($value === ':empty:') {
            $query->subQuery->andWhere(['not exists', $existsQuery]);
        } else {
            if ($value !== ':notempty:') {
                $ids = $value;
                if (!is_array($ids)) {
                    $ids = is_string($ids) ? StringHelper::split($ids) : [$ids];
                }

                $ids = array_map(function($id) {
                    return $id instanceof MatrixBlock ? $id->id : (int)$id;
                }, $ids);

                $existsQuery->andWhere(["matrixblocks_$ns.id" => $ids]);
            }

            $query->subQuery->andWhere(['exists', $existsQuery]);
        }
    }

    /**
     * @inheritdoc
     */
    public function getIsTranslatable(?ElementInterface $element = null): bool
    {
        return $this->propagationMethod !== self::PROPAGATION_METHOD_ALL;
    }

    /**
     * @inheritdoc
     */
    public function getTranslationDescription(?ElementInterface $element = null): ?string
    {
        if (!$element) {
            return null;
        }

        switch ($this->propagationMethod) {
            case self::PROPAGATION_METHOD_NONE:
                return Craft::t('app', 'Blocks will only be saved in the {site} site.', [
                    'site' => Craft::t('site', $element->getSite()->getName()),
                ]);
            case self::PROPAGATION_METHOD_SITE_GROUP:
                return Craft::t('app', 'Blocks will be saved across all sites in the {group} site group.', [
                    'group' => Craft::t('site', $element->getSite()->getGroup()->getName()),
                ]);
            case self::PROPAGATION_METHOD_LANGUAGE:
                $language = (new Locale($element->getSite()->language))
                    ->getDisplayName(Craft::$app->language);
                return Craft::t('app', 'Blocks will be saved across all {language}-language sites.', [
                    'language' => $language,
                ]);
            default:
                return null;
        }
    }

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    protected function inputHtml(mixed $value, ?ElementInterface $element = null): string
    {
        if ($element !== null && $element->hasEagerLoadedElements($this->handle)) {
            $value = $element->getEagerLoadedElements($this->handle)->all();
        }

        if ($value instanceof MatrixBlockQuery) {
            $value = $value->getCachedResult() ?? $value->limit(null)->status(null)->all();
        }

        $view = Craft::$app->getView();
        $id = $this->getInputId();

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
        $placeholderKey = StringHelper::randomString(10);
        $blockTypeInfo = $this->_getBlockTypeInfoForInput($element, $blockTypes, $placeholderKey);
        $createDefaultBlocks = (
            $this->minBlocks != 0 &&
            count($blockTypeInfo) === 1 &&
            (!$element || !$element->hasErrors($this->handle))
        );
        $staticBlocks = (
            $createDefaultBlocks &&
            $this->minBlocks == $this->maxBlocks &&
            $this->maxBlocks >= count($value)
        );

        $view->registerAssetBundle(MatrixAsset::class);

        $settings = [
            'placeholderKey' => $placeholderKey,
            'maxBlocks' => $this->maxBlocks,
            'staticBlocks' => $staticBlocks,
        ];

        $js = 'var matrixInput = new Craft.MatrixInput(' .
            '"' . $view->namespaceInputId($id) . '", ' .
            Json::encode($blockTypeInfo, JSON_UNESCAPED_UNICODE) . ', ' .
            '"' . $view->namespaceInputName($this->handle) . '", ' .
            Json::encode($settings) .
            ');';

        // Safe to create the default blocks?
        if ($createDefaultBlocks) {
            $blockTypeJs = Json::encode($blockTypes[0]->handle);
            for ($i = count($value); $i < $this->minBlocks; $i++) {
                $js .= "\nmatrixInput.addBlock($blockTypeJs, null, false);";
            }
        }

        $view->registerJs($js);

        return $view->renderTemplate('_components/fieldtypes/Matrix/input.twig',
            [
                'id' => $id,
                'name' => $this->handle,
                'blockTypes' => $blockTypes,
                'blocks' => $value,
                'static' => false,
                'staticBlocks' => $staticBlocks,
                'labelId' => $this->getLabelId(),
            ]);
    }

    /**
     * @inheritdoc
     */
    public function getElementValidationRules(): array
    {
        return [
            [
                'validateBlocks',
                'on' => [Element::SCENARIO_ESSENTIALS, Element::SCENARIO_DEFAULT, Element::SCENARIO_LIVE],
                'skipOnEmpty' => false,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function isValueEmpty(mixed $value, ElementInterface $element): bool
    {
        /** @var MatrixBlockQuery|Collection $value */
        return $value->count() === 0;
    }

    /**
     * Validates an owner element’s Matrix blocks.
     *
     * @param ElementInterface $element
     */
    public function validateBlocks(ElementInterface $element): void
    {
        /** @var MatrixBlockQuery|Collection $value */
        $value = $element->getFieldValue($this->handle);
        $blocks = $value->all();
        $allBlocksValidate = true;
        $scenario = $element->getScenario();

        foreach ($blocks as $i => $block) {
            /** @var MatrixBlock $block */
            if (
                $scenario === Element::SCENARIO_ESSENTIALS ||
                ($block->enabled && $scenario === Element::SCENARIO_LIVE)
            ) {
                $block->setScenario($scenario);
            }

            if (!$block->validate()) {
                $element->addModelErrors($block, "$this->handle[$i]");
                $allBlocksValidate = false;
            }
        }

        if (!$allBlocksValidate) {
            // Just in case the blocks weren't already cached
            $value->setCachedResult($blocks);
        }

        if (
            $element->getScenario() === Element::SCENARIO_LIVE &&
            ($this->minBlocks || $this->maxBlocks)
        ) {
            $arrayValidator = new ArrayValidator([
                'min' => $this->minBlocks ?: null,
                'max' => $this->maxBlocks ?: null,
                'tooFew' => $this->minBlocks ? Craft::t('app', '{attribute} should contain at least {min, number} {min, plural, one{block} other{blocks}}.', [
                    'attribute' => Craft::t('site', $this->name),
                    'min' => $this->minBlocks, // Need to pass this in now
                ]) : null,
                'tooMany' => $this->maxBlocks ? Craft::t('app', '{attribute} should contain at most {max, number} {max, plural, one{block} other{blocks}}.', [
                    'attribute' => Craft::t('site', $this->name),
                    'max' => $this->maxBlocks, // Need to pass this in now
                ]) : null,
                'skipOnEmpty' => false,
            ]);

            if (!$arrayValidator->validate($blocks, $error)) {
                $element->addError($this->handle, $error);
            }
        }
    }

    /**
     * @inheritdoc
     */
    protected function searchKeywords(mixed $value, ElementInterface $element): string
    {
        /** @var MatrixBlockQuery|Collection $value */
        $keywords = [];

        foreach ($value->all() as $block) {
            $fields = Craft::$app->getFields()->getAllFields($block->getFieldContext());
            foreach ($fields as $field) {
                if ($field->searchable) {
                    $fieldValue = $block->getFieldValue($field->handle);
                    $keywords[] = $field->getSearchKeywords($fieldValue, $element);
                }
            }
        }

        return parent::searchKeywords($keywords, $element);
    }

    /**
     * @inheritdoc
     */
    public function getStaticHtml(mixed $value, ElementInterface $element): string
    {
        /** @var MatrixBlockQuery|Collection $value */
        $value = $value->all();

        /** @var MatrixBlock[] $value */
        if (empty($value)) {
            return '<p class="light">' . Craft::t('app', 'No blocks.') . '</p>';
        }

        $id = StringHelper::randomString();

        return Craft::$app->getView()->renderTemplate('_components/fieldtypes/Matrix/input.twig', [
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
    public function getEagerLoadingMap(array $sourceElements): array|null|false
    {
        // Get the source element IDs
        $sourceElementIds = [];

        foreach ($sourceElements as $sourceElement) {
            $sourceElementIds[] = $sourceElement->id;
        }

        // Return any relation data on these elements, defined with this field
        $map = (new Query())
            ->select([
                'source' => 'matrixblocks_owners.ownerId',
                'target' => 'matrixblocks.id',
            ])
            ->from(['matrixblocks' => DbTable::MATRIXBLOCKS])
            ->innerJoin(['matrixblocks_owners' => DbTable::MATRIXBLOCKS_OWNERS], [
                'and',
                '[[matrixblocks_owners.blockId]] = [[matrixblocks.id]]',
                ['matrixblocks_owners.ownerId' => $sourceElementIds],
            ])
            ->where(['matrixblocks.fieldId' => $this->id])
            ->orderBy(['matrixblocks_owners.sortOrder' => SORT_ASC])
            ->all();

        return [
            'elementType' => MatrixBlock::class,
            'map' => $map,
            'criteria' => [
                'fieldId' => $this->id,
                'allowOwnerDrafts' => true,
                'allowOwnerRevisions' => true,
            ],
        ];
    }

    /**
     * @inheritdoc
     * @since 3.3.0
     */
    public function getContentGqlType(): Type|array
    {
        $typeArray = MatrixBlockTypeGenerator::generateTypes($this);
        $typeName = $this->handle . '_MatrixField';

        return [
            'name' => $this->handle,
            'type' => Type::nonNull(Type::listOf(Gql::getUnionType($typeName, $typeArray))),
            'args' => MatrixBlockArguments::getArguments(),
            'resolve' => MatrixBlockResolver::class . '::resolve',
            'complexity' => Gql::eagerLoadComplexity(),
        ];
    }

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    public function getContentGqlMutationArgumentType(): Type|array
    {
        return MatrixInputType::getType($this);
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
            } elseif (!$blockType->uid) {
                $blockType->uid = Db::uidById(DbTable::MATRIXBLOCKTYPES, $blockType->id);
            }

            foreach ($blockType->getCustomFields() as $field) {
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

        // Set the content table name
        if ($this->id) {
            $oldField = $fieldsService->getFieldById($this->id);
            if ($oldField instanceof self) {
                $this->contentTable = $oldField->contentTable;
            }
        }

        $this->contentTable = Craft::$app->getMatrix()->defineContentTableName($this);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function afterSave(bool $isNew): void
    {
        Craft::$app->getMatrix()->saveSettings($this, false);

        // If the propagation method just changed, resave all the Matrix blocks
        if (isset($this->oldSettings)) {
            $oldPropagationMethod = $this->oldSettings['propagationMethod'] ?? self::PROPAGATION_METHOD_ALL;
            $oldPropagationKeyFormat = $this->oldSettings['propagationKeyFormat'] ?? null;
            if ($this->propagationMethod !== $oldPropagationMethod || $this->propagationKeyFormat !== $oldPropagationKeyFormat) {
                Queue::push(new ApplyNewPropagationMethod([
                    'description' => Translation::prep('app', 'Applying new propagation method to Matrix blocks'),
                    'elementType' => MatrixBlock::class,
                    'criteria' => [
                        'fieldId' => $this->id,
                    ],
                ]));
            }
        }

        parent::afterSave($isNew);
    }

    /**
     * @inheritdoc
     */
    public function beforeApplyDelete(): void
    {
        Craft::$app->getMatrix()->deleteMatrixField($this);
        parent::beforeApplyDelete();
    }

    /**
     * @inheritdoc
     */
    public function afterElementPropagate(ElementInterface $element, bool $isNew): void
    {
        $matrixService = Craft::$app->getMatrix();
        $resetValue = false;

        if ($element->duplicateOf !== null) {
            // If this is a draft, just duplicate the relations
            if ($element->getIsDraft()) {
                $matrixService->duplicateOwnership($this, $element->duplicateOf, $element);
            } elseif ($element->getIsRevision()) {
                $matrixService->createRevisionBlocks($this, $element->duplicateOf, $element);
            } else {
                $matrixService->duplicateBlocks($this, $element->duplicateOf, $element, true, !$isNew);
            }
            $resetValue = true;
        } elseif ($element->isFieldDirty($this->handle) || !empty($element->newSiteIds)) {
            $matrixService->saveField($this, $element);
        } elseif ($element->mergingCanonicalChanges) {
            $matrixService->mergeCanonicalChanges($this, $element);
            $resetValue = true;
        }

        // Repopulate the Matrix block query if this is a new element
        if ($resetValue || $isNew) {
            /** @var MatrixBlockQuery|Collection $value */
            $value = $element->getFieldValue($this->handle);
            if ($value instanceof MatrixBlockQuery) {
                $this->_populateQuery($value, $element);
            }
            $value->clearCachedResult();
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

        // Delete any Matrix blocks that primarily belong to this element
        foreach (Craft::$app->getSites()->getAllSiteIds() as $siteId) {
            $elementsService = Craft::$app->getElements();
            /** @var MatrixBlock[] $matrixBlocks */
            $matrixBlocks = MatrixBlock::find()
                ->primaryOwnerId($element->id)
                ->status(null)
                ->siteId($siteId)
                ->all();

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
    public function afterElementRestore(ElementInterface $element): void
    {
        // Also restore any Matrix blocks for this element
        $elementsService = Craft::$app->getElements();
        foreach (ElementHelper::supportedSitesForElement($element) as $siteInfo) {
            /** @var MatrixBlock[] $blocks */
            $blocks = MatrixBlock::find()
                ->primaryOwnerId($element->id)
                ->status(null)
                ->siteId($siteInfo['siteId'])
                ->trashed()
                ->andWhere(['matrixblocks.deletedWithOwner' => true])
                ->all();

            foreach ($blocks as $block) {
                $elementsService->restoreElement($block);
            }
        }

        parent::afterElementRestore($element);
    }

    /**
     * Returns info about each field type for the configurator.
     *
     * @return array
     */
    private function _getFieldOptionsForConfigurator(): array
    {
        $fieldTypes = [];

        foreach (Craft::$app->getFields()->getAllFieldTypes() as $class) {
            /** @var FieldInterface|string $class */
            // No Matrix-Inception, sorry buddy.
            if ($class === self::class) {
                continue;
            }

            $fieldTypes[] = [
                'type' => $class,
                'name' => $class::displayName(),
            ];
        }

        // Sort them by name
        ArrayHelper::multisort($fieldTypes, 'name');

        return $fieldTypes;
    }

    /**
     * Returns info about each block type and their field types for the Matrix field input.
     *
     * @param ElementInterface|null $element
     * @param MatrixBlockType[] $blockTypes
     * @param string $placeholderKey
     * @return array
     */
    private function _getBlockTypeInfoForInput(?ElementInterface $element, array $blockTypes, string $placeholderKey): array
    {
        $blockTypeInfo = [];

        // Set a temporary namespace for these
        // Note: we can't just wrap FieldLayoutForm::render() in a callable passed to namespaceInputs() here,
        // because the form HTML is for JavaScript; not returned by inputHtml().
        $view = Craft::$app->getView();
        $oldNamespace = $view->getNamespace();
        $view->setNamespace($view->namespaceInputName("$this->handle[blocks][__BLOCK_{$placeholderKey}__]"));

        foreach ($blockTypes as $blockType) {
            // Create a fake MatrixBlock so the field types have a way to get at the owner element, if there is one
            $block = new MatrixBlock();
            $block->fieldId = $this->id;
            $block->typeId = $blockType->id;

            if ($element) {
                $block->setOwner($element);
                $block->siteId = $element->siteId;
            }

            $fieldLayout = $blockType->getFieldLayout();
            $fieldLayoutTab = $fieldLayout->getTabs()[0] ?? new FieldLayoutTab();

            foreach ($fieldLayoutTab->getElements() as $layoutElement) {
                if ($layoutElement instanceof CustomField) {
                    $layoutElement->getField()->setIsFresh(true);
                }
            }

            $view->startJsBuffer();
            $bodyHtml = $view->namespaceInputs($fieldLayout->createForm($block)->render());
            $js = $view->clearJsBuffer();

            // Reset $_isFresh's
            foreach ($fieldLayoutTab->getElements() as $layoutElement) {
                if ($layoutElement instanceof CustomField) {
                    $layoutElement->getField()->setIsFresh(null);
                }
            }

            $blockTypeInfo[] = [
                'handle' => $blockType->handle,
                'name' => Craft::t('site', $blockType->name),
                'bodyHtml' => $bodyHtml,
                'js' => $js,
            ];
        }

        $view->setNamespace($oldNamespace);
        return $blockTypeInfo;
    }

    /**
     * Creates an array of blocks based on the given serialized data.
     *
     * @param array $value The raw field value
     * @param ElementInterface $element The element the field is associated with
     * @return MatrixBlock[]
     */
    private function _createBlocksFromSerializedData(array $value, ElementInterface $element): array
    {
        // Get the possible block types for this field
        /** @var MatrixBlockType[] $blockTypes */
        $blockTypes = ArrayHelper::index(Craft::$app->getMatrix()->getBlockTypesByFieldId($this->id), 'handle');

        // Get the old blocks
        if ($element->id) {
            /** @var MatrixBlock[] $oldBlocksById */
            $oldBlocksById = MatrixBlock::find()
                ->fieldId($this->id)
                ->ownerId($element->id)
                ->siteId($element->siteId)
                ->status(null)
                ->indexBy('id')
                ->all();
        } else {
            $oldBlocksById = [];
        }

        // Should we ignore disabled blocks?
        $request = Craft::$app->getRequest();
        $hideDisabledBlocks = !$request->getIsConsoleRequest() && (
                $request->getToken() !== null ||
                $request->getIsLivePreview()
            );

        $blocks = [];
        $prevBlock = null;

        $fieldNamespace = $element->getFieldParamNamespace();
        $baseBlockFieldNamespace = $fieldNamespace ? "$fieldNamespace.$this->handle" : null;

        // Was the value posted in the new (delta) format?
        if (isset($value['blocks']) || isset($value['sortOrder'])) {
            $newBlockData = $value['blocks'] ?? [];
            $newSortOrder = $value['sortOrder'] ?? array_keys($oldBlocksById);
            if ($baseBlockFieldNamespace) {
                $baseBlockFieldNamespace .= '.blocks';
            }
        } else {
            $newBlockData = $value;
            $newSortOrder = array_keys($value);
        }

        foreach ($newSortOrder as $blockId) {
            if (isset($newBlockData[$blockId])) {
                $blockData = $newBlockData[$blockId];
            } elseif (
                isset(Elements::$duplicatedElementSourceIds[$blockId]) &&
                isset($newBlockData[Elements::$duplicatedElementSourceIds[$blockId]])
            ) {
                // $blockId is a duplicated block's ID, but the data was sent with the original block ID
                $blockData = $newBlockData[Elements::$duplicatedElementSourceIds[$blockId]];
            } else {
                $blockData = [];
            }

            // If this is a preexisting block but we don't have a record of it,
            // check to see if it was recently duplicated.
            if (
                !str_starts_with($blockId, 'new') &&
                !isset($oldBlocksById[$blockId]) &&
                isset(Elements::$duplicatedElementIds[$blockId]) &&
                isset($oldBlocksById[Elements::$duplicatedElementIds[$blockId]])
            ) {
                $blockId = Elements::$duplicatedElementIds[$blockId];
            }

            // Existing block?
            if (isset($oldBlocksById[$blockId])) {
                /** @var MatrixBlock $block */
                $block = $oldBlocksById[$blockId];
                $dirty = !empty($blockData);

                // Is this a derivative element, and does the block primarily belong to the canonical?
                if ($dirty && $element->getIsDerivative() && $block->primaryOwnerId === $element->getCanonicalId()) {
                    // Duplicate it as a draft. (We'll drop its draft status from `Matrix::saveField()`.)
                    $block = Craft::$app->getDrafts()->createDraft($block, Craft::$app->getUser()->getId(), null, null, [
                        'canonicalId' => $block->id,
                        'primaryOwnerId' => $element->id,
                        'owner' => $element,
                        'siteId' => $element->siteId,
                        'propagating' => false,
                        'markAsSaved' => false,
                    ]);
                }

                $block->dirty = $dirty;
            } else {
                // Make sure it's a valid block type
                if (!isset($blockData['type']) || !isset($blockTypes[$blockData['type']])) {
                    continue;
                }
                $block = new MatrixBlock();
                $block->fieldId = $this->id;
                $block->typeId = $blockTypes[$blockData['type']]->id;
                $block->primaryOwnerId = $block->ownerId = $element->id;
                $block->siteId = $element->siteId;

                // Preserve the collapsed state, which the browser can't remember on its own for new blocks
                $block->collapsed = !empty($blockData['collapsed']);
            }

            if (isset($blockData['enabled'])) {
                $block->enabled = (bool)$blockData['enabled'];
            }

            // Skip disabled blocks on Live Preview requests
            if ($hideDisabledBlocks && !$block->enabled) {
                continue;
            }

            $block->setOwner($element);

            // Set the content post location on the block if we can
            if ($baseBlockFieldNamespace) {
                $block->setFieldParamNamespace("$baseBlockFieldNamespace.$blockId.fields");
            }

            if (isset($blockData['fields'])) {
                $block->setFieldValues($blockData['fields']);
            }

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

        /** @var MatrixBlock[] $blocks */
        return $blocks;
    }
}
