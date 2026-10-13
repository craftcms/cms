<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ui\Nodes\Concerns;

use CraftCms\Cms\Cp\Components\Field as FieldComponent;
use CraftCms\Cms\Element\Enums\AttributeStatus;
use CraftCms\Cms\Support\Facades\Markdown;
use CraftCms\Cms\Support\Html;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Stringable;
use Twig\Markup;

/** Shared presentation for fields and groups using field appearance. */
trait HasFieldPresentation
{
    private ?string $label = null;

    private ?string $labelHtml = null;

    private bool $labelSrOnly = false;

    private bool $showStatus = true;

    private ?string $headingPrefix = null;

    private ?string $headingSuffix = null;

    private bool $translatable = false;

    private ?string $translationDescription = null;

    private ?string $orientation = null;

    private ?string $inputWidth = null;

    private ?string $status = null;

    private ?string $statusLabel = null;

    private bool $required = false;

    private ?string $instructions = null;

    private string $instructionsPosition = 'before';

    private ?string $tip = null;

    private ?string $warning = null;

    private ?string $layoutUid = null;

    /** Strings are plain text; Htmlable and Twig markup render as trusted HTML. */
    public function label(string|Htmlable|Stringable|null $label): static
    {
        $this->labelHtml = match (true) {
            $label instanceof Htmlable => $label->toHtml(),
            $label instanceof Markup => (string) $label,
            default => null,
        };
        $this->label = $this->labelHtml !== null
            ? html_entity_decode(strip_tags($this->labelHtml), ENT_QUOTES | ENT_HTML5, 'UTF-8')
            : ($label !== null ? (string) $label : null);

        return $this;
    }

    /** Visually hides the label, keeping it available to screen readers. */
    public function labelSrOnly(bool $labelSrOnly = true): static
    {
        $this->labelSrOnly = $labelSrOnly;

        return $this;
    }

    /**
     * Trusted HTML rendered beside the label, such as a `<craft-info-icon>`.
     * Encode any user-provided content before including it in the markup.
     */
    public function headingSuffix(string|Htmlable|null $headingSuffix): static
    {
        $this->headingSuffix = $headingSuffix instanceof Htmlable ? $headingSuffix->toHtml() : $headingSuffix;

        return $this;
    }

    /** Trusted HTML rendered before the label. Encode any user-provided content. */
    public function headingPrefix(string|Htmlable|null $headingPrefix): static
    {
        $this->headingPrefix = $headingPrefix instanceof Htmlable ? $headingPrefix->toHtml() : $headingPrefix;

        return $this;
    }

    public function translatable(bool $translatable = true, ?string $description = null): static
    {
        $this->translatable = $translatable;

        if ($description !== null) {
            $this->translationDescription = $description;
        }

        return $this;
    }

    public function showStatus(bool $showStatus = true): static
    {
        $this->showStatus = $showStatus;

        return $this;
    }

    /** @param 'ltr'|'rtl'|null $orientation */
    public function orientation(?string $orientation): static
    {
        $this->orientation = $orientation;

        return $this;
    }

    /**
     * Overrides control-based sizing within the field's grid allocation.
     * `full` fills the column even with a maxlength; `auto` shrinks without one.
     *
     * @param  'full'|'auto'|null  $width
     */
    public function inputWidth(?string $width): static
    {
        $this->inputWidth = $width;

        return $this;
    }

    /**
     * Sets the change-tracking status shown as a badge beside the label.
     *
     * @param  string|null  $status  An {@see AttributeStatus} value
     * @param  string|null  $label  The human-facing description of the status
     */
    public function status(?string $status, ?string $label = null): static
    {
        $this->status = $status;
        $this->statusLabel = $status !== null ? $label : null;

        return $this;
    }

    public function required(bool $required = true): static
    {
        $this->required = $required;

        return $this;
    }

