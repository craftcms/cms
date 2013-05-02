/**
 * Element Select input
 */
Craft.ElementSelectInput = Garnish.Base.extend({

	id: null,
	name: null,
	elementType: null,
	sources: null,
	limit: null,
	elementSelect: null,
	elementSort: null,
	modal: null,

	$container: null,
	$elementsContainer: null,
	$elements: null,
	$addElementBtn: null,

	init: function(id, name, elementType, sources, limit)
	{
		this.id = id;
		this.name = name;
		this.elementType = elementType;
		this.sources = sources;
		this.limit = limit;

		this.$container = $('#'+this.id);
		this.$elementsContainer = this.$container.children('.elements');
		this.$elements = this.$elementsContainer.children();
		this.$addElementBtn = this.$container.children('.btn.add');

		this.elementSelect = new Garnish.Select(this.$elements, {
			multi: true
		});

		this.elementSort = new Garnish.DragSort({
			container: this.$elementsContainer,
			filter: $.proxy(function() {
				return this.elementSelect.getSelectedItems();
			}, this),
			caboose: $('<div class="caboose"/>'),
			onSortChange: $.proxy(function() {
				this.elementSelect.resetItemOrder();
			}, this)
		});

		this.initElements(this.$elements);

		this.addListener(this.$addElementBtn, 'activate', 'showModal');
	},

	initElements: function($elements)
	{
		this.elementSelect.addItems($elements);
		this.elementSort.addItems($elements);

		$elements.find('.delete').on('click', $.proxy(function(ev) {
			var $element = $(ev.currentTarget).closest('.element');
			this.$elements = this.$elements.not($element);

			if (this.modal)
			{
				this.modal.enableElementsById($element.data('id'));
			}

			$element.remove();
		}, this));
	},

	showModal: function()
	{
		if (!this.modal)
		{
			var selectedElementIds = [];

			for (var i = 0; i < this.$elements.length; i++)
			{
				var $element = $(this.$elements[i]);
				selectedElementIds.push($element.data('id'));
			}

			this.modal = new Craft.ElementSelectorModal({
				elementType: this.elementType,
				sources: this.sources,
				disabledElementIds: selectedElementIds,
				onSelect: $.proxy(this, 'selectElements')
			});
		}
		else
		{
			this.modal.show();
		}
	},

	selectElements: function(elements)
	{
		for (var i = 0; i < elements.length; i++)
		{
			var element = elements[i],
				$element = $(
					'<div class="element removable" data-id="'+element.id+'">' +
						'<input type="hidden" name="'+this.name+'[]" value="'+element.id+'">' +
						'<a class="delete icon" title="'+Craft.t('Remove')+'"></a>' +
						'<span class="label">'+element.label+'</span>' +
					'</div>'
				);

			$element.appendTo(this.$elementsContainer);

			this.$elements = this.$elements.add($element);
			this.initElements($element);
		}
	}
});
