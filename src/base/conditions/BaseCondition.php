<?php

namespace craft\base\conditions;

use Craft;
use craft\base\Component;
use craft\events\RegisterConditionRulesEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\web\assets\conditionbuilder\ConditionBuilderAsset;
use Illuminate\Support\Collection;
use Throwable;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;

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
     * @event RegisterConditionRulesEvent The event that is triggered when defining the selectable condition rules.
     * @see getSelectableConditionRules()
     */
    public const EVENT_REGISTER_CONDITION_RULES = 'registerConditionRules';

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
     * @var ConditionRuleInterface[]|null The selectable condition rules for this condition.
     * @see getSelectableConditionRules()
     */
    private ?array $_selectableConditionRules = null;

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
    public function createConditionRule(array|string $config): ConditionRuleInterface
    {
        if (is_string($config)) {
            $config = ['class' => $config];
        }

        // Set the condition before anything else
        $config = ['condition' => $this] + $config;

        return Craft::$app->getConditions()->createConditionRule($config);
    }

    /**
     * @inheritdoc
     */
    public function getSelectableConditionRules(): array
    {
        if (!isset($this->_selectableConditionRules)) {
            $rules = $this->selectableConditionRules();

            // Fire a 'registerConditionRules' event
            if ($this->hasEventHandlers(self::EVENT_REGISTER_CONDITION_RULES)) {
                $event = new RegisterConditionRulesEvent([
                    'conditionRules' => $rules,
                ]);
                $this->trigger(self::EVENT_REGISTER_CONDITION_RULES, $event);
                $rules = $event->conditionRules;
            }

            $this->_selectableConditionRules = Collection::make($rules)
                ->keyBy(fn($type) => is_string($type) ? $type : Json::encode($type))
                ->map(fn($type) => $this->createConditionRule($type))
                ->filter(fn(ConditionRuleInterface $rule) => $this->isConditionRuleSelectable($rule))
                ->all();
        }

        return $this->_selectableConditionRules;
    }

    /**
     * Returns the selectable rules for this condition.
     *
     * Conditions should override this method instead of [[getSelectableConditionRules()]]
     * so [[EVENT_REGISTER_CONDITION_RULES]] handlers can modify the class-defined rules.
     *
     * Rules should be defined as either the class name or an array with a `class` key set to the class name.
     *
     * @return string[]|array[]
     * @phpstan-return string[]|array{class:string}[]
     */
    abstract protected function selectableConditionRules(): array;

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
        $this->_conditionRules = Collection::make();
        $projectConfig = Craft::$app->getProjectConfig();

        foreach ($rules as $rule) {
            if (!$rule instanceof ConditionRuleInterface) {
                try {
                    $rule = $this->createConditionRule($rule);
                } catch (InvalidArgumentException $e) {
                    Craft::warning("Invalid condition rule: {$e->getMessage()}");
                    continue;
                }
            }

            // Don't validate the rule when we're applying project config changes.
            // The rule type might depend on something that hasn't been added yet.
            if ($projectConfig->isApplyingExternalChanges || $this->validateConditionRule($rule)) {
                $this->_conditionRules->add($rule);
                $rule->setCondition($this);
            }
        }

        // Clear out our cache of selectable condition rules, in case any additional rules will depend on which
        // rules are already configured.
        $this->_selectableConditionRules = null;
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

        // Clear caches
        $this->_selectableConditionRules = null;
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

        $ruleClass = get_class($rule);

        foreach ($this->getSelectableConditionRules() as $selectableRule) {
            if ($ruleClass === get_class($selectableRule)) {
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
        $view = Craft::$app->getView();
        $view->registerJsWithVars(fn($id) => <<<JS
Craft.initUiElements('#' + $id);
JS, [$view->namespaceInputId($this->id)]);

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
            $ruleNum = 1;

            // Start rule js buffer
            $view->startJsBuffer();

            $html = Html::beginTag('div', [
                'class' => ['condition-main'],
                'hx' => [
                    'ext' => 'craft-cp, craft-condition',
                    'target' => "#$namespacedId", // replace self
                    'include' => "#$namespacedId", // In case we are in a non form container
                    'indicator' => sprintf('#%s', $view->namespaceInputId("$this->id-spinner")),
                ],
                'data' => [
                    'condition-config' => Json::encode(array_merge($this->toArray(), [
                        'id' => $namespacedId,
                        'name' => $view->getNamespace(),
                    ])),
                ],
            ]);

            $html .= Html::hiddenInput('class', get_class($this));
            $html .= Html::hiddenInput('config', Json::encode($this->getBuilderConfig()));

            foreach ($this->getConditionRules() as $rule) {
                try {
                    $allRulesHtml .= $view->namespaceInputs(function() use ($rule, $ruleNum, $selectableRules) {
                        $ruleHtml =
                            Html::tag('legend', Craft::t('app', 'Condition {num, number}', [
                                'num' => $ruleNum,
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
                        $labelId = "$this->id-type-label";

                        $ruleHtml .=
                            // Rule type selector
                            Html::beginTag('div', ['class' => 'rule-switcher']) .
                            Html::hiddenLabel(Craft::t('app', 'Rule Type'), 'type', [
                                'id' => $labelId,
                            ]) .
                            $this->_ruleTypeMenu($selectableRules, $rule, $ruleValue, [
                                'aria' => [
                                    'labelledby' => $labelId,
                                ],
                            ]) .
                            Html::endTag('div') .
                            // Rule HTML
                            Html::tag('div', $rule->getHtml(), [
                                'class' => ['rule-body', 'flex-grow'],
                            ]) .
                            // Remove button
                            Html::beginTag('div', [
                                'class' => ['rule-actions'],
                            ]) .
                            Html::button('', [
                                'class' => ['delete', 'icon'],
                                'title' => Craft::t('app', 'Remove'),
                                'aria' => [
                                  'label' => Craft::t('app', 'Remove'),
                                ],
                                'hx' => [
                                    'vals' => ['uid' => $rule->uid],
                                    'post' => UrlHelper::actionUrl('conditions/remove-rule'),
                                ],
                            ]) .
                            Html::endTag('div');

                        return Html::tag('fieldset', $ruleHtml, [
                            'class' => ['condition-rule', 'flex', 'flex-start', 'draggable'],
                        ]);
                    }, 'conditionRules[' . $ruleNum . ']');
                } catch (InvalidConfigException) {
                    // The rule is misconfigured
                    continue;
                }


                $ruleNum++;
            }

            $rulesJs = $view->clearJsBuffer(false);

            // Sortable rules div
            $html .= Html::tag('div', $allRulesHtml, [
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
                $this->_ruleTypeMenu($selectableRules, buttonAttributes: [
                    'class' => array_filter([
                        'add',
                        'icon',
                        empty($selectableRules) ? 'disabled' : null,
                    ]),
                    'aria' => [
                        'label' => $this->addRuleLabel,
                    ],
                    'autofocus' => $autofocusAddButton,
                ]) .
                Html::tag('div', '', [
                    'id' => "$this->id-spinner",
                    'class' => ['spinner'],
                ]) .
                Html::endTag('div'); // flex-nowrap

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
                        'class' => ['hx-body-html'],
                    ]);
                }
                if ($headHtml = $view->getHeadHtml()) {
                    $html .= html::tag('template', $headHtml, [
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
     * @param ConditionRuleInterface[] $selectableRules
     * @param ConditionRuleInterface|null $rule
     * @param string|null $ruleValue
     * @param array $buttonAttributes
     * @return string
     */
    private function _ruleTypeMenu(
        array $selectableRules,
        ?ConditionRuleInterface $rule = null,
        ?string $ruleValue = null,
        array $buttonAttributes = [],
    ): string {
        $groupedRuleTypeOptions = [];
        $labelsByGroup = [];

        if ($rule) {
            $label = $rule->getLabel();
            $hint = $rule->getLabelHint();
            $showHint = $rule->showLabelHint();
            $key = $label . ($hint !== null ? " - $hint" : '');
            $groupLabel = $rule->getGroupLabel() ?? '__UNGROUPED__';

            $groupedRuleTypeOptions[$groupLabel] = [
                [
                    'label' => $label,
                    'hint' => $hint,
                    'showHint' => $showHint,
                    'value' => $ruleValue,
                ],
            ];
            $labelsByGroup[$groupLabel][$key] = true;
        }

        foreach ($selectableRules as $value => $selectableRule) {
            try {
                $label = $selectableRule->getLabel();
            } catch (Throwable) {
                continue;
            }
            $hint = $selectableRule->getLabelHint();
            $showHint = $selectableRule->showLabelHint();
            $key = $label . ($hint !== null ? " - $hint" : '');
            $groupLabel = $selectableRule->getGroupLabel() ?? '__UNGROUPED__';

            if (!isset($labelsByGroup[$groupLabel][$key])) {
                $groupedRuleTypeOptions[$groupLabel][] = [
                    'label' => $label,
                    'hint' => $hint,
                    'showHint' => $showHint,
                    'value' => $value,
                ];
                $labelsByGroup[$groupLabel][$key] = true;
            }
        }

        // Sort by group label, and then option label
        ksort($groupedRuleTypeOptions);
        if (isset($groupedRuleTypeOptions['__UNGROUPED__']) && count($groupedRuleTypeOptions) > 1) {
            $ungroupedRuleTypeOptions = ArrayHelper::remove($groupedRuleTypeOptions, '__UNGROUPED__');
            $groupedRuleTypeOptions = array_merge(['__UNGROUPED__' => $ungroupedRuleTypeOptions], $groupedRuleTypeOptions);
        }

        $optionsHtml = '';

        foreach ($groupedRuleTypeOptions as $groupLabel => $groupRuleTypeOptions) {
            if ($groupLabel !== '__UNGROUPED__') {
                $optionsHtml .= Html::tag('hr', options: ['class' => 'padded']) .
                    Html::tag('h6', Html::encode($groupLabel), ['class' => 'padded']);
            }
            ArrayHelper::multisort($groupRuleTypeOptions, ['label', 'hint']);
            $optionsHtml .=
                Html::beginTag('ul', ['class' => 'padded']) .
                implode("\n", array_map(function(array $option) use ($ruleValue) {
                    $html = Html::beginTag('li');

                    $label = Html::encode($option['label']);
                    if ($option['showHint'] && $option['hint'] !== null) {
                        $label .= ' ' .
                            Html::tag('span', sprintf('– %s', Html::encode($option['hint'])), [
                                'class' => 'light',
                            ]);
                    }

                    $html .= Html::a($label, options: [
                        'class' => $option['value'] === $ruleValue ? 'sel' : false,
                        'data' => [
                            'value' => $option['value'],
                        ],
                    ]);
                    $html .= Html::endTag('li');

                    return $html;
                },
                    $groupRuleTypeOptions)) .
                Html::endTag('ul');
        }

        $buttonId = "$this->id-type-btn";
        $menuId = "$this->id-type-menu";
        $inputId = "$this->id-type-input";

        $view = Craft::$app->getView();
        $view->registerJsWithVars(
            fn($buttonId, $inputId) => <<<JS
Garnish.requestAnimationFrame(() => {
  const \$button = $('#' + $buttonId);
  \$button.menubtn().data('menubtn').on('optionSelect', event => {
    const \$option = $(event.option);
    \$button.text(\$option.text()).removeClass('add');
    // Don't use data('value') here because it could result in an object if data-value is JSON
    const \$input = $('#' + $inputId).val(\$option.attr('data-value'));
    htmx.trigger(\$input[0], 'change');
  });
});
JS,
            [
                $view->namespaceInputId($buttonId),
                $view->namespaceInputId($inputId),
            ]
        );

        return
            Html::button(Html::encode($rule?->getLabel() ?? $this->addRuleLabel), ArrayHelper::merge([
                'id' => $buttonId,
                'class' => ['btn', 'menubtn', 'wrap'],
                'autofocus' => $rule?->getAutofocus(),
            ], $buttonAttributes)) .
            Html::tag('div', $optionsHtml, [
                'id' => $menuId,
                'class' => 'menu',
            ]) .
            Html::hiddenInput($rule ? 'type' : 'new-rule-type', $ruleValue, [
                'id' => $inputId,
                'hx' => [
                    'post' => UrlHelper::actionUrl('conditions/render'),
                ],
            ]);
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
    public function getBuilderConfig(): array
    {
        return $this->config();
    }

    /**
     * @inheritdoc
     */
    public function getConfig(): array
    {
        return array_merge($this->config(), [
            'class' => get_class($this),
            'conditionRules' => $this->_conditionRules
                ->map(function(ConditionRuleInterface $rule) {
                    try {
                        return $rule->getConfig();
                    } catch (InvalidConfigException) {
                        // The rule is misconfigured
                        return null;
                    }
                })
                ->filter(fn(?array $config) => $config !== null)
                ->values()
                ->all(),
        ]);
    }

    /**
     * Returns the base config that should be maintained by the builder and included in the condition’s portable config.
     *
     * @return array
     */
    protected function config(): array
    {
        return [];
    }
}
