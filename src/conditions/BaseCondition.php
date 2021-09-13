<?php

namespace craft\conditions;

use Craft;
use craft\base\Component;
use craft\events\RegisterConditionRuleTypesEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\web\assets\conditionbuilder\ConditionBuilderAsset;
use Illuminate\Support\Collection;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;

/**
 * Base condition class.
 *
 * @property-read string $addRuleLabel
 * @property-read array $config
 * @property-read string $html
 * @property-read string[] $conditionRuleTypes
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
     * @var string
     */
    public string $uid;

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
            $this->setConditionRules([]);
        }

        if (!isset($this->uid)) {
            $this->uid = StringHelper::UUID();
        }
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
     * @inheritdoc
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
     * @throws InvalidArgumentException|InvalidConfigException if any of the rules don’t validate
     */
    public function setConditionRules(array $rules): void
    {
        $this->_conditionRules = new Collection(array_map(function($rule) {
            if (is_array($rule)) {
                $rule = Craft::$app->getConditions()->createConditionRule($rule);
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

        $this->getConditionRules()->add($rule);
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
        $conditionRules = $this->getConditionRules()
            ->map(fn(ConditionRuleInterface $rule) => $rule->getConfig())
            ->all();

        return [
            'type' => get_class($this),
            'uid' => $this->uid,
            'conditionRules' => array_values($conditionRules)
        ];
    }

    /**
     * @inheritdoc
     */
    public function getBuilderHtml(array $options = []): string
    {
        $isHtmxRequest = (bool)Craft::$app->getRequest()->getHeaders()->get('HX-Request', false);

        $options = array_merge([
            'mainTag' => 'form',
            'devMode' => false
        ], $options);

        $view = Craft::$app->getView();
        $view->registerAssetBundle(ConditionBuilderAsset::class);

        $mainId = 'condition-' . $this->uid;
        // Main Condition tag, and Htmx inheritable options
        $html = Html::beginTag($options['mainTag'], [
            'id' => $mainId,
            'hx-target' => 'this', // replace self
            'hx-include' => "#$mainId",//"[name^='condition'][name='options']",
            'hx-swap' => 'outerHTML', // replace this tag with the response
            'hx-indicator' => '#indicator-' . $this->uid, // ID of the spinner
        ]);
        // Loading indicator
        $html .= Html::tag('div', '', ['id' => 'indicator-' . $this->uid, 'class' => 'htmx-indicator spinner']);

        // Builder options
        $html .= Html::hiddenInput('options', Json::encode($options));
        // Condition hidden inputs
        $html .= Html::hiddenInput('condition[uid]', $this->uid);
        $html .= Html::hiddenInput('condition[type]', get_class($this));

        $view->startJsBuffer();

        $allRulesHtml = '';
        foreach ($this->getConditionRules() as $rule) {
            $ruleHtml = Craft::$app->getView()->namespaceInputs(function() use ($rule, $options) {

                $moveButton = Html::tag('a', '', ['class' => 'move icon draggable-handle']);
                $ruleHtml = Html::tag('div', $moveButton, ['id' => 'rule-move', 'class' => 'rule-move']);


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

                // Add rule type selector and uid hidden field
                $ruleBodyId = "#" . Craft::$app->getView()->namespaceInputId('rule-body', Craft::$app->getView()->getNamespace());
                $switcherHtml = Craft::$app->getView()->renderTemplate('_includes/forms/select', [
                    'name' => 'type',
                    'options' => $ruleTypeOptions,
                    'value' => $ruleClass,
                    'inputAttributes' => [
                        'hx-post' => UrlHelper::actionUrl('conditions/render')
                    ],
                ]);
                $switcherHtml .= Html::hiddenInput('uid', $rule->uid);

                $ruleHtml .= Html::tag('div', $switcherHtml, ['id' => 'rule-switcher', 'class' => 'rule-switcher']);

                // Get rule input html
                $ruleHtml .= Html::tag('div', $rule->getHtml($options), ['id' => 'rule-body', 'class' => 'rule-body flex-grow']);

                // Add delete button
                $deleteButtonAttr = [
                    'id' => 'delete',
                    'class' => 'delete icon',
                    'hx-vals' => '{"uid": "' . $rule->uid . '"}',
                    'hx-post' => UrlHelper::actionUrl('conditions/remove-rule'),
                    'title' => Craft::t('app', 'Delete'),
                ];
                $deleteButton = Html::tag('a', '', $deleteButtonAttr);
                $ruleHtml .= Html::tag('div', $deleteButton, ['id' => 'rule-actions', 'class' => 'rule-actions']);

                return Html::tag('div', $ruleHtml, ['id' => 'condition-rule', 'class' => 'condition-rule flex draggable']);
            }, "condition[conditionRules][$rule->uid]");

            $allRulesHtml .= $ruleHtml;
        }

        $rulesJs = $view->clearJsBuffer(false);

        // Sortable rules div
        $html .= Html::tag('div', $allRulesHtml, [
                'id' => 'condition-rules',
                'class' => 'sortable',
                'hx-post' => UrlHelper::actionUrl('conditions/render'),
                'hx-trigger' => 'end' // sortable library triggers this event
            ]
        );

        if (count($this->getConditionRuleTypes()) > 0) {
            $addButtonAttr = [
                'class' => 'btn add icon',
                'hx-confirm' => 'Add a condition?',
                'hx-post' => UrlHelper::actionUrl('conditions/add-rule')
            ];
            $addButton = Html::tag('button', $this->getAddRuleLabel(), $addButtonAttr);
            $html .= Html::tag('div', $addButton, ['class' => 'rightalign']);
        }

        if ($options['devMode'] == true) {
            $html .= Html::tag('div',
                Html::tag('pre', Json::encode($this->getConfig(), JSON_PRETTY_PRINT)),
                ['class' => 'pane']
            );
        }

        // Add inline scripts
        if ($isHtmxRequest && $rulesJs) {
            $html .= html::tag('script', $rulesJs, ['id' => 'inline-script', 'type' => 'text/javascript']);
        } else {
            $js = <<<JS
console.log('Loaded:', '#$mainId');
htmx.logAll();
var conditionBuilder = htmx.find('#$mainId');
// htmx.process();
JS;
            $view->registerJs($js);
            $view->registerJs($rulesJs);
        }

        // Add head and foot/body scripts to html returned so crafts htmx condition builder can insert them into the DOM
        // If this is not an htmx request, don't add scripts, since they will be in the page anyway.
        if ($isHtmxRequest) {
            if ($bodyHtml = $view->getBodyHtml()) {
                $html .= html::tag('template', $bodyHtml, [
                    'id' => 'body-html',
                    'class' => 'hx-body-html',
                ]);
            }
            if ($headHtml = $view->getHeadHtml()) {
                $html .= html::tag('template', $headHtml, [
                    'id' => 'head-html',
                    'class' => 'hx-head-html'
                ]);
            }
        }

        $html .= Html::endTag('form');

        return $html;
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        return [[['uid', 'type', 'conditionRules'], 'safe']];
    }
}
