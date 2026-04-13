<?php

declare(strict_types=1);

namespace CraftCms\Cms\Condition;

use Craft;
use craft\web\assets\conditionbuilder\ConditionBuilderAsset;
use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Condition\Contracts\ConditionInterface;
use CraftCms\Cms\Condition\Contracts\ConditionRuleInterface;
use CraftCms\Cms\Condition\Events\RegisterConditionRules;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Conditions;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Url;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Override;
use RuntimeException;
use Throwable;

use function CraftCms\Cms\t;

abstract class BaseCondition extends Component implements ConditionInterface
{
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
     * @var array The condition’s portable config
     */
    public array $config {
        get => $this->getConfig();
    }

    /**
     * @see getConditionRules()
     * @see setConditionRules()
     */
    private Collection $_conditionRules;

    /**
     * @var ConditionRuleInterface[] The rules this condition is configured with
     */
    public array $conditionRules {
        get => $this->getConditionRules();
        set {
            $this->setConditionRules($value);
        }
    }

    /**
     * @var ConditionRuleInterface[]|null The selectable condition rules for this condition.
     *
     * @see getSelectableConditionRules()
     */
    private ?array $_selectableConditionRules = null;

    /**
     * @var string The HTML for the condition builder, including its outer container element
     */
    public string $builderHtml {
        get => $this->getBuilderHtml();
    }

    /**
     * @var string The inner HTML for the condition builder, excluding its outer container element
     */
    public string $builderInnerHtml {
        get => $this->getBuilderInnerHtml();
    }

    public function __construct(array $config = [])
    {
        parent::__construct($config);

        if (! isset($this->id)) {
            $this->id = 'condition'.mt_rand();
        }

        if (! isset($this->addRuleLabel)) {
            $this->addRuleLabel = t('Add a rule');
        }

        if (! isset($this->_conditionRules)) {
            $this->setConditionRules([]);
        }
    }

    public function createConditionRule(array|string $config): ConditionRuleInterface
    {
        if (is_string($config)) {
            $config = ['class' => $config];
        }

        // Set the condition before anything else
        $config = ['condition' => $this] + $config;

        return Conditions::createConditionRule($config);
    }

    final public function getSelectableConditionRules(): array
    {
        if (! isset($this->_selectableConditionRules)) {
            $rules = $this->selectableConditionRules();

            event($event = new RegisterConditionRules(
                conditionRules: $rules,
            ));

            $rules = $event->conditionRules;

            $this->_selectableConditionRules = Collection::make($rules)
                ->keyBy(fn ($type) => is_string($type) ? $type : Json::encode($type))
                ->map(fn ($type) => $this->createConditionRule($type))
                ->filter(fn (ConditionRuleInterface $rule) => $this->isConditionRuleSelectable($rule))
                ->all();
        }

        return $this->_selectableConditionRules;
    }

    /**
     * Returns the selectable rules for this condition.
     *
     * Conditions should override this method instead of {@see getSelectableConditionRules()}
     * so {@see RegisterConditionRules} handlers can modify the class-defined rules.
     *
     * Rules should be defined as either the class name or an array with a `class` key set to the class name.
     *
     * @return string[]|array{class:string}[]
     */
    abstract protected function selectableConditionRules(): array;

    /**
     * Returns whether the given rule should be selectable by the condition builder.
     *
     * @param  ConditionRuleInterface  $rule  The rule in question
     */
    protected function isConditionRuleSelectable(ConditionRuleInterface $rule): bool
    {
        if (! $rule->isSelectable()) {
            return false;
        }

        if ($this->forProjectConfig && ! $rule::supportsProjectConfig()) {
            return false;
        }

        return true;
    }

    public function getConditionRules(): array
    {
        return $this->_conditionRules->all();
    }

