<?php

declare(strict_types=1);

namespace CraftCms\Cms\Condition;

use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Condition\Concerns\LegacyConstants;
use CraftCms\Cms\Condition\Contracts\ConditionGroupInterface;
use CraftCms\Cms\Condition\Contracts\ConditionInterface;
use CraftCms\Cms\Condition\Contracts\ConditionRuleInterface;
use CraftCms\Cms\Condition\Events\ConditionRulesResolving;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Facades\Conditions;
use CraftCms\Cms\Support\Json;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Override;

use function CraftCms\Cms\t;

abstract class BaseCondition extends Component implements ConditionInterface
{
    use LegacyConstants;

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
     * @var array<string, mixed> The condition’s portable config
     */
    public array $config {
        get => $this->getConfig();
    }

    /**
     * @var ConditionGroupInterface The rules this condition is configured with.
     */
    public ConditionGroupInterface $conditionRules {
        set(ConditionGroupInterface|array $value) {
            $this->conditionRules = $this->normalizeConditionRules($value);
        }
    }

    /**
     * @var ConditionRuleInterface[]|null The selectable condition rules for this condition.
     *
     * @see getSelectableConditionRules()
     */
    private ?array $_selectableConditionRules = null;

    /** @param  array<string, mixed>  $config */
    public function __construct(array $config = [])
    {
        parent::__construct($config);

        $this->id ??= 'condition'.mt_rand();

        $this->addRuleLabel ??= t('Add a rule');

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

            event($event = new ConditionRulesResolving(
                condition: $this,
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
     * so {@see ConditionRulesResolving} handlers can modify the class-defined rules.
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
        if (! $rule::isSelectable() || ! $rule::isSelectableForCondition($this)) {
            return false;
        }

        if ($this->forProjectConfig && ! $rule::supportsProjectConfig()) {
            return false;
        }

        return true;
    }

    public function getConditionRules(): ConditionGroupInterface
    {
        return $this->conditionRules;
    }

    public function setConditionRules(ConditionGroupInterface|array $rules): void
    {
        $this->conditionRules = $rules;
    }

    /** @param ConditionGroupInterface|array{operator: string, rules: array{class: string}|array{type: string}}|array<ConditionRuleInterface|array{class: string}|array{type: string}|string> $rules */
    protected function normalizeConditionRules(ConditionGroupInterface|array $rules): ConditionGroupInterface
    {
        if ($rules instanceof ConditionGroupInterface) {
            return $rules;
        }

        if (isset($rules['rules'])) {
            $group = $this->normalizeConditionRules($rules['rules']);

            if (isset($rules['operator'])) {
                $group->operator = $rules['operator'];
            }

            return $group;
        }

        $group = static::createGroup();
        $group->setCondition($this);

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

            if (! $projectConfig->isApplyingExternalChanges && ! $this->validateConditionRule($rule)) {
                throw new InvalidArgumentException('Invalid condition rule');
            }

            $group->addRule($rule);
        }

        // Clear out our cache of selectable condition rules, in case any additional rules will depend on which
        // rules are already configured.
        $this->_selectableConditionRules = null;

        return $group;
    }

    public function addConditionRule(ConditionRuleInterface $rule): void
    {
        // Don't validate the rule when we're applying project config changes.
        // The rule type might depend on something that hasn't been added yet.
        if (! app(ProjectConfig::class)->isApplyingExternalChanges && ! $this->validateConditionRule($rule)) {
            throw new InvalidArgumentException('Invalid condition rule');
        }

        $this->conditionRules->addRule($rule);

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

    #[Override]
    public function getRules(): array
    {
        return [
            'conditionRules' => ['nullable'],
        ];
    }

    /** @return array<string, mixed> */
    public function getBuilderConfig(): array
    {
        return $this->config();
    }

    /** @return array<string, mixed> */
    public function getConfig(): array
    {
        return array_merge($this->config(), [
            'class' => static::class,
            'conditionRules' => $this->conditionRules->getConfig(),
        ]);
    }

    /**
     * Returns the base config that should be maintained by the builder and included in the condition’s portable config.
     *
     * @return array<string, mixed>
     */
    protected function config(): array
    {
        return [];
    }
}
