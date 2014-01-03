/**
 * Element Select input
 */
Craft.BaseElementSelectInput = Garnish.Base.extend({

	id: null,
	name: null,
	elementType: null,
	sources: null,
	criteria: null,
	sourceElementId: null,
	limit: null,
	storageKey: null,

	totalElements: 0,
	elementSelect: null,
	elementSort: null,
	modal: null,

	$container: null,
	$elementsContainer: null,
	$elements: null,
	$addElementBtn: null,

	init: function(id, name, elementType, sources, criteria, sourceElementId, limit, storageKey)
	{
		this.id = id;
		this.name = name;
		this.elementType = elementType;
		this.sources = sources;
		this.criteria = criteria;
		this.sourceElementId = sourceElementId;
		this.limit = limit;
		this.storageKey = storageKey;

		this.$container = $('#'+this.id);
		this.$elementsContainer = this.$container.children('.elements');
		this.$elements = this.$elementsContainer.children();
		this.$addElementBtn = this.$container.children('.btn.add');

		this.totalElements = this.$elements.length;

		if (this.limit && this.totalElements >= this.limit)
		{
			this.$addElementBtn.addClass('disabled');
		}

		this.elementSelect = new Garnish.Select(this.$elements, {
			multi: true,
			filter: ':not(.delete)'
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

		$elements.find('.delete').on('click', $.proxy(function(ev)
		{
			var $element = $(ev.currentTarget).closest('.element');

			this.$elements = this.$elements.not($element);
			this.elementSelect.removeItems($element);

			if (this.modal)
			{
				this.modal.elementIndex.enableElementsById($element.data('id'));
			}

			this.totalElements--;

			if (this.$addElementBtn)
			{
				this.$addElementBtn.removeClass('disabled');
			}

			$element.css('z-index', 0);

			$element.animate({
				marginLeft: -($element.outerWidth() + parseInt($element.css('margin-right'))),
				opacity: -1 // double speed!
			}, 'fast', function() {
				$element.remove();
			});

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
			var disabledElementIds = [];

			if (this.sourceElementId)
			{
				disabledElementIds.push(this.sourceElementId);
			}

			for (var i = 0; i < this.$elements.length; i++)
			{
				var $element = $(this.$elements[i]);
				disabledElementIds.push($element.data('id'));
			}

			this.modal = Craft.createElementSelectorModal(this.elementType, {
				storageKey: (this.storageKey ? 'BaseElementSelectInput.'+this.storageKey : null),
				sources: this.sources,
				criteria: this.criteria,
				multiSelect: true,
				disableOnSelect: true,
				disabledElementIds: disabledElementIds,
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
		this.elementSelect.deselectAll();

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
				$newElement = element.$element.clone();

			// Make a couple tweaks
			$newElement.addClass('removable');
			$newElement.prepend('<input type="hidden" name="'+this.name+'[]" value="'+element.id+'">' +
				'<a class="delete icon" title="'+Craft.t('Remove')+'"></a>');

			$newElement.appendTo(this.$elementsContainer);

			// Animate it into place
			var origOffset = element.$element.offset(),
				destOffset = $newElement.offset();

			$newElement.css({
				left:   origOffset.left - destOffset.left,
				top:    origOffset.top - destOffset.top,
				zIndex: 10000
			});

			$newElement.animate({
				left: 0,
				top: 0
			}, function() {
				$(this).css('z-index', 1);
			});

			this.$elements = this.$elements.add($newElement);
			this.initElements($newElement);
		}

		this.totalElements += max;

		if (this.limit && this.totalElements == this.limit)
		{
			this.$addElementBtn.addClass('disabled');
		}
	},

	onHide: function ()
	{

	}

});
