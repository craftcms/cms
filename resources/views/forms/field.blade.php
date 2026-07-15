@php
    /**
     * CP form field wrapper: heading, instructions, input container, notices,
     * and errors. Ported from the string-built markup in FormFields::fieldHtml,
     * which still owns all config defaulting and passes precomputed values here.
     *
     * Raw echoes are intentional throughout: every value is either
     * caller-supplied HTML (label, input, headingPrefix/Suffix, labelExtra) or
     * HTML prerendered by FormFields (labelHtml, notices, error list), matching
     * the legacy unencoded concatenation.
     *
     * Inline runs (label internals, notice spans, input container) stay on one
     * line — whitespace between inline elements is visible in the CP.
     *
     * Declared in a PHP docblock (rather than a Blade comment) so PhpStorm types
     * these variables.
     *
     * @var array $fieldAttributes Merged attributes for the outer .field div
     * @var array|null $status [status, label] or null
     * @var string $statusId
     * @var string|null $statusClass
     * @var string|null $statusLabel
     * @var bool $showHeading
     * @var string|null $headingPrefix
     * @var string|null $headingSuffix
     * @var string|null $labelElementHtml Full <label>/<legend> element, or null
     * @var bool $static
     * @var bool $showLabelExtra
     * @var string|null $actionMenuHtml
     * @var string|null $attributeCopyHtml
     * @var string|null $labelExtra
     * @var string $instructionsId
     * @var string|null $instructionsContent Markdown-parsed instructions
     * @var string $instructionsPosition 'before' or 'after'
     * @var array $inputContainerAttributes Merged attributes for the .input div
     * @var string $input
     * @var string $tipId
     * @var string|null $tipContent
     * @var string $warningId
     * @var string|null $warningContent
     * @var string|null $errorListHtml
     */

    use CraftCms\Cms\Support\Html;

    use function CraftCms\Cms\t;
@endphp
<div{!! Html::renderTagAttributes($fieldAttributes) !!}>
    @if ($status)
        <div id="{{ $statusId }}" class="status-badge {{ $statusClass }}" title="{{ $statusLabel }}" aria-hidden="true"><span class="visually-hidden">{!! $statusLabel !!}</span></div>
    @endif
    @if ($showHeading)
        <div class="heading font-bold mb-1">
            {!! $headingPrefix !!}{!! $labelElementHtml !!}
            @if ($static)
                <span class="read-only-badge">{!! t('Read Only') !!}</span>
            @endif
            @if ($showLabelExtra)
                <div class="flex-grow"></div>
                {!! $actionMenuHtml !!}{!! $attributeCopyHtml !!}{!! $labelExtra !!}
            @endif
            {!! $headingSuffix !!}
        </div>
    @endif
    @if ($instructionsContent && $instructionsPosition === 'before')
        <div id="{{ $instructionsId }}" class="instructions">{!! $instructionsContent !!}</div>
    @endif
    <div{!! Html::renderTagAttributes($inputContainerAttributes) !!}>{!! $input !!}</div>
    @if ($instructionsContent && $instructionsPosition === 'after')
        <div id="{{ $instructionsId }}" class="instructions">{!! $instructionsContent !!}</div>
    @endif
    @if ($tipContent)
            @include('c::forms.field-notice', ['id' => $tipId, 'variant' => 'info', 'icon' => 'lightbulb', 'label' => t('Tip:'), 'content' => $tipContent])
    @endif
    @if ($warningContent)
            @include('c::forms.field-notice', ['id' => $warningId, 'variant' => 'warning', 'icon' => '', 'label' => t('Warning:'), 'content' => $warningContent])
    @endif
    {!! $errorListHtml !!}
</div>