    /** Parses markdown and preserves inline HTML, like {@see tip()} and {@see warning()}. */
    public function instructions(?string $instructions): static
    {
        $this->instructions = $instructions;

        return $this;
    }

    public function instructionsPosition(string $instructionsPosition): static
    {
        $this->instructionsPosition = $instructionsPosition;

        return $this;
    }

    public function tip(?string $tip): static
    {
        $this->tip = $tip;

        return $this;
    }

    public function warning(?string $warning): static
    {
        $this->warning = $warning;

        return $this;
    }

    public function layoutUid(?string $layoutUid): static
    {
        $this->layoutUid = $layoutUid;

        return $this;
    }

    /**
     * @return array{
     *     labelSrOnly: true|null,
     *     showStatus: false|null,
     *     labelHtml: string|null,
     *     headingPrefix: string|null,
     *     headingSuffix: string|null,
     *     translatable: true|null,
     *     translationDescription: string|null,
     *     orientation: string|null,
     *     inputWidth: string|null,
     *     instructionsPosition: string|null,
     *     instructionsHtml: string|null,
     *     tip: string|null,
     *     tipHtml: string|null,
     *     warning: string|null,
     *     warningHtml: string|null,
     *     layoutUid: string|null,
     *     status: string|null,
     *     statusLabel: string|null,
     * }
     */
    protected function fieldPresentationProps(): array
    {
        return [
            'labelSrOnly' => $this->labelSrOnly ?: null,
            'showStatus' => $this->showStatus ? null : false,
            'labelHtml' => $this->labelHtml,
            'headingPrefix' => $this->headingPrefix,
            'headingSuffix' => $this->headingSuffix,
            'translatable' => $this->translatable ?: null,
            'translationDescription' => $this->translationDescription,
            'orientation' => $this->orientation,
            'inputWidth' => $this->inputWidth,
            'instructionsPosition' => $this->instructionsPosition !== 'before' ? $this->instructionsPosition : null,
            'instructionsHtml' => $this->noticeHtml($this->instructions),
            'tip' => $this->tip,
            'tipHtml' => $this->noticeHtml($this->tip),
            'warning' => $this->warning,
            'warningHtml' => $this->noticeHtml($this->warning),
            'layoutUid' => $this->layoutUid,
            'status' => $this->showStatus ? $this->status : null,
            'statusLabel' => $this->showStatus && $this->status !== null ? $this->statusLabel : null,
        ];
    }

    /** @param array<string, mixed> $props */
    protected static function fieldComponent(array $props): FieldComponent
    {
        $showStatus = (bool) ($props['showStatus'] ?? true);

        return FieldComponent::make()
            ->label(isset($props['labelHtml']) ? new HtmlString($props['labelHtml']) : ($props['label'] ?? null))
            ->labelSrOnly((bool) ($props['labelSrOnly'] ?? false))
            ->headingPrefix(isset($props['headingPrefix']) ? (string) $props['headingPrefix'] : null)
            ->headingSuffix(isset($props['headingSuffix']) ? (string) $props['headingSuffix'] : null)
            ->translatable((bool) ($props['translatable'] ?? false), $props['translationDescription'] ?? null)
            ->orientation($props['orientation'] ?? null)
            ->width($props['inputWidth'] ?? null)
            ->instructions($props['instructions'] ?? null)
            ->instructionsPosition((string) ($props['instructionsPosition'] ?? 'before'))
            ->tip($props['tip'] ?? null)
            ->warning($props['warning'] ?? null)
            ->required((bool) ($props['required'] ?? false))
            ->status(
                $showStatus && isset($props['status']) ? (string) $props['status'] : null,
                $showStatus && isset($props['statusLabel']) ? (string) $props['statusLabel'] : null,
            );
    }

    private function noticeHtml(?string $notice): ?string
    {
        return $notice === null
            ? null
            : Html::decodeDoubles(Markdown::parseParagraph(Html::encodeInvalidTags($notice)));
    }
}
