<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Controls;

use CraftCms\Cms\Cp\Components\InputHandle;
use CraftCms\Cms\Form\ControlPayload;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Support\Facades\HtmlStack;
use Illuminate\Support\Arr;
use InvalidArgumentException;

class Handle extends Control
{
    /** @var list<string>|null */
    private ?array $source = null;

    public static function renderHtml(ControlPayload $control, mixed $value, array $attributes, FormHtmlRenderer $renderer): string
    {
        $source = $control->props['source'] ?? null;

        if ($source !== null && $attributes['name'] !== null) {
            $sourcePath = [...array_slice($control->path, 0, -1), ...$source];
            HtmlStack::jsWithVars(fn ($sourceId, $targetId) => <<<JS
new Craft.HandleGenerator('#' + $sourceId, '#' + $targetId)
JS, [$renderer->id($sourcePath), $attributes['id']]);
        }

        return InputHandle::make()
            ->id($attributes['id'])
            ->name($attributes['name'])
            ->value($value === null ? null : (string) $value)
            ->disabled($attributes['disabled'])
            ->readOnly($attributes['readonly'])
            ->describedBy($attributes['aria']['describedby'] ?? null)
            ->inputAttributes([
                'required' => $attributes['required'],
                'aria' => ['invalid' => $attributes['aria']['invalid'] ?? null],
            ])
            ->toHtml();
    }

    public function component(): string
    {
        return 'craft:handle';
    }

    /**
     * Sets the source path relative to this Control's parent path.
     *
     * @param  string|list<string>|null  $source
     */
    public function source(string|array|null $source): static
    {
        if ($source === null) {
            $this->source = null;

            return $this;
        }

        $source = is_string($source) ? explode('.', $source) : array_values($source);

        if ($source === [] || array_any($source, fn (mixed $segment): bool => ! is_string($segment) || $segment === '')) {
            throw new InvalidArgumentException('Handle source paths must contain non-empty string segments.');
        }

        $this->source = $source;

        return $this;
    }

    #[\Override]
    public function props(mixed $value = null): array
    {
        return Arr::whereNotNull(['source' => $this->source]);
    }
}
