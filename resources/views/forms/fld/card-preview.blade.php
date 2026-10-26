@php
    /**
     * @var string $thumbAlignment
     * @var boolean $showThumb
     * @var string $thumbnailHtml
     * @var string $headingHtml
     * @var string|null $statusIndicatorHtml
     * @var string|null $avatarHtml
     * @var string $labelText
     */
@endphp

{{-- A sample chip, as the element's chips show, with the card's thumbnail
     and status but not its attributes --}}
<craft-chip
    class="cvd-chip"
    @if ($showThumb) show-thumb @endif
    @if ($statusIndicatorHtml) show-status @endif
>
    @if ($statusIndicatorHtml)
        <div slot="status">{!! $statusIndicatorHtml !!}</div>
    @endif
    @if ($avatarHtml)
        <div slot="thumbnail">{!! $avatarHtml !!}</div>
    @elseif ($showThumb)
        <div slot="thumbnail" class="cvd-chip-thumbnail">
            <craft-icon name="image"></craft-icon>
        </div>
    @endif
    <div><a class="label-link" href="#" aria-disabled="true">{{ $labelText }}</a></div>
</craft-chip>

<craft-card thumb-alignment="{{$thumbAlignment}}">
    <div slot="header"></div>

    @if ($avatarHtml)
        <div slot="thumbnail" class="card-thumb">{!! $avatarHtml !!}</div>
    @elseif ($showThumb)
        <div slot="thumbnail" class="cvd-thumbnail">
            {!! $thumbnailHtml !!}
        </div>
    @endif

    {!! $headingHtml !!}
    {!! $bodyHtml !!}
</craft-card>
