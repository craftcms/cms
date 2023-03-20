<?php

namespace craft\elements\conditions;

use Craft;
use craft\base\conditions\BaseCondition;
use craft\base\conditions\ConditionRuleInterface;
use craft\base\ElementInterface;
use craft\elements\db\ElementQueryInterface;
use craft\errors\InvalidTypeException;
use craft\fields\conditions\FieldConditionRuleInterface;
use yii\base\InvalidConfigException;

/**
 * ElementCondition provides an element condition.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class ElementCondition extends BaseCondition implements ElementConditionInterface
{
    /**
     * @inerhitdoc
     */
    public bool $sortable = false;

    /**
     * @var string|null The element type being queried.
     * @phpstan-var class-string<ElementInterface>|null
     */
    public ?string $elementType = null;

    /**
     * @var string|null The selected element source key.
     * @since 4.1.0
     */
    public ?string $sourceKey = null;

    /**
     * @var string The field context that should be used when fetching custom fields’ condition rule types.
     * @see conditionRuleTypes()
     */
    public string $fieldContext = 'global';

    /**
     * @var string[] The element query params that available rules shouldn’t compete with.
     */
    public array $queryParams = [];

    /**
     * @var ElementInterface|null The element that this condition is being executed in reference to, if any.
     *
     * @since 4.4.0
     */
    public ?ElementInterface $referenceElement = null;

    /**
     * Constructor.
     *
     * @param string|null $elementType
     * @phpstan-param class-string<ElementInterface>|null $elementType
     * @param array $config
     */
    public function __construct(?string $elementType = null, array $config = [])
    {
        $elementType = $elementType ?? $config['elementType'] ?? $config['attributes']['elementType'] ?? null;
        unset($config['elementType'], $config['attributes']['elementType']);

        if (
            $elementType !== null &&
            (!class_exists($elementType) || !is_subclass_of($elementType, ElementInterface::class))
        ) {
            throw new InvalidConfigException("Invalid element type: $elementType");
        }

        $this->elementType = $elementType;
        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    protected function isConditionRuleSelectable(ConditionRuleInterface $rule): bool
    {
        if (!$rule instanceof ElementConditionRuleInterface) {
            return false;
        }

        if (!parent::isConditionRuleSelectable($rule)) {
            return false;
        }

        // Make sure the rule doesn't conflict with the existing params
        $queryParams = array_merge($this->queryParams);
        foreach ($this->getConditionRules() as $existingRule) {
            /** @var ElementConditionRuleInterface $existingRule */
            array_push($queryParams, ...$existingRule->getExclusiveQueryParams());
        }

        $queryParams = array_flip($queryParams);

        foreach ($rule->getExclusiveQueryParams() as $param) {
            if (isset($queryParams[$param])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    protected function conditionRuleTypes(): array
    {
        $types = [
            DateCreatedConditionRule::class,
            DateUpdatedConditionRule::class,
            IdConditionRule::class,
            RelatedToConditionRule::class,
            SlugConditionRule::class,
        ];

        /** @var string|ElementInterface|null $elementType */
        /** @phpstan-var class-string<ElementInterface>|ElementInterface|null $elementType */
        $elementType = $this->elementType;

        if (Craft::$app->getIsMultiSite() && (!$elementType || $elementType::isLocalized())) {
            $types[] = SiteConditionRule::class;
        }

        if ($elementType !== null) {
            if ($elementType::hasUris()) {
                $types[] = HasUrlConditionRule::class;
                $types[] = UriConditionRule::class;
            }

            if ($elementType::hasStatuses()) {
                $types[] = StatusConditionRule::class;
            }

            if ($elementType::hasContent()) {
                if ($elementType::hasTitles()) {
                    $types[] = TitleConditionRule::class;
                }

                // If we have a source key, we can fetch just the field layouts that are available to it
                if ($this->sourceKey) {
                    $fieldLayouts = Craft::$app->getElementSources()->getFieldLayoutsForSource($elementType, $this->sourceKey);
                } else {
                    $fieldLayouts = Craft::$app->getFields()->getLayoutsByType($elementType);
                }

                foreach ($fieldLayouts as $fieldLayout) {
                    foreach ($fieldLayout->getCustomFields() as $field) {
                        if (($type = $field->getElementConditionRuleType()) !== null) {
                            if (is_string($type)) {
                                $type = ['class' => $type];
                            }
                            if (!is_subclass_of($type['class'], FieldConditionRuleInterface::class)) {
                                throw new InvalidTypeException($type['class'], FieldConditionRuleInterface::class);
                            }
                            $type['fieldUid'] = $field->uid;
                            $types[] = $type;
                        }
                    }
                }
            }
        }

        return $types;
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['elementType', 'fieldContext'], 'safe'];
        return $rules;
    }

    /**
     * @inheritdoc
     */
    protected function config(): array
    {
        return $this->toArray(['elementType', 'fieldContext']);
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(ElementQueryInterface $query): void
    {
        foreach ($this->getConditionRules() as $rule) {
            /** @var ElementConditionRuleInterface $rule */
            $rule->modifyQuery($query);
        }
    }

    /**
     * @inheritdoc
     */
    public function matchElement(ElementInterface $element): bool
    {
        foreach ($this->getConditionRules() as $rule) {
            /** @var ElementConditionRuleInterface $rule */
            if (!$rule->matchElement($element)) {
                return false;
            }
        }

        return true;
    }
}
