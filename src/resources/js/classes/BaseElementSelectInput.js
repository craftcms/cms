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
		this.$addElementBtn = this.getAddElementsBtn();

		if (this.limit == 1)
		{
			this.sortable = false;
			this.$addElementBtn
				.css('position', 'absolute')
				.css('top', 0)
				.css(Craft.left, 0);
		}

		if (this.selectable)
		{
			this.elementSelect = new Garnish.Select({
				multi: this.sortable,
				filter: ':not(.delete)'
			});
		}

		if (this.sortable)
		{
			this.elementSort = new Garnish.DragSort({
				container: this.$elementsContainer,
				filter: (this.selectable ? $.proxy(function()
				{
					// Only return all the selected items if the target item is selected
					if (this.elementSort.$targetItem.hasClass('sel'))
					{
						return this.elementSelect.getSelectedItems();
					}
					else
					{
						return this.elementSort.$targetItem;
					}
				}, this) : null),
				ignoreHandleSelector: '.delete',
				collapseDraggees: true,
				magnetStrength: 4,
				helperLagBase: 1.5,
				onSortChange: (this.selectable ? $.proxy(function() {
					this.elementSelect.resetItemOrder();
				}, this) : null)
			});
		}

		// Add the elements already on the page
		this.resetElements();

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
		if (this.$addElementBtn && !this.$addElementBtn.hasClass('disabled'))
		{
			this.$addElementBtn.addClass('disabled');

			if (this.limit == 1)
			{
				this.$addElementBtn.velocity('fadeOut', Craft.BaseElementSelectInput.ADD_FX_DURATION);
			}
		}
	},

	enableAddElementsBtn: function()
	{
		if (this.$addElementBtn && this.$addElementBtn.hasClass('disabled'))
		{
			this.$addElementBtn.removeClass('disabled');

			if (this.limit == 1)
			{
				this.$addElementBtn.velocity('fadeIn', Craft.BaseElementSelectInput.REMOVE_FX_DURATION);
			}
		}
	},

	resetElements: function()
	{
		this.$elements = $();
		this.addElements(this.getElements());
	},

	addElements: function($elements)
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

		this.$elements = this.$elements.add($elements);
		this.updateAddElementsBtn();
	},

	removeElements: function($elements)
	{
		if (this.selectable)
		{
			this.elementSelect.removeItems($elements);
		}

		if (this.modal)
		{
			var ids = [];

			for (var i = 0; i < $elements.length; i++)
			{
				var id = $elements.eq(i).data('id');

				if (id)
				{
					ids.push(id);
				}
			}

			if (ids.length)
			{
				this.modal.elementIndex.enableElementsById(ids);
			}
		}

		// Disable the hidden input in case the form is submitted before this element gets removed from the DOM
		$elements.children('input').prop('disabled', true);

		this.$elements = this.$elements.not($elements);
		this.updateAddElementsBtn();
	},

	removeElement: function($element)
	{
		this.removeElements($element);
		this.animateElementAway($element, function() {
			$element.remove();
		});
	},

	animateElementAway: function($element, callback)
	{
		$element.css('z-index', 0);

		var animateCss = {
			opacity: -1
		};
		animateCss['margin-'+Craft.left] = -($element.outerWidth() + parseInt($element.css('margin-'+Craft.right)));

		$element.velocity(animateCss, Craft.BaseElementSelectInput.REMOVE_FX_DURATION, callback);
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
		return Craft.createElementSelectorModal(this.elementType, this.getModalSettings());
	},

	getModalSettings: function()
	{
		return {
			storageKey:         this.modalStorageKey,
			sources:            this.sources,
			criteria:           this.criteria,
			multiSelect:        (this.limit != 1),
			disabledElementIds: this.getDisabledElementIds(),
			onSelect:           $.proxy(this, 'onModalSelect')
		};
	},

	getSelectedElementIds: function()
	{
		var ids = [];

		for (var i = 0; i < this.$elements.length; i++)
		{
			ids.push(this.$elements.eq(i).data('id'));
		}

		return ids;
	},

	getDisabledElementIds: function()
	{
		var ids = this.getSelectedElementIds();

		if (this.sourceElementId)
		{
			ids.push(this.sourceElementId);
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
		this.updateDisabledElementsInModal();
	},

	selectElements: function(elements)
	{
		for (var i = 0; i < elements.length; i++)
		{
			var element = elements[i],
				$element = this.createNewElement(element);

			this.appendElement($element);
			this.addElements($element);
			this.animateElementIntoPlace(element.$element, $element);
		}

		this.onSelectElements();
	},

	createNewElement: function(elementInfo)
	{
		var $element = elementInfo.$element.clone();

		// Make a couple tweaks
		$element.addClass('removable');
		$element.prepend('<input type="hidden" name="'+this.name+'[]" value="'+elementInfo.id+'">' +
			'<a class="delete icon" title="'+Craft.t('Remove')+'"></a>');

		return $element;
	},

	appendElement: function($element)
	{
		$element.appendTo(this.$elementsContainer);
	},

	animateElementIntoPlace: function($modalElement, $inputElement)
	{
		var origOffset = $modalElement.offset(),
			destOffset = $inputElement.offset(),
			$helper = $inputElement.clone().appendTo(Garnish.$bod);

		$inputElement.css('visibility', 'hidden');

		$helper.css({
			position: 'absolute',
			zIndex: 10000,
			top: origOffset.top,
			left: origOffset.left
		});

		var animateCss = {
			top: destOffset.top,
			left: destOffset.left
		};

		$helper.velocity(animateCss, Craft.BaseElementSelectInput.ADD_FX_DURATION, function() {
			$helper.remove();
			$inputElement.css('visibility', 'visible');
		});
	},

	updateDisabledElementsInModal: function()
	{
		if (this.modal.elementIndex)
		{
			this.modal.elementIndex.disableElementsById(this.getDisabledElementIds());
		}
	},

	getElementById: function(id)
	{
		for (var i = 0; i < this.$elements.length; i++)
		{
			var $element = this.$elements.eq(i);

			if ($element.data('id') == id)
			{
				return $element;
			}
		}
	},

	onSelectElements: function()
	{
		this.trigger('selectElements');
	}
},
{
	ADD_FX_DURATION: 400,
	REMOVE_FX_DURATION: 200
});
