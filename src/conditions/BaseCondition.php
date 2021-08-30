<?php

namespace craft\conditions;

use Craft;
use craft\base\Component;
use craft\events\DefineConditionRuleTypesEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use Illuminate\Support\Collection;

/**
 *
 * @property-read string $addRuleLabel
 * @property-read array $config
 * @property-read string $html
 * @property Collection $conditionRules
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0
 */
abstract class BaseCondition extends Component
{
    /**
     * @event DefineConditionRuleTypesEvent The event that is triggered when defining the condition rule types
     * @see conditionRuleTypes()
     * @since 4.0
     */
    public const EVENT_DEFINE_CONDITION_RULE_TYPES = 'defineConditionRuleTypes';

    /**
     * @var Collection
     */
    private Collection $_conditionRules;

    /**
     * @var string
     */
    public string $handle;

    /**
     * @inheritDoc
     */
    public function init(): void
    {
        if (!isset($this->_conditionRules)) {
            $this->_conditionRules = new Collection();
        }
    }

    /**
     * @inheritDoc
     */
    public function attributes()
    {
        $attributes = parent::attributes();
        $attributes[] = 'conditionRules';
        $attributes[] = 'handle';

        return $attributes;
    }

    /**
     * @return string
     */
    public function getAddRuleLabel(): string
    {
        return Craft::t('app', 'Add Rule');
    }

    /**
     * Returns the condition rule types for this condition
     *
     * Condition rule should override this method instead of [[conditionRuleTypes()]]
     * so [[EVENT_DEFINE_CONDITION_RULE_TYPES]] handlers can modify the class-defined condition rule types.
     *
     * @return array
     * @since 4.0
     */
    abstract protected function defineConditionRuleTypes(): array;

    /**
     * Returns the condition rule types for this condition
     *
     * @return array Condition rule types
     */
    public function conditionRuleTypes(): array
    {
        $conditionRuleTypes = $this->defineConditionRuleTypes();

        // Give plugins a chance to modify them
        $event = new DefineConditionRuleTypesEvent([
            'conditionRuleTypes' => $conditionRuleTypes,
        ]);

        $this->trigger(self::EVENT_DEFINE_CONDITION_RULE_TYPES, $event);

        return $event->conditionRuleTypes;
    }

    /**
     * Returns the condition rule types available based on the current state of the condition.
     *
     * @return array Array of condition classes
     */
    public function availableRuleTypes(): array
    {
        return $this->conditionRuleTypes();
    }

    /**
     * Returns all available condition rule options for use in a select
     *
     * @return array Array of condition classes available to add to the condition
     */
    public function availableRuleTypesOptions(): array
    {
        $rules = $this->availableRuleTypes();
        $options = [];
        foreach ($rules as $rule) {
            /** @var $rule string */
            $options[$rule] = $rule::displayName();
        }

        return $options;
    }

    /**
     * Returns all condition rules
     *
     * @return Collection
     */
    public function getConditionRules(): Collection
    {
        return $this->_conditionRules;
    }

    /**
     * Sets the condition rules
     *
     * @param BaseConditionRule[]|array $rules
     * @return void
     * @throws \yii\base\InvalidConfigException
     */
    public function setConditionRules(array $rules): void
    {
        $conditionRules = [];
        foreach ($rules as $rule) {
            if (is_array($rule)) {
                $conditionRules[] = Craft::$app->getConditions()->createConditionRule($rule);
            } elseif ($rule instanceof BaseConditionRule) {
                $conditionRules[] = $rule;
            }
        }

        $this->_conditionRules = new Collection($conditionRules);
    }

    /**
     * Add a Rule to the Condition
     *
     * @param BaseConditionRule $conditionRule
     */
    public function addConditionRule(BaseConditionRule $conditionRule): void
    {
        $this->_conditionRules->add($conditionRule);
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        $config = [
            'type' => get_class($this),
            'handle' => $this->handle,
            'conditionRules' => []
        ];

        foreach ($this->getConditionRules() as $conditionRule) {
            $config['conditionRules'][] = $conditionRule->getConfig();
        }

        return $config;
    }

