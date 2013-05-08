/**
 * Element Select input
 */
Craft.ElementSelectInput = Garnish.Base.extend({

	id: null,
	name: null,
	elementType: null,
	sources: null,
	limit: null,
	totalElements: 0,
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

		this.totalElements = this.$elements.length;

		if (this.limit && this.totalElements == this.limit)
		{
			this.$addElementBtn.addClass('disabled');
		}

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
			this.totalElements--;
			this.$addElementBtn.removeClass('disabled');
		}, this));
	},

	showModal: function()
	{
		// Make sure we haven't reached the limit
		if (this.limit && this.totalElements == this.limit)
		{
			return;
		}

		if (!this.modal)
		{
			var selectedElementIds = [];

			for (var i = 0; i < this.$elements.length; i++)
			{
				var $element = $(this.$elements[i]);
				selectedElementIds.push($element.data('id'));
			}

			this.modal = new Craft.ElementSelectorModal({
				id: this.id,
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
		if (this.limit)
		{
			var slotsLeft = this.limit - this.totalElements,
				max = Math.min(elements.length, slotsLeft);
		}
		else
		{
			var max = elements.length;
		}

		for (var i = 0; i < max; i++)
		{
			var element = elements[i],
				$element = $(
					'<div class="element removable'+(element.hasThumb ? ' hasthumb' : '')+'" data-id="'+element.id+'">' +
						(element.hasThumb ? '<div class="thumb thumb'+element.id+'"></div>' : '') +
						'<input type="hidden" name="'+this.name+'[]" value="'+element.id+'">' +
						'<a class="delete icon" title="'+Craft.t('Remove')+'"></a>' +
						(element.status ? '<div class="status '+element.status+'"></div> ' : '') +
						'<span class="label">'+element.label+'</span>' +
					'</div>'
				);

			$element.appendTo(this.$elementsContainer);

			this.$elements = this.$elements.add($element);
			this.initElements($element);
		}

		this.totalElements += max;

		if (this.limit && this.totalElements == this.limit)
		{
			this.$addElementBtn.addClass('disabled');
		}
	}
});