    public function setConditionRules(array $rules): void
    {
        $this->_conditionRules = Collection::make();
        $projectConfig = app(ProjectConfig::class);

        foreach ($rules as $rule) {
            if (! $rule instanceof ConditionRuleInterface) {
                try {
                    $rule = $this->createConditionRule($rule);
                } catch (InvalidArgumentException $e) {
                    Log::warning("Invalid condition rule: {$e->getMessage()}");

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

    public function addConditionRule(ConditionRuleInterface $rule): void
    {
        if (! $this->validateConditionRule($rule)) {
            throw new InvalidArgumentException('Invalid condition rule');
        }

        $rule->setCondition($this);
        $this->_conditionRules->add($rule);

        // Clear caches
        $this->_selectableConditionRules = null;
    }

    /**
     * Ensures that a rule can be added to this condition.
     */
    protected function validateConditionRule(ConditionRuleInterface $rule): bool
    {
        if (! $rule->isSelectable()) {
            return false;
        }

        $ruleClass = $rule::class;

        return array_any($this->getSelectableConditionRules(), fn ($selectableRule) => $ruleClass === $selectableRule::class);
    }

    public function getBuilderHtml(): string
    {
        HtmlStack::jsWithVars(fn ($id) => <<<JS
Craft.initUiElements('#' + $id);
JS, [InputNamespace::namespaceId($this->id)]);

        return Html::tag($this->mainTag, $this->getBuilderInnerHtml(), [
            'id' => $this->id,
            'class' => 'condition-container',
        ]);
    }

    public function getBuilderInnerHtml(bool $autofocusAddButton = false): string
    {
        Craft::$app->getView()->registerAssetBundle(ConditionBuilderAsset::class);
        $namespacedId = InputNamespace::namespaceId($this->id);

        return InputNamespace::namespaceInputs(function () use ($namespacedId, $autofocusAddButton) {
            $isHtmxRequest = request()->headers->has('HX-Request');
            $selectableRules = $this->getSelectableConditionRules();
            $allRulesHtml = '';
            $ruleNum = 1;

            // Start rule js buffer
            HtmlStack::startJsBuffer();

            $html = Html::beginTag('div', [
                'class' => ['condition-main'],
                'hx' => [
                    'ext' => 'craft-cp, craft-condition',
                    'target' => "#$namespacedId", // replace self
                    'include' => "#$namespacedId", // In case we are in a non form container
                    'indicator' => sprintf('#%s', InputNamespace::namespaceId("$this->id-spinner")),
                ],
                'data' => [
                    'condition-config' => Json::encode(array_merge($this->getBuilderConfig(), [
                        'class' => static::class,
                        'id' => $namespacedId,
                        'name' => InputNamespace::get(),
                        'mainTag' => $this->mainTag,
                        'sortable' => $this->sortable,
                        'forProjectConfig' => $this->forProjectConfig,
                        'addRuleLabel' => $this->addRuleLabel,
                    ])),
                ],
            ]);

            $html .= Html::hiddenInput('class', static::class);
            $html .= Html::hiddenInput('config', Json::encode($this->getBuilderConfig()));

            foreach ($this->getConditionRules() as $rule) {
                try {
                    $allRulesHtml .= InputNamespace::namespaceInputs(function () use ($rule, $ruleNum, $selectableRules) {
                        $ruleHtml =
                            Html::tag('legend', t('Condition {num, number}', [
                                'num' => $ruleNum,
                            ]), [
                                'class' => 'visually-hidden',
                            ]).
                            Html::hiddenInput('uid', $rule->uid).
                            Html::hiddenInput('class', $rule::class);

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
                            Html::beginTag('div', ['class' => 'rule-switcher']).
                            Html::hiddenLabel(t('Rule Type'), 'type', [
                                'id' => $labelId,
                            ]).
                            $this->_ruleTypeMenu($selectableRules, $rule, $ruleValue, [
                                'aria' => [
                                    'labelledby' => $labelId,
                                ],
                            ]).
                            Html::endTag('div').
                            // Rule HTML
                            Html::tag('div', $rule->getHtml(), [
                                'class' => ['rule-body', 'flex-grow'],
                            ]).
                            // Remove button
                            Html::beginTag('div', [
                                'class' => ['rule-actions'],
                            ]).
                            Html::button('', [
                                'class' => ['delete', 'icon'],
                                'title' => t('Remove'),
                                'aria' => [
                                    'label' => t('Remove'),
                                ],
                                'hx' => [
                                    'vals' => ['uid' => $rule->uid],
                                    'post' => Url::actionUrl('conditions/remove-rule'),
                                ],
                            ]).
                            Html::endTag('div');

                        return Html::tag('fieldset', $ruleHtml, [
                            'class' => ['condition-rule', 'flex', 'flex-start', 'draggable'],
                        ]);
                    }, 'conditionRules['.$ruleNum.']');
                } catch (Throwable) {
                    // The rule is misconfigured
                    continue;
                }

                $ruleNum++;
            }

            $rulesJs = HtmlStack::clearJsBuffer(false);

            // Sortable rules div
            $html .= Html::tag('div', $allRulesHtml, [
                'class' => array_filter([
                    'condition',
                    $this->sortable ? 'sortable' : null,
                ]),
                'hx' => [
                    'post' => Url::actionUrl('conditions/render'),
                    'trigger' => 'end', // sortable library triggers this event
                ],
            ]
            );

            $html .=
                Html::beginTag('div', [
                    'class' => ['condition-footer', 'flex', 'flex-nowrap'],
                ]).
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
                ]).
                Html::tag('div', '', [
                    'id' => "$this->id-spinner",
                    'class' => ['spinner'],
                ]).
                Html::endTag('div'); // flex-nowrap

            if ($rulesJs) {
                if ($isHtmxRequest) {
                    $html .= Html::tag('script', $rulesJs, ['type' => 'text/javascript']);
                } else {
                    HtmlStack::js($rulesJs);
                }
            }

            // Add head and foot/body scripts to html returned so crafts htmx condition builder can insert them into the DOM
            // If this is not an htmx request, don't add scripts, since they will be in the page anyway.
            if ($isHtmxRequest) {
                if ($bodyHtml = HtmlStack::bodyHtml()) {
                    $html .= Html::tag('template', $bodyHtml, [
                        'class' => ['hx-body-html'],
                    ]);
                }
                if ($headHtml = HtmlStack::headHtml()) {
                    $html .= Html::tag('template', $headHtml, [
                        'class' => ['hx-head-html'],
                    ]);
                }
            } else {
                HtmlStack::jsWithVars(
                    fn ($containerSelector) => <<<JS
htmx.process(htmx.find($containerSelector))
htmx.trigger(htmx.find($containerSelector), 'htmx:load')
JS,
                    [sprintf('#%s', $namespacedId)]
                );
            } // condition-main

            return $html.Html::endTag('div');
        }, $this->name);
    }

    /**
     * @param  ConditionRuleInterface[]  $selectableRules
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
            $key = $label.($hint !== null ? " - $hint" : '');
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
            $key = $label.($hint !== null ? " - $hint" : '');
            $groupLabel = $selectableRule->getGroupLabel() ?? '__UNGROUPED__';

            if (! isset($labelsByGroup[$groupLabel][$key])) {
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
            $ungroupedRuleTypeOptions = $groupedRuleTypeOptions;
            Arr::forget($ungroupedRuleTypeOptions, '__UNGROUPED__');
            $groupedRuleTypeOptions = array_merge(['__UNGROUPED__' => $ungroupedRuleTypeOptions], $groupedRuleTypeOptions);
        }

        $optionsHtml = '';

        foreach ($groupedRuleTypeOptions as $groupLabel => $groupRuleTypeOptions) {
            if ($groupLabel !== '__UNGROUPED__') {
                $optionsHtml .= Html::tag('hr', attributes: ['class' => 'padded']).
                    Html::tag('h6', Html::encode($groupLabel), ['class' => 'padded']);
            }
            $groupRuleTypeOptions = Collection::make($groupRuleTypeOptions)
                ->sortBy(['label', 'hint'])
                ->all();
            $optionsHtml .=
                Html::beginTag('ul', ['class' => 'padded']).
                implode("\n", array_map(function (array $option) use ($ruleValue) {
                    $html = Html::beginTag('li');

                    $label = Html::encode($option['label']);
                    if ($option['showHint'] && $option['hint'] !== null) {
                        $label .= ' '.
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

                    return $html.Html::endTag('li');
                },
                    $groupRuleTypeOptions)).
                Html::endTag('ul');
        }

        $buttonId = "$this->id-type-btn";
        $menuId = "$this->id-type-menu";
        $inputId = "$this->id-type-input";

        HtmlStack::jsWithVars(
            fn ($buttonId, $inputId) => <<<JS
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
                InputNamespace::namespaceId($buttonId),
                InputNamespace::namespaceId($inputId),
            ]
        );

        return
            Html::button(Html::encode($rule?->getLabel() ?? $this->addRuleLabel), Arr::merge([
                'id' => $buttonId,
                'class' => ['btn', 'menubtn', 'wrap'],
                'autofocus' => $rule?->getAutofocus(),
            ], $buttonAttributes)).
            Html::tag('div', $optionsHtml, [
                'id' => $menuId,
                'class' => 'menu',
            ]).
            Html::hiddenInput($rule ? 'type' : 'new-rule-type', $ruleValue, [
                'id' => $inputId,
                'hx' => [
                    'post' => Url::actionUrl('conditions/render'),
                ],
            ]);
    }

    #[Override]
    public function getRules(): array
    {
        return [
            'conditionRules' => ['nullable'],
        ];
    }

    public function getBuilderConfig(): array
    {
        return $this->config();
    }

    final public function getConfig(): array
    {
        return array_merge($this->config(), [
            'class' => static::class,
            'conditionRules' => $this->_conditionRules
                ->map(function (ConditionRuleInterface $rule) {
                    try {
                        return $rule->getConfig();
                    } catch (RuntimeException) {
                        // The rule is misconfigured
                        return null;
                    }
                })
                ->filter(fn (?array $config) => $config !== null)
                ->values()
                ->all(),
        ]);
    }

    /**
     * Returns the base config that should be maintained by the builder and included in the condition’s portable config.
     */
    protected function config(): array
    {
        return [];
    }
}
