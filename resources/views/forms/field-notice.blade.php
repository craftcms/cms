@php
    /**
     * Tip/warning notice for a form field. Split out of FormFields::fieldHtml.
     *
     * The label span keeps its trailing space (inside the span) so screen
     * readers announce "Tip: …" with a natural pause, matching the legacy
     * output.
     *
     * @var string $id
     * @var string $class 'notice' or 'warning'
     * @var string $label Visually hidden prefix, e.g. t('Tip:')
     * @var string $content Pre-parsed markdown paragraph HTML
     */
@endphp
<p id="{{ $id }}" class="{{ $class }} has-icon"><span class="icon" aria-hidden="true"></span><span class="visually-hidden">{!! $label !!} </span><span>{!! $content !!}</span></p>
