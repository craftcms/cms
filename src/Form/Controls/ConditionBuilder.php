<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Controls;

use CraftCms\Cms\Condition\BaseCondition;
use CraftCms\Cms\Condition\Contracts\ConditionInterface;
use CraftCms\Cms\Form\ControlPayload;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Support\Facades\Conditions;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Html;
use InvalidArgumentException;

class ConditionBuilder extends Control
{
    /** @var class-string<ConditionInterface>|null */
    private ?string $conditionClass = null;

    /** @var list<string> */
    private array $queryParams = [];

    private bool $forProjectConfig = false;

    public static function renderHtml(ControlPayload $control, mixed $value, array $attributes, FormHtmlRenderer $renderer): string
    {
        return self::builderHtml(
            is_array($value) ? $value : [],
            $control->props['conditionClass'],
            $control->props['queryParams'],
            (bool) $control->props['forProjectConfig'],
            $attributes['name'],
            $attributes['name'] === null,
        );
    }

    /**
     * @param  array<string, mixed>  $value
     * @param  class-string<ConditionInterface>  $conditionClass
     * @param  list<string>  $queryParams
     */
    public static function builderHtml(
        array $value,
        string $conditionClass,
        array $queryParams,
        bool $forProjectConfig,
        ?string $name,
        bool $disabled,
    ): string {
        $condition = Conditions::createCondition([...$value, 'class' => $conditionClass]);
        if (! $condition instanceof BaseCondition) {
            throw new InvalidArgumentException("Condition [{$conditionClass}] must extend ".BaseCondition::class.'.');
        }

        $condition->mainTag = 'div';
        $condition->name = $name === null ? 'condition' : self::leafName($name);
        $condition->forProjectConfig = $forProjectConfig;
        if (property_exists($condition, 'queryParams')) {
            $condition->queryParams = array_values(array_unique([...$condition->queryParams, ...$queryParams]));
        }
        $namespace = $name === null ? null : self::parentInputName($name);
        $html = InputNamespace::namespaceInputs($condition->getBuilderHtml(...), $namespace);

        return $disabled ? (string) Html::disableInputs($html) : $html;
    }

    public function component(): string
    {
        return 'craft:condition-builder';
    }

    /** @param class-string<ConditionInterface> $conditionClass */
    public function conditionClass(string $conditionClass): static
    {
        if (! is_a($conditionClass, ConditionInterface::class, true)) {
            throw new InvalidArgumentException("Condition [{$conditionClass}] must implement ".ConditionInterface::class.'.');
        }

        $this->conditionClass = $conditionClass;

        return $this;
    }

    /** @param list<string> $queryParams */
    public function queryParams(array $queryParams): static
    {
        $this->queryParams = $queryParams;

        return $this;
    }

    public function forProjectConfig(bool $forProjectConfig = true): static
    {
        $this->forProjectConfig = $forProjectConfig;

        return $this;
    }

    #[\Override]
    public function props(mixed $value = null): array
    {
        if ($this->conditionClass === null) {
            throw new InvalidArgumentException('ConditionBuilder Controls require a condition class.');
        }

        return [
            'conditionClass' => $this->conditionClass,
            'queryParams' => $this->queryParams,
            'forProjectConfig' => $this->forProjectConfig,
        ];
    }

    private static function leafName(string $name): string
    {
        preg_match('/(?:^|\[)([^\[\]]+)]?$/', $name, $matches);

        return $matches[1] ?? $name;
    }
}
