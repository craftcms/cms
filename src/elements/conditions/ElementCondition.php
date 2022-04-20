<?php

namespace craft\elements\conditions;

use Craft;
use craft\base\conditions\BaseCondition;
use craft\base\conditions\ConditionRuleInterface;
use craft\base\ElementInterface;
use craft\elements\db\ElementQueryInterface;
use craft\errors\InvalidTypeException;
use craft\fields\conditions\FieldConditionRuleInterface;

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
     * @var string The field context that should be used when fetching custom fields’ condition rule types.
     * @see conditionRuleTypes()
     */
    public string $fieldContext = 'global';

    /**
     * @var string[] The element query params that available rules shouldn’t compete with.
     */
    public array $queryParams = [];

    /**
     * Constructor.
     *
     * @param string|null $elementType
     * @phpstan-param class-string<ElementInterface>|null $elementType
     * @param array $config
     */
    public function __construct(?string $elementType = null, array $config = [])
    {
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

        if (Craft::$app->getIsMultiSite()) {
            $types[] = SiteConditionRule::class;
        }

        if ($this->elementType !== null) {
            /** @var string|ElementInterface $elementType */
            /** @phpstan-var class-string<ElementInterface>|ElementInterface $elementType */
            $elementType = $this->elementType;

            if ($elementType::hasContent() && $elementType::hasTitles()) {
                $types[] = TitleConditionRule::class;
            }

            if ($elementType::hasUris()) {
                $types[] = HasUrlConditionRule::class;
                $types[] = UriConditionRule::class;
            }

            if ($elementType::hasStatuses()) {
                $types[] = StatusConditionRule::class;
            }
        }

        foreach (Craft::$app->getFields()->getAllFields($this->fieldContext) as $field) {
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
