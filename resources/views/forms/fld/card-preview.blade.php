@php
    /**
     * @var string $thumbAlignment
     * @var boolean $showThumb
     * @var string $thumbnailHtml
     * @var string $headingHtml
     */
@endphp

<craft-card thumb-alignment="{{$thumbAlignment}}">
    <div slot="header"></div>

    @if($showThumb)
        <div slot="thumbnail" class="cvd-thumbnail">
            {!! $thumbnailHtml !!}
        </div>
    @endif

    {!! $headingHtml !!}
    {!! $bodyHtml !!}
</craft-card>
