<?php

namespace craft\base\conditions;

use Craft;
use craft\base\Component;
use craft\events\RegisterConditionRuleTypesEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\web\assets\conditionbuilder\ConditionBuilderAsset;
use Illuminate\Support\Collection;
use yii\base\InvalidArgumentException;

/**
 * BaseCondition provides a base implementation for conditions.
 *
 * @property ConditionRuleInterface[] $conditionRules The rules this condition is configured with
 * @property-read array $config The condition’s portable config
 * @property-read string $builderHtml The HTML for the condition builder, including its outer container element
 * @property-read string $builderInnerHtml The inner HTML for the condition builder, excluding its outer container element
 * @property-read string[]|array{class: string}[] $conditionRuleTypes The available rule types for this condition
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
     * @var string The condition builder container tag name
     */
    public string $mainTag = 'form';

    /**
     * @var string|null The ID of the condition builder
     */
    public ?string $id = null;

    /**
     * @var string The root input name of the condition builder
     */
    public string $name = 'condition';

    /**
     * @var bool Whether the condition rules should be sortable
     */
    public bool $sortable = true;

    /**
     * @var bool Whether the condition will be stored in the project config
     */
    public bool $forProjectConfig = false;

    /**
     * @var string|null The “Add a rule” button label.
     */
    public ?string $addRuleLabel = null;

    /**
     * @var Collection
     * @see getConditionRules()
     * @see setConditionRules()
     */
    private Collection $_conditionRules;

    /**
     * @var string[]|array[] The available rule types for this condition.
     * @phpstan-var string[]|array{class:string}[]|array{type:string}[]
     * @see getConditionRuleTypes()
     */
    private array $_conditionRuleTypes;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        if (!isset($this->id)) {
            $this->id = 'condition' . mt_rand();
        }

        if (!isset($this->addRuleLabel)) {
            $this->addRuleLabel = Craft::t('app', 'Add a rule');
        }

        if (!isset($this->_conditionRules)) {
            $this->setConditionRules([]);
        }
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
     * Returns the rule types for this condition.
     *
     * Conditions should override this method instead of [[getConditionRuleTypes()]]
     * so [[EVENT_REGISTER_CONDITION_RULE_TYPES]] handlers can modify the class-defined rule types.
     *
     * Rule types should be defined as either the class name or an array with a `class` key set to the class name.
     *
     * @return string[]|array[]
     * @phpstan-return string[]|array{class:string}[]
     */
    abstract protected function conditionRuleTypes(): array;

    /**
     * @inheritdoc
     */
    public function getSelectableConditionRules(): array
    {
        $conditionsService = Craft::$app->getConditions();
        return Collection::make($this->getConditionRuleTypes())
            ->keyBy(fn($type) => is_string($type) ? $type : Json::encode($type))
            ->map(fn($type) => $conditionsService->createConditionRule($type))
            ->filter(fn(ConditionRuleInterface $rule) => $this->isConditionRuleSelectable($rule))
            ->all();
    }

    /**
     * Returns whether the given rule should be selectable by the condition builder.
     *
     * @param ConditionRuleInterface $rule The rule in question
     */
    protected function isConditionRuleSelectable(ConditionRuleInterface $rule): bool
    {
        if (!$rule->isSelectable()) {
            return false;
        }

        if ($this->forProjectConfig && !$rule::supportsProjectConfig()) {
            return false;
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function getConditionRules(): array
    {
        return $this->_conditionRules->all();
    }

    /**
     * @inheritdoc
     */
    public function setConditionRules(array $rules): void
    {
        $conditionsService = Craft::$app->getConditions();
        $this->_conditionRules = Collection::make($rules)
            ->map(function($rule) use ($conditionsService) {
                if ($rule instanceof ConditionRuleInterface) {
                    return $rule;
                }
                try {
                    return $conditionsService->createConditionRule($rule);
                } catch (InvalidArgumentException $e) {
                    Craft::warning("Invalid condition rule: {$e->getMessage()}");
                    return null;
                }
            })
            ->filter(fn(?ConditionRuleInterface $rule) => $rule && $this->validateConditionRule($rule))
            ->each(fn(ConditionRuleInterface $rule) => $rule->setCondition($this));
    }

    /**
     * @inheritdoc
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
     * Ensures that a rule can be added to this condition.
     *
     * @param ConditionRuleInterface $rule
     * @return bool
     */
    protected function validateConditionRule(ConditionRuleInterface $rule): bool
    {
        if (!$rule->isSelectable()) {
            return false;
        }

        foreach ($this->getConditionRuleTypes() as $type) {
            if (is_array($type)) {
                $type = $type['class'];
            }
            if ($type === get_class($rule)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function getBuilderHtml(): string
    {
        return Html::tag($this->mainTag, $this->getBuilderInnerHtml(), [
            'id' => $this->id,
            'class' => 'condition-container',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getBuilderInnerHtml(bool $autofocusAddButton = false): string
    {
        $view = Craft::$app->getView();
        $view->registerAssetBundle(ConditionBuilderAsset::class);
        $namespacedId = $view->namespaceInputId($this->id);

        return $view->namespaceInputs(function() use ($view, $namespacedId, $autofocusAddButton) {
            $isHtmxRequest = Craft::$app->getRequest()->getHeaders()->has('HX-Request');
            $selectableRules = $this->getSelectableConditionRules();
            $allRulesHtml = '';
            $ruleCount = 0;

            // Start rule js buffer
            $view->startJsBuffer();

            $html = Html::beginTag('div', [
                'class' => ['condition-main'],
                'hx' => [
                    'ext' => 'craft-cp, craft-condition',
                    'target' => "#$namespacedId", // replace self
                    'include' => "#$namespacedId", // In case we are in a non form container
                    'indicator' => sprintf('#%s', $view->namespaceInputId('add-btn')),
                ],
                'data' => [
                    'condition-config' => Json::encode(array_merge($this->toArray(), [
                        'id' => $namespacedId,
                        'name' => $view->getNamespace(),
                    ])),
                ],
            ]);

            $html .= Html::hiddenInput('class', get_class($this));
            $html .= Html::hiddenInput('config', Json::encode($this->config()));

            foreach ($this->getConditionRules() as $rule) {
                $ruleCount++;

                $allRulesHtml .= $view->namespaceInputs(function() use ($rule, $ruleCount, $selectableRules) {
                    $ruleHtml =
                        Html::tag('legend', Craft::t('app', 'Condition {num, number}', [
                            'num' => $ruleCount,
                        ]), [
                            'class' => 'visually-hidden',
                        ]) .
                        Html::hiddenInput('uid', $rule->uid) .
                        Html::hiddenInput('class', get_class($rule));

                    if ($this->sortable) {
                        $ruleHtml .= Html::tag('div',
                            Html::tag('a', '', [
                                'class' => ['move', 'icon', 'draggable-handle'],
                            ]),
                            [
                                'class' => ['rule-move'],
                            ]
                        );
                    }

                    $ruleValue = Json::encode($rule->getConfig());
                    $ruleLabel = $rule->getLabel();
                    $ruleGroupLabel = $rule->getGroupLabel() ?? '__UNGROUPED__';

                    $groupedRuleTypeOptions = [
                        $ruleGroupLabel => [
                            ['value' => $ruleValue, 'label' => $ruleLabel],
                        ],
                    ];
                    $labels = [
                        $ruleLabel => true,
                    ];
                    foreach ($selectableRules as $value => $selectableRule) {
                        /** @var ConditionRuleInterface $selectableRule */
                        $label = $selectableRule->getLabel();
                        if (!isset($labels[$label])) {
                            $groupLabel = $selectableRule->getGroupLabel() ?? '__UNGROUPED__';
                            $groupedRuleTypeOptions[$groupLabel][] = compact('value', 'label');
                            $labels[$label] = true;
                        }
                    }

                    // Sort by group label, and then option label
                    ksort($groupedRuleTypeOptions);
                    if (isset($groupedRuleTypeOptions['__UNGROUPED__']) && count($groupedRuleTypeOptions) > 1) {
                        $ungroupedRuleTypeOptions = ArrayHelper::remove($groupedRuleTypeOptions, '__UNGROUPED__');
                        $groupedRuleTypeOptions = array_merge(['__UNGROUPED__' => $ungroupedRuleTypeOptions], $groupedRuleTypeOptions);
                    }

                    $ruleTypeOptions = [];

                    foreach ($groupedRuleTypeOptions as $groupLabel => $groupRuleTypeOptions) {
                        ArrayHelper::multisort($groupRuleTypeOptions, 'label');
                        if ($groupLabel !== '__UNGROUPED__') {
                            $ruleTypeOptions[] = ['optgroup' => $groupLabel];
                        }
                        array_push($ruleTypeOptions, ...$groupRuleTypeOptions);
                    }


                    $ruleHtml .=
                        // Rule type selector
                        Html::beginTag('div', ['class' => 'rule-switcher']) .
                        Html::hiddenLabel(Craft::t('app', 'Rule Type'), 'type') .
                        Cp::selectHtml([
                            'id' => 'type',
                            'name' => 'type',
                            'options' => $ruleTypeOptions,
                            'value' => $ruleValue,
                            'autofocus' => $rule->getAutofocus(),
                            'inputAttributes' => [
                                'hx' => [
                                    'post' => UrlHelper::actionUrl('conditions/render'),
                                ],
                            ],
                        ]) .
                        Html::endTag('div') .
                        // Rule HTML
                        Html::tag('div', $rule->getHtml(), [
                            'id' => 'rule-body',
                            'class' => ['rule-body', 'flex-grow'],
                        ]) .
                        // Remove button
                        Html::beginTag('div', [
                            'id' => 'rule-actions',
                            'class' => ['rule-actions'],
                        ]) .
                        Html::button('', [
                            'type' => 'button',
                            'class' => ['delete', 'icon'],
                            'title' => Craft::t('app', 'Remove'),
                            'hx' => [
                                'vals' => ['uid' => $rule->uid],
                                'post' => UrlHelper::actionUrl('conditions/remove-rule'),
                            ],
                        ]) .
                        Html::endTag('div');

                    return Html::tag('fieldset', $ruleHtml, [
                        'id' => 'condition-rule',
                        'class' => ['condition-rule', 'flex', 'flex-start', 'draggable'],
                    ]);
                }, 'conditionRules[' . $ruleCount . ']');
            }

            $rulesJs = $view->clearJsBuffer(false);

            // Sortable rules div
            $html .= Html::tag('div', $allRulesHtml, [
                    'id' => 'condition-rules',
                    'class' => array_filter([
                        'condition',
                        $this->sortable ? 'sortable' : null,
                    ]),
                    'hx' => [
                        'post' => UrlHelper::actionUrl('conditions/render'),
                        'trigger' => 'end', // sortable library triggers this event
                    ],
                ]
            );

            $html .=
                Html::beginTag('div', [
                    'class' => ['condition-footer', 'flex', 'flex-nowrap'],
                ]) .
                Html::beginTag('button', [
                    'type' => 'button',
                    'id' => 'add-btn',
                    'class' => array_filter([
                        'btn',
                        'add',
                        'icon',
                        'fullwidth',
                        'dashed',
                        empty($selectableRules) ? 'disabled' : null,
                    ]),
                    'autofocus' => $autofocusAddButton,
                    'aria' => [
                        'label' => $this->addRuleLabel,
                    ],
                    'hx' => [
                        'post' => UrlHelper::actionUrl('conditions/add-rule'),
                    ],
                ]) .
                Html::tag('div', Html::encode($this->addRuleLabel), [
                    'class' => 'label',
                ]) .
                Html::tag('div', '', [
                    'class' => ['spinner', 'spinner-absolute'],
                ]) .
                Html::endTag('button') .
                Html::endTag('div');

            if ($rulesJs) {
                if ($isHtmxRequest) {
                    $html .= html::tag('script', $rulesJs, ['type' => 'text/javascript']);
                } else {
                    $view->registerJs($rulesJs);
                }
            }

            // Add head and foot/body scripts to html returned so crafts htmx condition builder can insert them into the DOM
            // If this is not an htmx request, don't add scripts, since they will be in the page anyway.
            if ($isHtmxRequest) {
                if ($bodyHtml = $view->getBodyHtml()) {
                    $html .= html::tag('template', $bodyHtml, [
                        'id' => 'body-html',
                        'class' => ['hx-body-html'],
                    ]);
                }
                if ($headHtml = $view->getHeadHtml()) {
                    $html .= html::tag('template', $headHtml, [
                        'id' => 'head-html',
                        'class' => ['hx-head-html'],
                    ]);
                }
            } else {
                $view->registerJsWithVars(
                    fn($containerSelector) => <<<JS
htmx.process(htmx.find($containerSelector));
htmx.trigger(htmx.find($containerSelector), 'htmx:load');
JS,
                    [sprintf('#%s', $namespacedId)]
                );
            }

            $html .= Html::endTag('div'); //condition-main
            return $html;
        }, $this->name);
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        return [
            [['conditionRules'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function getConfig(): array
    {
        return array_merge($this->config(), [
            'class' => get_class($this),
            'conditionRules' => $this->_conditionRules
                ->map(fn(ConditionRuleInterface $rule) => $rule->getConfig())
                ->values()
                ->all(),
        ]);
    }

    /**
     * Returns the condition’s portable config.
     *
     * @return array
     */
    protected function config(): array
    {
        return [];
    }
}
