<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Controls\Concerns;

use CraftCms\Cms\Form\ControlPayload;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Json;

/**
 * @phpstan-type TextExpanderOption array{
 *     label: string,
 *     value: string,
 *     keywords?: list<string>,
 *     data?: mixed,
 * }
 * @phpstan-type TextExpanderTrigger (
 *     array{trigger: string, boundary: 'start'|'whitespace'|'anywhere', label?: string, options: list<TextExpanderOption>, source?: never, limit?: int}
 *     | array{trigger: string, boundary: 'start'|'whitespace'|'anywhere', label?: string, source: string, options?: never, limit?: int}
 * )
 * @phpstan-type TextExpanderTriggers list<TextExpanderTrigger>
 */
trait HasTextExpander
{
    /** @var TextExpanderTriggers */
    private array $textExpanderTriggers = [];

    /** @param TextExpanderTriggers $triggers */
    public function textExpanderTriggers(array $triggers): static
    {
        $this->textExpanderTriggers = $triggers;

        return $this;
    }

    /** @return array{textExpanderTriggers: TextExpanderTriggers|null} */
    protected function textExpanderProps(): array
    {
        return ['textExpanderTriggers' => $this->textExpanderTriggers ?: null];
    }

    /** @param array<string, mixed> $attributes */
    protected static function textExpanderHtml(ControlPayload $control, array $attributes): string
    {
        if ($attributes['name'] === null || empty($control->props['textExpanderTriggers'])) {
            return '';
        }

        return Html::tag('craft-text-expander', '', [
            'slot' => 'input',
            'for' => $attributes['id'],
            'triggers' => Json::encode($control->props['textExpanderTriggers']),
        ]);
    }
}
