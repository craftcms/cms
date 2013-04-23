/**
 * Element Select input
 */
Craft.ElementSelectInput = Garnish.Base.extend({

	id: null,
	name: null,
	elementType: null,
	sources: null,
	limit: null,
	modal: null,

	$container: null,
	$addElementBtn: null,

	init: function(id, name, elementType, sources, limit)
	{
		this.id = id;
		this.name = name;
		this.elementType = elementType;
		this.sources = sources;
		this.limit = limit;

		this.$container = $('#'+this.id);
		this.$addElementBtn = this.$container.next().find('.btn.add');

		this.addListener(this.$addElementBtn, 'activate', 'showModal');
	},

	showModal: function()
	{
		if (!this.modal)
		{
			this.modal = new Craft.ElementSelectorModal({
				elementType: this.elementType,
				sources: this.sources
			});
		}
		else
		{
			this.modal.show();
		}
	}
});
