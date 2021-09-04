<?php

namespace craft\conditions;

use Craft;
use craft\base\Component;
use craft\events\RegisterConditionRuleTypesEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use Illuminate\Support\Collection;
use yii\base\InvalidArgumentException;

/**
 * Base condition class.
 *
 * @property-read string $addRuleLabel
 * @property-read array $config
 * @property-read string $html
 * @property Collection $conditionRules
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
abstract class BaseCondition extends Component implements ConditionInterface
{
    /**
     * @event RegisterConditionRuleTypesEvent The event that is triggered when defining the condition rule types.
     * @see getConditionRuleTypes()
     */
    public const EVENT_REGISTER_CONDITION_RULE_TYPES = 'registerConditionRuleTypes';

    /**
     * @var string The condition handle.
     */
    public string $handle;

    /**
     * @var Collection|ConditionRuleInterface[]
     */
    private Collection $_conditionRules;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        if (!isset($this->_conditionRules)) {
            $this->_conditionRules = new Collection();
        }
    }

    /**
     * @inheritdoc
     */
    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'conditionRules',
            'handle',
        ]);
    }

    /**
     * Returns the label for the “Add a rule” button.
     *
     * @return string
     */
    public function getAddRuleLabel(): string
    {
        return Craft::t('app', 'Add a rule');
    }

    /**
     * Returns the available rule types for this condition.
     *
     * @return string[]
     */
    public function getConditionRuleTypes(): array
    {
        $conditionRuleTypes = $this->conditionRuleTypes();

        // Give plugins a chance to modify them
        $event = new RegisterConditionRuleTypesEvent([
            'conditionRuleTypes' => $conditionRuleTypes,
        ]);

        $this->trigger(self::EVENT_REGISTER_CONDITION_RULE_TYPES, $event);
        return $event->conditionRuleTypes;
    }

    /**
     * Returns the available rule types for this condition.
     *
     * Conditions should override this method instead of [[getConditionRuleTypes()]]
     * so [[EVENT_REGISTER_CONDITION_RULE_TYPES]] handlers can modify the class-defined rule types.
     *
     * @return string[]
     */
    abstract protected function conditionRuleTypes(): array;

    /**
     * Returns the rules this condition is configured with.
     *
     * @return Collection
     */
    public function getConditionRules(): Collection
    {
        return $this->_conditionRules;
    }

    /**
     * Sets the rules this condition should be configured with.
     *
     * @param ConditionRuleInterface[]|array[] $rules
     * @throws InvalidArgumentException if any of the rules don’t validate
     */
    public function setConditionRules(array $rules): void
    {
        $this->_conditionRules = new Collection(array_map(function($rule) {
            if (is_array($rule)) {
                $rule = Craft::$app->getConditions()->createConditionRule($rule);;
            }
            if (!$this->validateConditionRule($rule)) {
                throw new InvalidArgumentException('Invalid condition rule');
            }
            $rule->setCondition($this);
            return $rule;
        }, $rules));
    }

    /**
     * Adds a rule to the condition.
     *
     * @param ConditionRuleInterface $rule
     * @throws InvalidArgumentException if the rule doesn’t validate
     */
    public function addConditionRule(ConditionRuleInterface $rule): void
    {
        if (!$this->validateConditionRule($rule)) {
            throw new InvalidArgumentException('Invalid condition rule');
        }
        $rule->setCondition($this);
        $this->_conditionRules->add($rule);
    }

    /**
     * Validates a given rule to ensure it can be used with this condition.
     *
     * @param mixed $rule
     * @return bool
     */
    protected function validateConditionRule($rule): bool
    {
        return $rule instanceof ConditionRuleInterface;
    }

    /**
     * @inheritdoc
     */
    public function getConfig(): array
    {
        return [
            'type' => get_class($this),
            'handle' => $this->handle,
            'conditionRules' => $this->getConditionRules()
                ->map(fn(ConditionRuleInterface $rule) => $rule->getConfig())
                ->all()
        ];
    }

    /**
     * @inheritdoc
     */
    public function getBuilderHtml(): string
    {
        $conditionId = Html::namespaceId('condition', $this->handle);
        $indicatorId = Html::namespaceId('indicator', $this->handle);

        // Main Condition tag, and htmx inheritable options
        $html = Html::beginTag('form', [
            'id' => 'condition',
            'class' => 'pane',
            'hx-target' => '#' . $conditionId, // replace self
            'hx-swap' => 'outerHTML', // replace this tag with the response
            'hx-indicator' => '#' . $indicatorId, // ID of the spinner
        ]);

        // Loading indicator
        $html .= Html::tag('div', '', ['id' => 'indicator', 'class' => 'htmx-indicator spinner']);

        // Condition hidden inputs
        $html .= Html::hiddenInput('handle', $this->handle);
        $html .= Html::hiddenInput('type', get_class($this));
        $html = Html::namespaceHtml($html, $this->handle);

        $html .= Html::csrfInput();
        $html .= Html::hiddenInput('conditionLocation', $this->handle);

        $allRulesHtml = '';
        foreach ($this->_conditionRules as $rule) {
            /** @var string|ConditionRuleInterface $ruleClass */
            $ruleClass = get_class($rule);
            $ruleTypeOptions = [];
            foreach ($this->getConditionRuleTypes() as $type) {
                /** @var string|ConditionRuleInterface $type */
                if ($type !== $ruleClass) {
                    $ruleTypeOptions[] = ['value' => $type, 'label' => $type::displayName()];
                }
            }
            $ruleTypeOptions[] = ['value' => $ruleClass, 'label' => $ruleClass::displayName()];

            ArrayHelper::multisort($ruleTypeOptions, 'label');

            // Add rule type selector
            $ruleHtml = Craft::$app->getView()->renderTemplate('_includes/forms/select', [
                'name' => 'type',
                'options' => $ruleTypeOptions,
                'value' => $ruleClass,
                'class' => '',
                'inputAttributes' => [
                    'hx-post' => UrlHelper::actionUrl('conditions/render'),
                ],
            ]);
            $ruleHtml = Html::tag('div', $ruleHtml, ['class' => 'condition-rule-type']);
            $ruleHtml .= Html::hiddenInput('uid', $rule->uid);

            // Get rule input html
            $ruleHtml .= Html::tag('div', $rule->getHtml(), ['class' => 'flex-grow']);

            // Add delete button
            $deleteButtonAttr = [
                'class' => 'delete icon',
                'hx-vals' => '{"uid": "' . $rule->uid . '"}',
                'hx-post' => UrlHelper::actionUrl('conditions/remove-rule'),
                'title' => Craft::t('app', 'Delete'),
            ];
            $deleteButton = Html::tag('a', '', $deleteButtonAttr);
            $ruleHtml .= Html::tag('div', $deleteButton);

            // Namespace the rule
            $ruleHtml = Craft::$app->getView()->namespaceInputs(function() use ($ruleHtml) {
                return $ruleHtml;
            }, "conditionRules[$rule->uid]");

            $draggableHandle = Html::tag('a', '', ['class' => 'move icon draggable-handle']);

            $allRulesHtml .= Html::tag('div',
                $draggableHandle . $ruleHtml,
                ['class' => 'flex draggable']
            );
        }

        $allRulesHtml = Html::namespaceHtml($allRulesHtml, $this->handle);

        // Sortable rules div
        $html .= Html::tag('div', $allRulesHtml, [
                'class' => 'sortable',
                'hx-post' => UrlHelper::actionUrl('conditions/render'),
                'hx-trigger' => 'end' // sortable library triggers this event
            ]
        );

        if (count($this->getConditionRuleTypes()) > 0) {
            $addButtonAttr = [
                'class' => 'btn add icon',
                'hx-post' => UrlHelper::actionUrl('conditions/add-rule'),
            ];
            $addButton = Html::tag('button', $this->getAddRuleLabel(), $addButtonAttr);
            $html .= Html::tag('div', $addButton, ['class' => 'rightalign']);
        }

        $html .= Html::tag('div',
            Html::tag('pre', Json::encode($this->getConfig(), JSON_PRETTY_PRINT)),
            ['class' => 'pane']
        );

        $html .= Html::endTag('form');
        return $html;
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        return [
            [['conditionRules'], 'safe']
        ];
    }
}
