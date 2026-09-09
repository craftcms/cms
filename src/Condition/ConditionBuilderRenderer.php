<?php

declare(strict_types=1);

namespace CraftCms\Cms\Condition;

use CraftCms\Cms\Condition\Contracts\ConditionInterface;
use CraftCms\Cms\Condition\Contracts\ConditionRuleInterface;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\View\LegacyAssets\ConditionBuilderAsset;
use CraftCms\Cms\View\LegacyAssets\InternalAssetRegistry;
use Illuminate\Support\Collection;
use Throwable;

use function CraftCms\Cms\t;

class ConditionBuilderRenderer
{
    public function __construct(private readonly ConditionInterface $condition) {}

    public function render(): string
    {
        HtmlStack::jsWithVars(fn ($id) => <<<JS
Craft.initUiElements('#' + $id);
JS, [InputNamespace::namespaceId($this->condition->id)]);

        return Html::tag($this->condition->mainTag, $this->renderInner(), [
            'id' => $this->condition->id,
            'class' => 'condition-container',
        ]);
    }

    public function renderInner(bool $autofocusAddButton = false): string
    {
        app(InternalAssetRegistry::class)->register(ConditionBuilderAsset::class);
        $namespacedId = InputNamespace::namespaceId($this->condition->id);

        return InputNamespace::namespaceInputs(function () use ($namespacedId, $autofocusAddButton) {
            $isHtmxRequest = request()->headers->has('HX-Request');
            $selectableRules = $this->condition->getSelectableConditionRules();
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
                    'indicator' => sprintf('#%s', InputNamespace::namespaceId("{$this->condition->id}-spinner")),
                ],
                'data' => [
                    'condition-config' => Json::encode(array_merge($this->condition->getBuilderConfig(), [
                        'class' => $this->condition::class,
                        'id' => $namespacedId,
                        'name' => InputNamespace::get(),
                        'mainTag' => $this->condition->mainTag,
                        'sortable' => $this->condition->sortable,
                        'forProjectConfig' => $this->condition->forProjectConfig,
                        'addRuleLabel' => $this->condition->addRuleLabel,
                    ])),
                ],
            ]);

            $html .= Html::hiddenInput('class', $this->condition::class);
            $html .= Html::hiddenInput('config', Json::encode($this->condition->getBuilderConfig()));

