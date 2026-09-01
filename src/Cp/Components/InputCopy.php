<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use Override;
use Stringable;

/**
 * PHP counterpart to the `<craft-input-copy>` web component — a read-only text
 * input with an integrated copy-to-clipboard button in the suffix (rendered by
 * the web component itself).
 *
 * Extends {@see Input} to mirror the web component's `CraftInputCopy extends
 * CraftInput` hierarchy, inheriting the full input API (size, width, maxlength,
 * monospace, …). It only adds an independent clipboard value and forces the
 * control read-only.
 *
 *     InputCopy::make()
 *         ->name('apiKey')
 *         ->value($apiKey);
 *
 * When the value displayed in the textbox should differ from what is sent to
 * the clipboard (e.g. a masked token), pass the full value via
 * {@see self::copyValue()}:
 *
 *     InputCopy::make()
 *         ->value('sk-••••••••••••••1234')
 *         ->copyValue($fullToken);
 */
class InputCopy extends Input
{
    /**
     * Value sent to the clipboard when the copy button is clicked. When
     * omitted, the displayed value is used instead.
     */
    protected string|int|float|Stringable|null $copyValue = null;

    /** The copy field is always read-only. */
    #[Override]
    protected bool $readOnly = true;

    #[Override]
    protected function tagName(): string
    {
        return 'craft-input-copy';
    }

    /**
     * Value sent to the clipboard when the copy button is clicked. When
     * omitted, the displayed value is copied instead.
     */
    public function copyValue(string|int|float|Stringable|null $copyValue): static
    {
        $this->copyValue = $copyValue;

        return $this;
    }

    #[Override]
    protected function hostAttributes(): array
    {
        return [
            ...parent::hostAttributes(),
            'copy-value' => $this->copyValue !== null ? (string) $this->copyValue : null,
        ];
    }
}
