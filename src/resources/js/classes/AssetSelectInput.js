/**
 * Element Select input
 */
Craft.AssetSelectInput = Craft.BaseElementSelectInput.extend({

    requestId: 0,
    hud: null,

	init: function(id, name, elementType, sources, limit)
	{
		this.base(id, name, elementType, sources, limit);
        this._attachHUDEvents();
	},

    selectElements: function (elements)
    {
        this.base(elements);
        this._attachHUDEvents();
    },

    _attachHUDEvents: function ()
    {
        this.removeListener(this.$elements, 'dlbclick');
        this.addListener(this.$elements, 'dblclick', $.proxy(this, '_editProperties'));
    },

    _editProperties: function (event)
    {
        var $target = $(event.currentTarget);
        if (!$target.data('ElementEditor'))
        {
            var settings = {
                elementId: $target.attr('data-id'),
                $trigger: $target,
                loadContentAction: 'assets/viewFile',
                saveContentAction: 'assets/saveFileContent'
            };
            $target.data('ElementEditor', new Craft.ElementEditor(settings));
        }

        $target.data('ElementEditor').show();
    }
});
