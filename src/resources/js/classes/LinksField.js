/**
 * Links Block
 */
Blocks.LinksField = Garnish.Base.extend({

	_$inputContainer: null,
	_$inputTbody: null,
	_$fillerRows: null,
	_$showModalBtn: null,
	_$removeLinksBtn: null,

	_$modalBody: null,
	_$modalTbody: null,
	_$cancelBtn: null,
	_$selectBtn: null,

	_id: null,
	_name: null,
	_settings: null,
	_selectedIds: null,
	_minSlots: null,
	_modal: null,
	_inputSelect: null,
	_modalSelect: null,
	_inputSort: null,

	init: function(id, name, settings, selectedIds)
	{
		this._id = id;
		this._name = name;
		this._settings = settings;
		this._selectedIds = selectedIds;

		this._$inputContainer = $('#'+this._id);

		// Find the field buttons
		var $buttons = this._$inputContainer.next();
		this._$showModalBtn = $buttons.find('.btn.add');
		this._$removeLinksBtn = $buttons.find('.btn.remove');

		// Find the preselected elements
		var $table = this._$inputContainer.children('table');
		this._$inputTbody = $table.children('tbody');
		var $rows = this._$inputTbody.children(':not(.filler)');
		var $elements = $rows.find('div.element');

		this._inputSelect = new Garnish.Select(this._$inputContainer, $elements, {
			multi: true,
			onSelectionChange: $.proxy(function() {
				if (this._inputSelect.totalSelected)
				{
					this._$removeLinksBtn.enable();
				}
				else
				{
					this._$removeLinksBtn.disable();
				}
			}, this)
		});

		this._inputSort = new Blocks.DataTableSorter($table, {
			handle: '.element',
			filter: $.proxy(function() {
				return this._inputSelect.getSelectedItems().closest('tr');
			}, this),
			onSortChange: $.proxy(function() {
				this._inputSelect.resetItemOrder();
			}, this)
		});

		// Find any filler rows
		this._minSlots = settings.limit ? Math.min(3, settings.limit) : 3;
		this._$fillerRows = this._$inputTbody.children('.filler');

		this.addListener(this._$showModalBtn, 'activate', '_showModal');
		this.addListener(this._$removeLinksBtn, 'activate', '_removeSelectedElements');
	},

	_buildModal: function()
	{
		var $modal = $('<div class="addlinksmodal modal"/>').appendTo(Garnish.$bod),
			$header = $('<header class="header"><h1>'+this._settings.addLabel+'</h1></header>').appendTo($modal);

		this._$modalBody = $('<div class="body"/>').appendTo($modal);

		var $footer = $('<footer class="footer"/>').appendTo($modal),
			$rightList = $('<ul class="right"/>').appendTo($footer),
			$cancelBtnContainer = $('<li/>').appendTo($rightList),
			$selectBtnContainer = $('<li/>').appendTo($rightList);

		this._$cancelBtn = $('<div class="btn">'+Blocks.t('Cancel')+'</div>').appendTo($cancelBtnContainer);
		this._$selectBtn = $('<div class="btn submit disabled">'+this._settings.addLabel+'</div>').appendTo($selectBtnContainer);

		this._modal = new Garnish.Modal($modal);

		this._updateModal();

		this.addListener(this._$cancelBtn, 'activate', '_hideModal');
		this.addListener(this._$selectBtn, 'activate', '_selectElements');
	},

	_showModal: function()
	{
		if (!this._modal)
		{
			this._buildModal();
		}
		else
		{
			this._inputSelect.deselectAll();
			this._modalSelect.deselectAll();
			this._modal.show();

			setTimeout($.proxy(function() {
				this._$modalBody.focus();
			}, this), 50);
		}
	},

	_hideModal: function()
	{
		this._modal.hide();
		this._$inputContainer.focus();
	},

	_updateModal: function()
	{
		var data = {
			type: this._settings.type,
			name: this._name,
			settings: JSON.stringify(this._settings.elementTypeSettings),
			selectedIds: JSON.stringify(this._selectedIds)
		};

		Blocks.postActionRequest('links/getModalBody', data, $.proxy(function(response) {
			this._$modalBody.html(response);

			this._$modalTbody = this._$modalBody.find('tbody:first');
			var $elements = this._$modalTbody.children(':not(.hidden)').find('.element');

			this._modalSelect = new Garnish.Select(this._$modalBody, $elements, {
				multi: true,
				waitForDblClick: true,
				onSelectionChange: $.proxy(function() {
					if (this._modalSelect.totalSelected)
					{
						this._$selectBtn.enable();
					}
					else
					{
						this._$selectBtn.disable();
					}
				}, this)
			});

			this.addListener(this._$modalBody, 'dblclick', '_selectElements');
		}, this));
	},

	_selectElements: function()
	{
		if (!this._modalSelect.totalSelected)
		{
			return;
		}

		var $elements = this._modalSelect.getSelectedItems();
		this._modalSelect.removeItems($elements);

		// Delete extra filler rows
		var count = Math.min($elements.length, this._$fillerRows.length);
		this._$fillerRows.slice(0, count).remove();
		this._$fillerRows = this._$fillerRows.slice(count);

		// Clone the rows and add them to the field
		var $rows = $elements.closest('tr'),
			$clonedRows = $rows.clone();

		if (this._$fillerRows.length)
		{
			$clonedRows.insertBefore(this._$fillerRows.first());
		}
		else
		{
			$clonedRows.appendTo(this._$inputTbody)
		}

		// Finish up
		this._modal.hide();
		this._$removeLinksBtn.enable();

		$elements = $clonedRows.find('.element');
		this._inputSelect.addItems($elements);
		this._inputSort.addItems($clonedRows);

		this._$inputContainer.focus();

		// Hide the original rows once the modal has faded out
		setTimeout(function() {
			$rows.addClass('hidden');
		}, 200);
	},

	_removeSelectedElements: function()
	{
		if (!this._inputSelect.totalSelected)
		{
			return;
		}

		// Find the selected links
		var $elements = this._inputSelect.getSelectedItems(),
			$rows = $elements.closest('tr');

		// Remove them
		this._inputSelect.removeItems($elements);
		this._inputSort.removeItems($rows);
		$rows.remove();

		// Add filler rows?
		var totalSelectedElements = this._inputSelect.$items.length;
		var missingFillerRows = this._minSlots - totalSelectedElements;
		for (var i = this._$fillerRows.length; i < missingFillerRows; i++)
		{
			var $fillerRow = $('<tr class="filler"><td></td></tr>').appendTo(this._$inputTbody);
			this._$fillerRows = this._$fillerRows.add($fillerRow);
		}

		// Disable the Remove Links button
		this._$removeLinksBtn.disable();

		// Show them in the modal
		if (this._modal)
		{
			var $hiddenElements = this._$modalTbody.children('.hidden').find('.element');

			for (var i = 0; i < $elements.length; i++)
			{
				var id = $($elements[i]).attr('data-id'),
					$element = $hiddenElements.filter('[data-id='+id+']:first');

				$element.closest('tr').removeClass('hidden');
				this._modalSelect.addItems($element);
			}
		}

		this._$inputContainer.focus();
	}
});
