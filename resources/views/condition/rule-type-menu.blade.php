@php
    /**
     * Rule type menu for the condition builder.
     *
     * @var array<string, list<array{label: string, hint: string|null, showHint: bool, value: string}>> $groupedRuleTypeOptions
     * @var string|null $ruleValue
     * @var string $buttonId
     * @var string $menuId
     * @var string $inputId
     * @var string $buttonLabel
     * @var array $buttonAttributes
     * @var string $inputName
     * @var array $inputAttributes
     */
@endphp
<craft-action-menu id="{{ $menuId }}">
    <craft-button slot="invoker" {!!  \CraftCms\Cms\Support\Html::renderTagAttributes($buttonAttributes) !!}>
        {{e($buttonLabel)}}
    </craft-button>
    <div slot="content">
        @foreach ($groupedRuleTypeOptions as $groupLabel => $groupOptions)
            @if ($groupLabel !== '__UNGROUPED__')
                <hr />
                <h6>{{$groupLabel}}</h6>
            @endif
            @foreach ($groupOptions as $option)
                <craft-action-item data-value="{{ $option['value'] }}">
                    {!! e($option['label']) !!}
                    @if ($option['showHint'] && $option['hint'] !== null)
                        <span class="c-text-on-quiet">– {!! e($option['hint']) !!}</span>
                    @endif
                </craft-action-item>
            @endforeach
        @endforeach
    </div>
    {!! \CraftCms\Cms\Support\Html::hiddenInput($inputName, $ruleValue, $inputAttributes) !!}
</craft-action-menu>
