<?php

namespace craft\conditions;

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
 * @property string[]|array{class: string}[] $conditionRuleTypes The available rule types for this condition
 * @property-read array $config The condition’s portable config
 * @property-read string $builderHtml The HTML for the condition builder, including its outer container element
 * @property-read string $builderInnerHtml The inner HTML for the condition builder, excluding its outer container element
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
     * @var Collection
     * @see getConditionRules()
     * @see setConditionRules()
     */
    private Collection $_conditionRules;

    /**
     * @var string[]|array{class: string}[]|array{type: string}[] The available rule types for this condition.
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
    }

    /**
     * Returns the label for the “Add a rule” button.
     *
     * @return string
     */
    protected function addRuleLabel(): string
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
     * Returns the rule types for this condition.
     *
     * Conditions should override this method instead of [[getConditionRuleTypes()]]
     * so [[EVENT_REGISTER_CONDITION_RULE_TYPES]] handlers can modify the class-defined rule types.
     *
     * Rule types should be defined as either the class name or an array with a `class` key set to the class name.
     *
     * @return string[]|array{class: string}[]
     */
    abstract protected function conditionRuleTypes(): array;

    /**
     * @inheritdoc
     */
    public function getSelectableConditionRules(array $options): array
    {
        $conditionsService = Craft::$app->getConditions();
        return collect($this->getConditionRuleTypes())
            ->keyBy(fn($type) => is_string($type) ? $type : Json::encode($type))
            ->map(fn($type) => $conditionsService->createConditionRule($type))
            ->filter(fn(ConditionRuleInterface $rule) => $this->isConditionRuleSelectable($rule, $options))
            ->all();
    }

    /**
     * Returns whether the given rule should be selectable by the condition builder.
     *
     * @param ConditionRuleInterface $rule The rule in question
     * @param array $options The builder options
     */
    protected function isConditionRuleSelectable(ConditionRuleInterface $rule, array $options): bool
    {
        return (
            $rule->isSelectable() &&
            !$options['projectConfigTypes'] || $rule::supportsProjectConfig()
        );
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
    public function getConfig(): array
    {
        return [
            'class' => get_class($this),
            'conditionRules' => $this->_conditionRules
                ->map(fn(ConditionRuleInterface $rule) => $rule->getConfig())
                ->values()
                ->all(),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getBuilderHtml(array $options = []): string
    {
        $tagName = ArrayHelper::remove($options, 'mainTag', 'form');
        $options += [
            'id' => 'condition' . mt_rand(),
        ];

        return Html::tag($tagName, $this->getBuilderInnerHtml($options), [
            'id' => $options['id'],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getBuilderInnerHtml(array $options = []): string
    {
        $isHtmxRequest = Craft::$app->getRequest()->getHeaders()->has('HX-Request');
        $view = Craft::$app->getView();
        $view->registerAssetBundle(ConditionBuilderAsset::class);

        $options += $this->defaultBuilderOptions() + [
                'sortable' => true,
                'projectConfigTypes' => false,
            ];

        // Get all the selectable condition rules as type/rule pairs
        $selectableRules = $this->getSelectableConditionRules($options);

        $namespace = $view->getNamespace();
        $namespacedId = Html::namespaceId($options['id'], $namespace);

        $html = Html::beginTag('div', [
            'class' => ['condition-main'],
            'hx' => [
                'target' => "#$namespacedId", // replace self
                'indicator' => "#$namespacedId-indicator", // ID of the spinner
                'include' => "#$namespacedId", // In case we are in a non form container
                'vals' => array_filter([
                    'namespace' => $namespace,
                    'options' => Json::encode($options),
                    'conditionRuleTypes' => Json::encode($this->getConditionRuleTypes()),
                ]),
            ],
        ]);

        $html .= Html::hiddenInput('class', get_class($this));

        // Start rule js buffer
        $view->startJsBuffer();

        $allRulesHtml = '';
        $ruleCount = 0;

        foreach ($this->getConditionRules() as $rule) {
            $allRulesHtml .= $view->namespaceInputs(function() use ($rule, $options, $selectableRules) {
                $ruleHtml = Html::hiddenInput('uid', $rule->uid) .
                    Html::hiddenInput('class', get_class($rule));

                if ($options['sortable']) {
                    $ruleHtml .= Html::tag('div',
                        Html::tag('a', '', [
                            'class' => ['move', 'icon', 'draggable-handle'],
                        ]),
                        [
                            'class' => ['rule-move'],
                        ]
                    );
                }

                $ruleTypeOptions = [];
                $ruleValue = Json::encode($rule->getConfig());
                $ruleLabel = $rule->getLabel();
                foreach ($selectableRules as $value => $selectableRule) {
                    /** @var ConditionRuleInterface $selectableRule */
                    $label = $selectableRule->getLabel();
                    if ($label !== $ruleLabel) {
                        $ruleTypeOptions[] = compact('value', 'label');
                    }
                }
                $ruleTypeOptions[] = ['value' => $ruleValue, 'label' => $ruleLabel];

                ArrayHelper::multisort($ruleTypeOptions, 'label');

                // Add rule type selector
                $switcherHtml = Cp::selectHtml([
                    'name' => 'type',
                    'options' => $ruleTypeOptions,
                    'value' => $ruleValue,
                    'inputAttributes' => [
                        'hx' => [
                            'post' => UrlHelper::actionUrl('conditions/render'),
                        ],
                    ],
                ]);

                $ruleHtml .= Html::tag('div', $switcherHtml, [
                    'class' => ['rule-switcher'],
                ]);

                // Get rule input html
                $ruleHtml .= Html::tag('div', $rule->getHtml($options), [
                    'id' => 'rule-body',
                    'class' => ['rule-body', 'flex-grow'],
                ]);

                // Add delete button
                $deleteButtonAttr = [
                    'id' => 'delete',
                    'class' => ['delete', 'icon'],
                    'title' => Craft::t('app', 'Delete'),
                    'hx' => [
                        'vals' => ['uid' => $rule->uid],
                        'post' => UrlHelper::actionUrl('conditions/remove-rule'),
                    ],
                ];
                $deleteButton = Html::tag('a', '', $deleteButtonAttr);
                $ruleHtml .= Html::tag('div', $deleteButton, [
                    'id' => 'rule-actions',
                    'class' => ['rule-actions'],
                ]);

                return Html::tag('div', $ruleHtml, [
                    'id' => 'condition-rule',
                    'class' => ['condition-rule', 'flex', 'draggable'],
                ]);
            }, 'conditionRules[' . ++$ruleCount . ']');
        }

        $rulesJs = $view->clearJsBuffer(false);

        // Sortable rules div
        $html .= Html::tag('div', $allRulesHtml, [
                'id' => 'condition-rules',
                'class' => array_filter([
                    'condition',
                    $options['sortable'] ? 'sortable' : null,
                ]),
                'hx' => [
                    'post' => UrlHelper::actionUrl('conditions/render'),
                    'trigger' => 'end', // sortable library triggers this event
                ],
            ]
        );

        $html .= Html::tag('div',
            Html::tag('button',
                $this->addRuleLabel() .
                Html::tag('div', '', [
                    'class' => ['htmx-indicator', 'spinner'],
                    'id' => "{$options['id']}-indicator",
                ]), [
                    'type' => 'button',
                    'class' => array_filter([
                        'btn',
                        'add',
                        'icon',
                        'fullwidth',
                        'dashed',
                        empty($selectableRules) ? 'disabled' : null,
                    ]),
                    'hx' => [
                        'post' => UrlHelper::actionUrl('conditions/add-rule'),
                    ],
                ]
            ), [
                'class' => ['condition-footer', 'flex', 'flex-nowrap'],
            ]
        );

        // Add inline script tag
        if ($isHtmxRequest && $rulesJs) {
            $html .= html::tag('script', $rulesJs, ['type' => 'text/javascript']);
        } elseif ($rulesJs) {
            $view->registerJs($rulesJs);
        }

        if (!$isHtmxRequest) {
            $view->registerJs("htmx.process(htmx.find('#$namespacedId'));");
            $view->registerJs("htmx.trigger(htmx.find('#$namespacedId'), 'htmx:load');");
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
        }

        $html .= Html::endTag('div'); //condition-main

        return $html;
    }

    /**
     * Returns the default builder options.
     *
     * @return array
     */
    protected function defaultBuilderOptions(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        return [
            [['conditionRules', 'conditionRuleTypes'], 'safe'],
        ];
    }
}
