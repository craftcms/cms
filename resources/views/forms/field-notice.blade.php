@php
    /**
     * Tip/warning notice for a form field. Split out of FormFields::fieldHtml.
     *
     * The label span keeps its trailing space (inside the span) so screen
     * readers announce "Tip: …" with a natural pause, matching the legacy
     * output.
     *
     * @var string $id
     * @var string $variant 'info' or 'warning'
     * @var string $icon Icon to disply as a prefix
     * @var string $label Visually hidden prefix, e.g. t('Tip:')
     * @var string $content Pre-parsed markdown paragraph HTML
     */
@endphp
<craft-callout
    id="{{ $id }}"
    variant="{{ $variant }}"
    @if($icon) icon="{{$icon}}" @endif
    appearance="plain"
    class="p-0 mt-1"
>
    <span class="visually-hidden">{!! $label !!} </span>
    {!! $content !!}
</craft-callout>
