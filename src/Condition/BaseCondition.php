<?php

declare(strict_types=1);

namespace CraftCms\Cms\Condition;

use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Condition\Concerns\LegacyConstants;
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
use RuntimeException;

use function CraftCms\Cms\t;

abstract class BaseCondition extends Component implements ConditionInterface
{
    use LegacyConstants;

    public static function supportsGroups(): bool
    {
        return false;
    }

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
     * @see getConditionRules()
     * @see setConditionRules()
     *
     * @var Collection<int, ConditionRuleInterface>|Collection<int, ConditionRuleGroup>
     */
    private Collection $_conditionRules;

    /**
     * @var ConditionRuleInterface[]|ConditionRuleGroup[] The rules this condition is configured with, or condition groups if {@see supportsGroups()} is `true`.
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

    public function getConditionRules(): array
    {
        return $this->_conditionRules->all();
    }

    /** @param  array<ConditionRuleInterface|ConditionRuleGroup|array{class: string}|array{type: string}|array{conditionRules: array{class: string}|array{type: string}}|string>  $rules */
    public function setConditionRules(array $rules): void
    {
        $this->_conditionRules = Collection::make();
        app(ProjectConfig::class);

        $group = -1;

        foreach ($rules as $rule) {
            $isGroup = static::supportsGroups() && (
                $rule instanceof ConditionRuleGroup ||
                (is_array($rule) && isset($rule['conditionRules']))
            );

            // starting a new group?
            if ($group === -1 || $isGroup) {
                $group++;
            }

            if ($isGroup) {
                $groupRules = $rule instanceof ConditionRuleGroup ? $rule->conditionRules->all() : $rule['conditionRules'];
                foreach ($groupRules as $r) {
                    $r = $this->normalizeConditionRule($r);

                    if ($r !== null) {
                        $this->addConditionRule($r, $group);
                    }
                }
            } else {
                $rule = $this->normalizeConditionRule($rule);

                if ($rule !== null) {
                    $this->addConditionRule($rule, $group);
                }
            }
        }

        // Clear out our cache of selectable condition rules, in case any additional rules will depend on which
        // rules are already configured.
        $this->_selectableConditionRules = null;
    }

    /** @param ConditionRuleInterface|array{class: string}|array{type: string}|string $rule */
    private function normalizeConditionRule(ConditionRuleInterface|array|string $rule): ?ConditionRuleInterface
    {
        if ($rule instanceof ConditionRuleInterface) {
            return $rule;
        }

        try {
            return $this->createConditionRule($rule);
        } catch (InvalidArgumentException $e) {
            Log::warning("Invalid condition rule: {$e->getMessage()}");

            return null;
        }
    }

    public function addConditionRule(ConditionRuleInterface $rule, int $group = 0): void
    {
        // Don't validate the rule when we're applying project config changes.
        // The rule type might depend on something that hasn't been added yet.
        if (! app(ProjectConfig::class)->isApplyingExternalChanges && ! $this->validateConditionRule($rule)) {
            throw new InvalidArgumentException('Invalid condition rule');
        }

        $rule->setCondition($this);

        if (static::supportsGroups()) {
            /** @var ConditionRuleGroup $group */
            $conditionGroup = $this->_conditionRules->getOrPut($group, fn () => new ConditionRuleGroup);
            $conditionGroup->conditionRules->add($rule);
        } else {
            $this->_conditionRules->add($rule);
        }

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
            'conditionRules' => $this->_conditionRules
                ->map(function (ConditionRuleInterface|ConditionRuleGroup $rule) {
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
     *
     * @return array<string, mixed>
     */
    protected function config(): array
    {
        return [];
    }
}
