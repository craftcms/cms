<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\FormDefinitions\Elements;

class ElementConditionInput extends InputElement
{
    /** @var class-string */
    private string $conditionClass;

    /** @var array<string, mixed> */
    private array $builderConfig = [];

    private bool $sortable = true;

    private ?string $addRuleLabel = null;

    public static function type(): string
    {
        return 'craft:element-condition-input';
    }

    /** @param class-string $conditionClass */
    public function conditionClass(string $conditionClass): self
    {
        $this->conditionClass = $conditionClass;

        return $this;
    }

    /** @param array<string, mixed> $builderConfig */
    public function builderConfig(array $builderConfig): self
    {
        $this->builderConfig = $builderConfig;

        return $this;
    }

    public function sortable(bool $sortable = true): self
    {
        $this->sortable = $sortable;

        return $this;
    }

    public function addRuleLabel(?string $addRuleLabel): self
    {
        $this->addRuleLabel = $addRuleLabel;

        return $this;
    }

    #[\Override]
    protected function props(): array
    {
        return array_filter([
            'conditionClass' => $this->conditionClass,
            'builderConfig' => $this->builderConfig,
            'sortable' => $this->sortable,
            'addRuleLabel' => $this->addRuleLabel,
        ], fn (mixed $value): bool => $value !== null);
    }
}
