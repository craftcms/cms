@php
    /**
     * A single tab within the field layout designer.
     *
     * @var \CraftCms\Cms\Cp\FieldLayoutDesigner\FieldLayoutDesigner $designer
     * @var \CraftCms\Cms\FieldLayout\FieldLayoutTab $tab
     * @var bool $customizable
     * @var bool $disabled
     */
@endphp
<div class="fld-tab" data-uid="{{ $tab->uid }}">
    <div class="tabs">
        <div @class(['tab', 'sel', 'draggable' => $customizable])>
            {!! $tab->labelHtml() !!}
        </div>
    </div>
    <div class="fld-tabcontent">
        @foreach ($tab->getElements() as $element)
            {!! $designer->layoutElementSelectorHtml($element) !!}
        @endforeach
        <craft-button
            type="button"
            class="cp:w-full fld-add-btn cp:mt-2"
            variant="outline"
            size="small"
            command="--add-field"
            @disabled($disabled)
        >
            <craft-icon name="plus" slot="prefix"></craft-icon>
            {{ \CraftCms\Cms\t('Add') }}
        </craft-button>
    </div>
</div>
