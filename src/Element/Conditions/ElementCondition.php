<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Conditions;

use craft\base\ElementInterface;
use craft\errors\InvalidTypeException;
use CraftCms\Cms\Condition\BaseCondition;
use CraftCms\Cms\Condition\Contracts\ConditionRuleInterface;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionInterface;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Field\Conditions\Contracts\FieldConditionRuleInterface;
use CraftCms\Cms\Field\Conditions\GeneratedFieldConditionRule;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\Support\Facades\SiteGroups;
use CraftCms\Cms\Support\Facades\Sites;
use Override;
use yii\base\InvalidConfigException;

class ElementCondition extends BaseCondition implements ElementConditionInterface
{
    #[\Override]
    public bool $sortable = false;

    /**
     * @var class-string<ElementInterface>|null The element type being queried.
     */
    public ?string $elementType = null;

    /**
     * @var string|null The selected element source key.
     */
    public ?string $sourceKey = null;

    /**
     * @var string The field context that should be used when fetching custom fields’ condition rule types.
     *
     * @see selectableConditionRules()
     */
    public string $fieldContext = 'global';

    /**
     * @var string[] The element query params that available rules shouldn’t compete with.
     */
    public array $queryParams = [];

    /**
     * @var ElementInterface|null The element that this condition is being executed in reference to, if any.
     */
    public ?ElementInterface $referenceElement = null;

    /**
     * @var FieldLayout[]
     *
     * @see getFieldLayouts()
     * @see setFieldLayouts()
     */
    private array $_fieldLayouts;

    public array $fieldLayouts {
        get => $this->getFieldLayouts();
        set {
            $this->setFieldLayouts($value);
        }
    }

    /**
     * Constructor.
     *
     * @param  class-string<ElementInterface>|null  $elementType
     */
    public function __construct(?string $elementType = null, array $config = [])
    {
        $elementType ??= $config['elementType'] ?? $config['attributes']['elementType'] ?? null;
        unset($config['elementType'], $config['attributes']['elementType']);

        if (
            $elementType !== null &&
            (! class_exists($elementType) || ! is_subclass_of($elementType, ElementInterface::class))
        ) {
            throw new InvalidConfigException("Invalid element type: $elementType");
        }

        if ($elementType !== null) {
            $this->elementType = $elementType;
        }

        parent::__construct($config);
    }

    public function getFieldLayouts(): array
    {
        if (isset($this->_fieldLayouts)) {
            return $this->_fieldLayouts;
        }

        if ($this->elementType === null) {
            return [];
        }

        // If we have a source key, we can fetch just the field layouts that are available to it
        if ($this->sourceKey) {
            return app(ElementSources::class)->getFieldLayoutsForSource($this->elementType, $this->sourceKey)->all();
        }

        return app(Fields::class)->getLayoutsByType($this->elementType)->all();
    }

    public function setFieldLayouts(array $fieldLayouts): void
    {
        $fieldsService = app(Fields::class);
        $this->_fieldLayouts = array_map(function (FieldLayout|array $fieldLayout) use ($fieldsService) {
            if (is_array($fieldLayout)) {
                $fieldLayout['type'] = $this->elementType;

                return $fieldsService->createLayout($fieldLayout);
            }

            return $fieldLayout;
        }, $fieldLayouts);
    }

    #[Override]
    protected function isConditionRuleSelectable(ConditionRuleInterface $rule): bool
    {
        if (! $rule instanceof ElementConditionRuleInterface) {
            return false;
        }

        if (! parent::isConditionRuleSelectable($rule)) {
            return false;
        }

        // Make sure the rule doesn't conflict with the existing params
        $queryParams = array_merge($this->queryParams);
        foreach ($this->getConditionRules() as $existingRule) {
            /** @var ElementConditionRuleInterface $existingRule */
            array_push($queryParams, ...$existingRule->getExclusiveQueryParams());
        }

        $queryParams = array_flip($queryParams);

        return array_all($rule->getExclusiveQueryParams(), fn ($param) => ! isset($queryParams[$param]));
    }

    protected function selectableConditionRules(): array
    {
        $types = [
            DateCreatedConditionRule::class,
            DateUpdatedConditionRule::class,
            IdConditionRule::class,
            NotRelatedToConditionRule::class,
            RelatedToConditionRule::class,
            SlugConditionRule::class,
        ];

        if (Sites::isMultiSite() && ($this->elementType === null || $this->elementType::isLocalized())) {
            $types[] = SiteConditionRule::class;
            $types[] = LanguageConditionRule::class;

            if (SiteGroups::getAllGroups()->count() > 1) {
                $types[] = SiteGroupConditionRule::class;
            }
        }

        if ($this->elementType !== null) {
            if ($this->elementType::hasUris()) {
                $types[] = HasUrlConditionRule::class;
                $types[] = UriConditionRule::class;
            }

            if ($this->elementType::hasStatuses()) {
                $types[] = StatusConditionRule::class;
            }

            if ($this->elementType::hasTitles()) {
                $types[] = TitleConditionRule::class;
            }

            foreach ($this->getFieldLayouts() as $fieldLayout) {
                foreach ($fieldLayout->getCustomFieldElements() as $layoutElement) {
                    // Discard fields with empty labels
                    $label = $layoutElement->label();
                    if ($label === null) {
                        continue;
                    }
                    $field = $layoutElement->getField();
                    $type = $field->getElementConditionRuleType();
                    if ($type === null) {
                        continue;
                    }

                    if (is_string($type)) {
                        $type = ['class' => $type];
                    }
                    if (! is_subclass_of($type['class'], FieldConditionRuleInterface::class)) {
                        throw new InvalidTypeException($type['class'], FieldConditionRuleInterface::class);
                    }

                    $type['fieldUid'] = $field->uid;
                    $type['layoutElementUid'] = $field->layoutElement->uid;

                    $types[] = $type;
                }

                foreach ($fieldLayout->getGeneratedFields() as $field) {
                    if (($field['name'] ?? '') !== '' && ($field['handle'] ?? '') !== '') {
                        $types[] = [
                            'class' => GeneratedFieldConditionRule::class,
                            'fieldUid' => $field['uid'],
                        ];
                    }
                }
            }
        }

        return $types;
    }

    #[Override]
    public function getRules(): array
    {
        return array_merge(parent::getRules(), [
            'elementType' => ['string'],
            'fieldLayouts' => ['array'],
            'fieldContext' => ['string'],
        ]);
    }

    #[Override]
    public function getBuilderConfig(): array
    {
        $config = parent::getBuilderConfig();

        if (isset($this->_fieldLayouts)) {
            $config['fieldLayouts'] = array_map(fn (FieldLayout $layout) => $layout->getConfig(), $this->_fieldLayouts);
        }

        return $config;
    }

    #[Override]
    protected function config(): array
    {
        return [
            'elementType' => $this->elementType,
            'fieldContext' => $this->fieldContext,
        ];
    }

    public function modifyQuery(ElementQueryInterface $query): void
    {
        foreach ($this->getConditionRules() as $rule) {
            /** @var ElementConditionRuleInterface $rule */
            $rule->modifyQuery($query);
        }
    }

    public function matchElement(ElementInterface $element): bool
    {
        /** @var ElementConditionRuleInterface[] $rules */
        $rules = $this->getConditionRules();

        return array_all($rules, fn (ElementConditionRuleInterface $rule) => $rule->matchElement($element));
    }
}
