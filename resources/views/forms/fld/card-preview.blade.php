@php
    /**
     * @var string $thumbAlignment
     * @var string|null $coverSrc
     * @var array $coverLuminance
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
        <div slot="thumbnail" class="cvd-chip-thumbnail">{!! $thumbnailHtml !!}</div>
    @endif
    <div><a class="label-link" href="#" aria-disabled="true">{{ $labelText }}</a></div>
</craft-chip>

{{-- With the Cover thumb style, a sample image covers the card in place of
     the thumbnail column --}}
<craft-card
    thumb-alignment="{{$thumbAlignment}}"
    @if ($coverSrc)
        cover-src="{{ $coverSrc }}"
        luminance="{{ json_encode($coverLuminance) }}"
    @endif
>
    <div slot="header"></div>

    @unless ($coverSrc)
        @if ($avatarHtml)
            <div slot="thumbnail" class="card-thumb">{!! $avatarHtml !!}</div>
        @elseif ($showThumb)
            <div slot="thumbnail" class="cvd-thumbnail">{!! $thumbnailHtml !!}</div>
        @endif
    @endunless

    {!! $headingHtml !!}
    {!! $bodyHtml !!}
</craft-card>
