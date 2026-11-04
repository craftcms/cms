<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Conditions;

use CraftCms\Cms\Condition\BaseCondition;
use CraftCms\Cms\Condition\Contracts\ConditionGroupInterface;
use CraftCms\Cms\Condition\Contracts\ConditionRuleInterface;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionInterface;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Conditions\Contracts\ElementQueryConditionRuleInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Exceptions\InvalidTypeException;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Field\Conditions\Contracts\FieldConditionRuleInterface;
use CraftCms\Cms\Field\Conditions\GeneratedFieldConditionRule;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\Support\Facades\ElementSources;
use CraftCms\Cms\Support\Facades\SiteGroups;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Json;
use Illuminate\Database\Query\Builder;
use Override;
use RuntimeException;

/**
 * @since 6.0.0
 */
class ElementCondition extends BaseCondition implements ElementConditionInterface
{
    public static function createGroup(): ConditionGroupInterface
    {
        return new ElementConditionGroup;
    }

    #[Override]
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
     * @var bool Whether the condition will be applied to an element query, as opposed to an instantiated element.
     */
    public bool $forQuery = false;

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

    /**
     * @var array{layouts: FieldLayout[], fields: array<string, list<array{field: FieldInterface, label: string|null}>>, labels: array<int, string|null>}
     *
     * @see customFieldIndex()
     */
    private array $_customFieldIndex;

    /** @var array<FieldLayout|array<string, mixed>> */
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
            throw new RuntimeException("Invalid element type: $elementType");
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
            return ElementSources::getFieldLayoutsForSource($this->elementType, $this->sourceKey)->all();
        }

        return app(Fields::class)->getLayoutsByType($this->elementType)->all();
    }

    /** @param array<FieldLayout|array<string, mixed>> $fieldLayouts */
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

    /**
     * Returns the custom fields in the condition’s field layouts with their labels, indexed by their
     * field UUID and by any old field UUID they were saved with, in layout order.
     *
     * Field condition rules look up their field instances here instead of each rescanning every layout
     * and translating every instance’s label.
     *
     * @return array<string, list<array{field: FieldInterface, label: string|null}>>
     */
    public function getCustomFieldsByUid(): array
    {
        return $this->customFieldIndex()['fields'];
    }

    /**
     * @return array{layouts: FieldLayout[], fields: array<string, list<array{field: FieldInterface, label: string|null}>>, labels: array<int, string|null>}
     */
    private function customFieldIndex(): array
    {
        $fieldLayouts = $this->getFieldLayouts();

        if (isset($this->_customFieldIndex) && $this->_customFieldIndex['layouts'] === $fieldLayouts) {
            return $this->_customFieldIndex;
        }

        $fields = [];
        $labels = [];

        foreach ($fieldLayouts as $fieldLayout) {
            foreach ($fieldLayout->getCustomFields() as $field) {
                $instance = ['field' => $field, 'label' => $field->layoutElement->label()];
                $fields[$field->uid][] = $instance;
                $labels[spl_object_id($field->layoutElement)] = $instance['label'];

                $oldFieldUid = $field->layoutElement->oldFieldUid;
                if ($oldFieldUid !== null && $oldFieldUid !== $field->uid) {
                    $fields[$oldFieldUid][] = $instance;
                }
            }
        }

        return $this->_customFieldIndex = ['layouts' => $fieldLayouts, 'fields' => $fields, 'labels' => $labels];
    }

    #[Override]
    protected function isConditionRuleSelectable(ConditionRuleInterface $rule): bool
    {
        $contract = $this->forQuery
            ? ElementQueryConditionRuleInterface::class
            : ElementConditionRuleInterface::class;

        if (! is_a($rule, $contract)) {
            return false;
        }

        return parent::isConditionRuleSelectable($rule);
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

            $labels = $this->customFieldIndex()['labels'];
            $seenFieldRules = [];

            foreach ($this->getFieldLayouts() as $fieldLayout) {
                foreach ($fieldLayout->getCustomFieldElements() as $layoutElement) {
                    // Discard fields with empty labels
                    $label = $labels[spl_object_id($layoutElement)] ?? $layoutElement->label();
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

                    // A field shared by many layouts would otherwise offer one rule per layout, which the
                    // condition builder can't tell apart and only shows once
                    $key = Json::encode([$type, $label, $field->handle]);
                    if (isset($seenFieldRules[$key])) {
                        continue;
                    }
                    $seenFieldRules[$key] = true;

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
            'forQuery' => ['boolean'],
            'sourceKey' => ['nullable', 'string'],
        ]);
    }

    #[Override]
    public function getBuilderConfig(): array
    {
        $config = parent::getBuilderConfig();
        $config['sourceKey'] = $this->sourceKey;

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
            'forQuery' => $this->forQuery,
        ];
    }

    public function modifyQuery(ElementQueryInterface $elementQuery): void
    {
        $elementQuery->beforeQuery(function (ElementQueryInterface $elementQuery) {
            $elementQuery->where(function (Builder $query) use ($elementQuery) {
                /** @var ElementConditionGroup $group */
                $group = $this->getConditionRules();
                $group->modifyQuery($query, $elementQuery);
            });
        });
    }

    public function matchElement(ElementInterface $element): bool
    {
        /** @var ElementConditionGroup $group */
        $group = $this->getConditionRules();

        return $group->matchElement($element);
    }
}
