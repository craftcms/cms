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
 * @property Collection $conditionRules
 * @property string[] $conditionRuleTypes
 * @property-read string $addRuleLabel
 * @property-read array $config
 * @property-read string $html
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
     * @var array The available rule types for this condition.
     * @see getConditionRuleTypes()
     * @see setConditionRuleTypes()
     */
    private array $_conditionRuleTypes;

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
        if (!isset($this->_conditionRuleTypes)) {
            $conditionRuleTypes = $this->conditionRuleTypes();

            // Give plugins a chance to modify them
            $event = new RegisterConditionRuleTypesEvent([
                'conditionRuleTypes' => $conditionRuleTypes,
            ]);

            $this->trigger(self::EVENT_REGISTER_CONDITION_RULE_TYPES, $event);
            $this->_conditionRuleTypes = $event->conditionRuleTypes;
        }

        return $this->_conditionRuleTypes;
    }

    /**
     * @inheritdoc
     */
    public function setConditionRuleTypes(array $conditionRuleTypes = []): void
    {
        $this->_conditionRuleTypes = $conditionRuleTypes;
    }

    /**
     * Sets the available rule types and returns self
     *
     * @param string[] $conditionRuleTypes
     * @return self
     * @see setConditionRuleTypes()
     */
    public function withConditionRuleTypes(array $conditionRuleTypes = []): self
    {
        $this->setConditionRuleTypes($conditionRuleTypes);

        return $this;
    }

    /**
     * Returns the rule types for this condition.
     *
     * Conditions should override this method instead of [[getConditionRuleTypes()]]
     * so [[EVENT_REGISTER_CONDITION_RULE_TYPES]] handlers can modify the class-defined rule types.
     *
     * @return string[]
     */
    abstract protected function conditionRuleTypes(): array;

    /**
     * @inheritdoc
     */
    public function getConditionRules(): Collection
    {
        return $this->_conditionRules;
    }

    /**
     * Sets the rules this condition should be configured with.
     *
     * @param ConditionRuleInterface[]|array[] $rules
     * @throws InvalidArgumentException|InvalidConfigException if any of the rules are not selectable
     */
    public function setConditionRules(array $rules): void
    {
        $allRules = $rules; //easier xdebug
        $this->_conditionRules = new Collection(array_map(function($rule) {
            if (is_array($rule)) {
                $rule = Craft::$app->getConditions()->createConditionRule($rule);
            }
            if (!$rule->isSelectable() || !in_array(get_class($rule), $this->getConditionRuleTypes())) {
                throw new InvalidArgumentException('Invalid condition rule');
            }
            $rule->setCondition($this);
            return $rule;
        }, $allRules));
    }

    /**
     * Adds a rule to the condition.
     *
     * @param ConditionRuleInterface $rule
     * @throws InvalidArgumentException if the rule is not selectable
     */
    public function addConditionRule(ConditionRuleInterface $rule): void
    {
        if (!$rule->isSelectable() || !in_array(get_class($rule), $this->getConditionRuleTypes())) {
            throw new InvalidArgumentException('Invalid condition rule');
        }

        $this->getConditionRules()->add($rule);
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
        $isHtmxRequest = Craft::$app->getRequest()->getHeaders()->has('HX-Request');
        $view = Craft::$app->getView();
        $view->registerAssetBundle(ConditionBuilderAsset::class);

        // Set defaults
        $options['mainTag'] = $options['mainTag'] ?? 'form';
        $options['sortable'] = $options['sortable'] ?? true;
        $options['baseInputName'] = $options['baseInputName'] ?? 'condition[' . $this->uid . ']';
        $options['mainId'] = isset($options['mainId']) ? $view->namespaceInputId($options['mainId']) : $view->namespaceInputId($options['baseInputName']);

        // Main Condition tag, and Htmx inheritable options
        $html = Html::beginTag($options['mainTag'], [
            'id' => $options['mainId'],
        ]);

        $html .= Html::beginTag('div', [
            'class' => 'condition-main',
            'hx' => [
                'target' => "#" . $options['mainId'], // replace self
                'indicator' => '#indicator-' . $options['mainId'], // ID of the spinner
                'include' => "#" . $options['mainId'], // In case we are in a non form container
                'vals' => [
                    'options' => Json::encode($options),
                    'conditionRuleTypes' => Json::encode($this->getConditionRuleTypes()),
                ], // We want this data sent outside of the namespaced input
            ],
        ]);

        // Condition hidden inputs

        $html .= Html::hiddenInput($options['baseInputName'] . '[uid]', $this->uid);
        $html .= Html::hiddenInput($options['baseInputName'] . '[type]', get_class($this));

        // Start rule js buffer
        $view->startJsBuffer();

        $allRulesHtml = '';
        $ruleCount = 0;
        /** @var ConditionRuleInterface $rule */
        foreach ($this->getConditionRules() as $rule) {
            $ruleCount++;
            $ruleHtml = Craft::$app->getView()->namespaceInputs(function() use ($rule, $options) {

                $moveButton = $options['sortable'] ? Html::tag('a', '', ['class' => 'move icon draggable-handle']) : '';
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
                $switcherHtml = Craft::$app->getView()->renderTemplate('_includes/forms/select', [
                    'name' => 'type',
                    'options' => $ruleTypeOptions,
                    'value' => $ruleClass,
                    'inputAttributes' => [
                        'hx' => [
                            'post' => UrlHelper::actionUrl('conditions/render'),
                        ],
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
                    'title' => Craft::t('app', 'Delete'),
                    'hx' => [
                        'vals' => '{"uid": "' . $rule->uid . '"}',
                        'post' => UrlHelper::actionUrl('conditions/remove-rule'),
                    ],
                ];
                $deleteButton = Html::tag('a', '', $deleteButtonAttr);
                $ruleHtml .= Html::tag('div', $deleteButton, ['id' => 'rule-actions', 'class' => 'rule-actions']);

                return Html::tag('div', $ruleHtml, [
                    'id' => 'condition-rule',
                    'class' => 'condition-rule flex draggable'
                ]);
            }, $options['baseInputName'] . "[conditionRules][$ruleCount]");

            $allRulesHtml .= $ruleHtml;
        }

        $rulesJs = $view->clearJsBuffer(false);

        // Sortable rules div
        $html .= Html::tag('div', $allRulesHtml, [
                'id' => 'condition-rules',
                'class' => 'condition sortable',
                'hx' => [
                    'post' => UrlHelper::actionUrl('conditions/render'),
                    'trigger' => 'end', // sortable library triggers this event
                ],
            ]
        );

        $footerContent = '';

        $disabled = (count($this->getConditionRuleTypes()) == 0) ? ' disabled' : '';
        $addButtonAttr = [
            'class' => 'btn add icon' . $disabled,
            'hx' => [
                'post' => UrlHelper::actionUrl('conditions/add-rule'),
            ],
        ];
        $footerContent .= Html::tag('button', $this->getAddRuleLabel(), $addButtonAttr);

        // Main loading indicator spinner
        $footerContent .= Html::tag('div', '', [
            'class' => 'htmx-indicator spinner',
            'id' => 'indicator-' . $options['mainId'],
        ]);


        $html .= Html::tag('div', $footerContent, [
            'class' => ['condition-footer', 'flex', 'flex-nowrap'],
        ]);

        // Add inline script tag
        if ($isHtmxRequest && $rulesJs) {
            $html .= html::tag('script', $rulesJs, ['type' => 'text/javascript']);
        } elseif ($rulesJs) {
            $view->registerJs($rulesJs);
        }

        if (!$isHtmxRequest) {
            $view->registerJs("htmx.process(htmx.find('#" . $options['mainId'] . "'));");
            $view->registerJs("htmx.trigger(htmx.find('#" . $options['mainId'] . "'), 'htmx:load');");
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

        $html .= Html::endTag('div'); //condition-main
        $html .= Html::endTag($options['mainTag']);

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
