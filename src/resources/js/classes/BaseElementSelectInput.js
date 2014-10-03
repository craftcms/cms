/**
 * Element Select input
 */
Craft.BaseElementSelectInput = Garnish.Base.extend(
{
	id: null,
	name: null,
	elementType: null,
	sources: null,
	criteria: null,
	sourceElementId: null,
	limit: null,
	modalStorageKey: null,

	elementSelect: null,
	elementSort: null,
	modal: null,

	$container: null,
	$elementsContainer: null,
	$elements: null,
	$addElementBtn: null,

	selectable: true,
	sortable: true,

	init: function(id, name, elementType, sources, criteria, sourceElementId, limit, modalStorageKey)
	{
		this.id = id;
		this.name = name;
		this.elementType = elementType;
		this.sources = sources;
		this.criteria = criteria;
		this.sourceElementId = sourceElementId;
		this.limit = limit;

		if (modalStorageKey)
		{
			this.modalStorageKey = 'BaseElementSelectInput.'+modalStorageKey;
		}

		this.$container = this.getContainer();
		this.$elementsContainer = this.getElementsContainer();
		this.$elements = this.getElements();
		this.$addElementBtn = this.getAddElementsBtn();

		this.updateAddElementsBtn();

		if (this.selectable)
		{
			this.elementSelect = new Garnish.Select(this.$elements, {
				multi: true,
				filter: ':not(.delete)'
			});
		}

		if (this.sortable)
		{
			this.elementSort = new Garnish.DragSort({
				container: this.$elementsContainer,
				filter: (this.selectable ? $.proxy(function() {
					return this.elementSelect.getSelectedItems();
				}, this) : null),
				ignoreHandleSelector: '.delete',
				onSortChange: (this.selectable ? $.proxy(function() {
					this.elementSelect.resetItemOrder();
				}, this) : null)
			});
		}

		this.initElements(this.$elements);

		this.addListener(this.$addElementBtn, 'activate', 'showModal');
	},

	getContainer: function()
	{
		return $('#'+this.id);
	},

	getElementsContainer: function()
	{
		return this.$container.children('.elements');
	},

	getElements: function()
	{
		return this.$elementsContainer.children();
	},

	getAddElementsBtn: function()
	{
		return this.$container.children('.btn.add');
	},

	canAddMoreElements: function()
	{
		return (!this.limit || this.$elements.length < this.limit);
	},

	updateAddElementsBtn: function()
	{
		if (this.canAddMoreElements())
		{
			this.enableAddElementsBtn();
		}
		else
		{
			this.disableAddElementsBtn();
		}
	},

	disableAddElementsBtn: function()
	{
		this.$addElementBtn.addClass('disabled');
	},

	enableAddElementsBtn: function()
	{
		this.$addElementBtn.removeClass('disabled');
	},

	initElements: function($elements)
	{
		if (this.selectable)
		{
			this.elementSelect.addItems($elements);
		}

		if (this.sortable)
		{
			this.elementSort.addItems($elements);
		}

		$elements.find('.delete').on('click', $.proxy(function(ev)
		{
			this.removeElement($(ev.currentTarget).closest('.element'));
		}, this));

		this.addListener($elements, 'dblclick', function(ev)
		{
			Craft.showElementEditor($(ev.currentTarget));
		});
	},

	removeElement: function(element)
	{
		var $element = $(element);

		this.$elements = this.$elements.not($element);

		if (this.selectable)
		{
			this.elementSelect.removeItems($element);
		}

		if (this.modal && $element.data('id'))
		{
			this.modal.elementIndex.enableElementsById($element.data('id'));
		}

		if (this.$addElementBtn && this.$addElementBtn.length)
		{
			this.updateAddElementsBtn();
		}

		$element.css('z-index', 0);

		var animateCss = {
			opacity: -1
		};
		animateCss['margin-'+Craft.left] = -($element.outerWidth() + parseInt($element.css('margin-'+Craft.right)));

		$element.velocity(animateCss, 'fast', function() {
			$element.remove();
		});

	},

	showModal: function()
	{
		// Make sure we haven't reached the limit
		if (!this.canAddMoreElements())
		{
			return;
		}

		if (!this.modal)
		{
			this.modal = this.createModal();
		}
		else
		{
			this.modal.show();
		}
	},

	createModal: function()
	{
		return Craft.createElementSelectorModal(this.elementType, {
			storageKey:         this.modalStorageKey,
			sources:            this.sources,
			criteria:           this.criteria,
			multiSelect:        (this.limit != 1),
			disabledElementIds: this.getSelectedElementIds(),
			onSelect:           $.proxy(this, 'onModalSelect')
		});
	},

	getSelectedElementIds: function()
	{
		var ids = [];

		if (this.sourceElementId)
		{
			ids.push(this.sourceElementId);
		}

		for (var i = 0; i < this.$elements.length; i++)
		{
			ids.push($(this.$elements[i]).data('id'));
		}

		return ids;
	},

	onModalSelect: function(elements)
	{
		if (this.limit)
		{
			// Cut off any excess elements
			var slotsLeft = this.limit - this.$elements.length;

			if (elements.length > slotsLeft)
			{
				elements = elements.slice(0, slotsLeft);
			}
		}

		this.selectElements(elements);

		if (this.modal.elementIndex)
		{
			this.modal.elementIndex.disableElementsById(this.getSelectedElementIds());
		}
	},

	selectElements: function(elements)
	{
		for (var i = 0; i < elements.length; i++)
		{
			var element = elements[i],
				$element = this.createNewElement(element);

			// Animate it into place
			var origOffset = element.$element.offset(),
				destOffset = $element.offset();

			var css = {
				top:    origOffset.top - destOffset.top,
				zIndex: 10000
			};
			css[Craft.left] = origOffset.left - destOffset.left;

			$element.css(css);

			var animateCss = {
				top: 0
			};
			animateCss[Craft.left] = 0;

			$element.velocity(animateCss, function() {
				$(this).css('z-index', 1);
			});

			this.initElements($element);
			this.$elements = this.$elements.add($element);
		}

		this.updateAddElementsBtn();
		this.onSelectElements();
	},

	createNewElement: function(elementInfo)
	{
		var $element = elementInfo.$element.clone();

		// Make a couple tweaks
		$element.addClass('removable');
		$element.prepend('<input type="hidden" name="'+this.name+'[]" value="'+elementInfo.id+'">' +
			'<a class="delete icon" title="'+Craft.t('Remove')+'"></a>');

		$element.appendTo(this.$elementsContainer);

		return $element;
	},

	onSelectElements: function()
	{
		this.trigger('selectElements');
	}

});
