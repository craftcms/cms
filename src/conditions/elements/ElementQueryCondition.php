<?php

namespace craft\conditions\elements;

use Craft;
use craft\base\ElementInterface;
use craft\conditions\BaseQueryCondition;
use craft\conditions\elements\fields\FieldConditionRuleInterface;
use craft\errors\InvalidTypeException;

/**
 * ElementQueryCondition provides an element query condition.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class ElementQueryCondition extends BaseQueryCondition
{
    /**
     * @var string|null The element type being queried.
     */
    public ?string $elementType;

    /**
     * @var string The field context that should be used when fetching custom fields’ condition rule types.
     * @see conditionRuleTypes()
     */
    public string $fieldContext = 'global';

    /**
     * Constructor.
     *
     * @param string|null $elementType
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
    protected function addRuleLabel(): string
    {
        return Craft::t('app', 'Add a filter');
    }

    /**
     * @inheritdoc
     */
    protected function conditionRuleTypes(): array
    {
        $types = [
            RelatedToConditionRule::class,
            SlugConditionRule::class,
        ];

        if ($this->elementType !== null) {
            /** @var string|ElementInterface $elementType */
            $elementType = $this->elementType;

            if ($elementType::hasUris()) {
                $types[] = HasUrlConditionRule::class;
            }
        }

        foreach (Craft::$app->getFields()->getAllFields($this->fieldContext) as $field) {
            if (($type = $field->getQueryConditionRuleType()) !== null) {
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
}