    /**
     * Renders the condition
     *
     * @return string
     */
    public function getHtml(): string
    {
        $conditionId = Html::namespaceId('condition', $this->handle);
        $indicatorId = Html::namespaceId('indicator' , $this->handle);

        // Main Condition tag, and htmx inheritable options
        $attr = Html::renderTagAttributes([
            'id' => 'condition',
            'class' => 'pane',
            'hx-target' => '#' . $conditionId, // replace self
            'hx-swap' => 'outerHTML', // replace this tag with the response
            'hx-indicator' => '#' . $indicatorId, // ID of the spinner
        ]);
        $html = "<form $attr>";

        // Loading indicator
        $attr = Html::renderTagAttributes(['id' => 'indicator', 'class' => 'htmx-indicator spinner']);
        $html .= "<div $attr></div>";

        // Condition hidden inputs
        $html .= Html::hiddenInput('handle', $this->handle);
        $html .= Html::hiddenInput('type', get_class($this));
        $html = Html::namespaceHtml($html, $this->handle);

        $html .= Html::csrfInput();
        $html .= Html::hiddenInput('conditionLocation', $this->handle);

        $allRulesHtml = '';
        /** @var BaseConditionRule $rule */
        foreach ($this->_conditionRules as $rule) {
            // Rules types available
            $ruleClass = get_class($rule);
            $availableRules = $this->availableRuleTypesOptions();
            ArrayHelper::remove($availableRules, $ruleClass); // since we are adding it, remove it so we don't have duplicates
            $availableRules[$ruleClass] = $rule::displayName(); // should always be in the list since it is the current rule

            // Add rule type selector
            $ruleHtml = Craft::$app->getView()->renderTemplate('_includes/forms/select', [
                'name' => 'type',
                'options' => $availableRules,
                'value' => $ruleClass,
                'class' => '',
                'inputAttributes' => [
                    'hx-post' => UrlHelper::actionUrl('conditions/render'),
                ]
            ]);
            $ruleHtml = "<div class='condition-rule-type'>" . $ruleHtml . "</div>";
            $ruleHtml .= Html::hiddenInput('uid', $rule->uid);

            // Get rule input html
            $ruleHtml .= "<div class='flex-grow'>" . $rule->getHtml() . "</div>";

            // Add delete button
            $removeButtonAttr = Html::renderTagAttributes([
                'class' => 'delete icon',
                'hx-vals' => '{"uid": "' . $rule->uid . '"}',
                'hx-post' => UrlHelper::actionUrl('conditions/remove-rule'),
            ]);
            $ruleHtml .= "<div><a title='" . Craft::t('app', 'Delete') . "' $removeButtonAttr></a></div>";

            // Namespace the rule
            $ruleHtml = Html::namespaceHtml($ruleHtml, "conditionRules[$rule->uid]");

            $allRulesHtml .= "<div class='flex draggable'><a class='move icon draggable-handle'></a>" . $ruleHtml . '</div>';
        }

        $allRulesHtml = Html::namespaceHtml($allRulesHtml, $this->handle);

        // Sortable rules div
        $html .= Html::tag('div', $allRulesHtml, [
                'class' => 'sortable',
                'hx-post' => UrlHelper::actionUrl('conditions/render'),
                'hx-trigger' => 'end'
            ]
        );

        if (count($this->availableRuleTypes()) > 0) {
            $addButtonAttr = Html::renderTagAttributes([
                'class' => 'btn add icon',
                'hx-post' => UrlHelper::actionUrl('conditions/add-rule'),
            ]);
            $html .= "<div class='rightalign'><button $addButtonAttr>" . $this->getAddRuleLabel() . "</button></div>";
        }
        $html .= "</form>";

        $html .= "<div class='pane'><pre>" . Json::encode($this->getConfig(), JSON_PRETTY_PRINT) . "</pre></div>";

        return $html;
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        return [['conditionRules', 'safe']];
    }
}