            foreach ($this->condition->getConditionRules() as $rule) {
                $allRulesHtml .= InputNamespace::namespaceInputs(function () use ($rule, $ruleNum, $selectableRules) {
                    $ruleHtml =
                        Html::tag('legend', t('Condition {num, number}', [
                            'num' => $ruleNum,
                        ]), [
                            'class' => 'visually-hidden',
                        ]).
                        Html::hiddenInput('uid', $rule->uid).
                        Html::hiddenInput('class', $rule::class);

                    if ($this->condition->sortable) {
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
                    $labelId = "{$this->condition->id}-type-label";

                    $ruleHtml .=
                        // Rule type selector
                        Html::beginTag('div', ['class' => 'rule-switcher']).
                        Html::hiddenLabel(t('Rule Type'), 'type', [
                            'id' => $labelId,
                        ]).
                        $this->_ruleTypeMenu($selectableRules, $rule, $ruleValue, [
                            'icon' => 'chevron-down',
                            'icon-position' => 'suffix',
                            'aria' => [
                                'labelledby' => $labelId,
                            ],
                        ]).
                        Html::endTag('div').
                        // Rule HTML
                        Html::tag('div', app(ConditionRuleRenderer::class)->render($rule), [
                            'class' => ['rule-body', 'flex items-center gap-1 flex-grow'],
                        ]).
                        // Remove button
                        Html::beginTag('div', [
                            'class' => ['rule-actions'],
                        ]).
                        Html::tag('craft-button', '', [
                            'type' => 'button',
                            'icon' => 'x',
                            'aria-label' => t('Remove'),
                            'variant' => 'danger-plain',
                            'size' => 'small',
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

                $ruleNum++;
            }

            $rulesJs = HtmlStack::clearJsBuffer(false);

            if ($rulesJs) {
                HtmlStack::js($rulesJs);
            }

            // Sortable rules div
            $html .= Html::tag('div', $allRulesHtml, [
                'class' => array_filter([
                    'condition',
                    $this->condition->sortable ? 'sortable' : null,
                ]),
                'hx' => [
                    'post' => Url::actionUrl('conditions/render'),
                    'trigger' => 'end', // sortable library triggers this event
                ],
            ]);

            $html .=
                Html::beginTag('div', [
                    'class' => ['condition-footer', 'flex', 'flex-nowrap'],
                ]).
                $this->_ruleTypeMenu($selectableRules, buttonAttributes: [
                    'class' => array_filter([
                        empty($selectableRules) ? 'disabled' : null,
                    ]),
                    'icon' => 'plus',
                    'disabled' => empty($selectableRules),
                    'aria' => [
                        'label' => $this->condition->addRuleLabel,
                    ],
                    'autofocus' => $autofocusAddButton,
                ]).
                Html::tag('div', '', [
                    'id' => "{$this->condition->id}-spinner",
                    'class' => ['spinner'],
                ]).
                Html::endTag('div'); // flex-nowrap

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
        }, $this->condition->name);
    }

    /**
     * @param  ConditionRuleInterface[]  $selectableRules
     * @param  array<string, mixed>  $buttonAttributes
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

        // Sort by group label, and then option label within each group
        ksort($groupedRuleTypeOptions);
        if (isset($groupedRuleTypeOptions['__UNGROUPED__']) && count($groupedRuleTypeOptions) > 1) {
            $ungroupedRuleTypeOptions = $groupedRuleTypeOptions;
            Arr::forget($ungroupedRuleTypeOptions, '__UNGROUPED__');
            $groupedRuleTypeOptions = array_merge(['__UNGROUPED__' => $ungroupedRuleTypeOptions], $groupedRuleTypeOptions);
        }

        foreach ($groupedRuleTypeOptions as $groupLabel => $groupRuleTypeOptions) {
            $groupedRuleTypeOptions[$groupLabel] = Collection::make($groupRuleTypeOptions)
                ->sortBy(['label', 'hint'])
                ->all();
        }

        $buttonId = "{$this->condition->id}-type-btn";
        $menuId = "{$this->condition->id}-type-menu";
        $inputId = "{$this->condition->id}-type-input";

        HtmlStack::jsWithVars(
            fn ($menuId, $buttonId, $inputId) => <<<JS
Garnish.requestAnimationFrame(() => {
  const menu = document.querySelector('#' + $menuId);
  const input = document.querySelector('#' + $inputId);

  menu.addEventListener('change', (event) => {
      const item = event.detail?.item;
      if (!item) {
        return;
      }

      input.value = item?.getAttribute('data-value');
      htmx.trigger(input, 'change');
  })
});
JS,
            [
                InputNamespace::namespaceId($menuId),
                InputNamespace::namespaceId($buttonId),
                InputNamespace::namespaceId($inputId),
            ]
        );

        return view('c::condition.rule-type-menu', [
            'groupedRuleTypeOptions' => $groupedRuleTypeOptions,
            'ruleValue' => $ruleValue,
            'buttonId' => $buttonId,
            'menuId' => $menuId,
            'inputId' => $inputId,
            'buttonLabel' => $rule?->getLabel() ?? $this->condition->addRuleLabel,
            'buttonAttributes' => Arr::merge([
                'id' => $buttonId,
                'type' => 'button',
                'variant' => 'fill',
                'autofocus' => $rule?->getAutofocus(),
            ], $buttonAttributes),
            'inputName' => $rule ? 'type' : 'new-rule-type',
            'inputAttributes' => [
                'id' => $inputId,
                'hx' => [
                    'post' => Url::actionUrl('conditions/render'),
                ],
            ],
        ])->render();
    }
}
