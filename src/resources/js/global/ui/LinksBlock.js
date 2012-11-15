(function($) {

/**
 * Links Block
 */
Blocks.ui.LinksBlock = Blocks.Base.extend({

	_$inputContainer: null,
	_$inputTbody: null,
	_$fillerRows: null,
	_$showModalBtn: null,
	_$removeLinksBtn: null,

	_$modalBody: null,
	_$modalTbody: null,
	_$cancelBtn: null,
	_$selectBtn: null,

	_name: null,
	_settings: null,
	_selectedIds: null,
	_minSlots: null,
	_modal: null,
	_inputSelect: null,
	_modalSelect: null,
	_inputSort: null,

	init: function(name, settings, selectedIds)
	{
		this._name = name;
		this._settings = settings;
		this._selectedIds = selectedIds;

		this._$inputContainer = $('#blocks-'+this._name);

		// Find the field buttons
		var $buttons = this._$inputContainer.next();
		this._$showModalBtn = $buttons.find('.btn.add');
		this._$removeLinksBtn = $buttons.find('.btn.remove');

		// Find the preselected entities
		var $table = this._$inputContainer.children('table');
		this._$inputTbody = $table.children('tbody');
		var $rows = this._$inputTbody.children(':not(.filler)');
		var $entities = $rows.find('div.entity');

		this._inputSelect = new Blocks.ui.Select(this._$inputContainer, $entities, {
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

		this._inputSort = new Blocks.ui.DataTableSorter($table, {
			handle: '.entity',
			filter: $.proxy(function() {
				return this._inputSelect.getSelectedItems().closest('tr');
			}, this),
			onSortChange: $.proxy(function() {
				this._inputSelect.resetItemOrder();
			}, this)
		});

		// Find any filler rows
		this._minSlots = settings.limit ? Math.min(3, settings.limit) : 3;
		if ($entities.length < this._minSlots)
		{
			this._$fillerRows = this._$inputTbody.children('.filler');
		}

		this.addListener(this._$showModalBtn, 'activate', '_showModal');
		this.addListener(this._$removeLinksBtn, 'activate', '_removeSelectedEntities');
	},

	_buildModal: function()
	{
		var $modal = $('<div class="addlinksmodal modal"/>').appendTo(Blocks.$body),
			$header = $('<header class="header"><h1>'+this._settings.addLabel+'</h1></header>').appendTo($modal);

		this._$modalBody = $('<div class="body"/>').appendTo($modal);

		var $footer = $('<footer class="footer"/>').appendTo($modal),
			$rightList = $('<ul class="right"/>').appendTo($footer),
			$cancelBtnContainer = $('<li/>').appendTo($rightList),
			$selectBtnContainer = $('<li/>').appendTo($rightList);

		this._$cancelBtn = $('<div class="btn">'+Blocks.t('Cancel')+'</div>').appendTo($cancelBtnContainer);
		this._$selectBtn = $('<div class="btn submit disabled">'+this._settings.addLabel+'</div>').appendTo($selectBtnContainer);

		this._modal = new Blocks.ui.Modal($modal);

		this._updateModal();

		this.addListener(this._$cancelBtn, 'activate', '_hideModal');
		this.addListener(this._$selectBtn, 'activate', '_selectEntities');
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
			settings: this._settings.linkTypeSettings,
			selectedIds: this._selectedIds
		};

		Blocks.postActionRequest('links/getModalBody', data, $.proxy(function(response) {
			this._$modalBody.html(response);

			this._$modalTbody = this._$modalBody.find('tbody:first');
			var $entities = this._$modalTbody.children(':not(.hidden)').find('.entity');

			this._modalSelect = new Blocks.ui.Select(this._$modalBody, $entities, {
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

			this.addListener(this._$modalBody, 'dblclick', '_selectEntities');
		}, this));
	},

	_selectEntities: function()
	{
		if (!this._modalSelect.totalSelected)
		{
			return;
		}

		var $entities = this._modalSelect.getSelectedItems();
		this._modalSelect.removeItems($entities);

		// Delete extra filler rows
		var count = Math.min($entities.length, this._$fillerRows.length);
		this._$fillerRows.slice(0, count).remove();
		this._$fillerRows = this._$fillerRows.slice(count);

		// Clone the rows and add them to the field
		var $rows = $entities.closest('tr'),
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

		$entities = $clonedRows.find('.entity');
		this._inputSelect.addItems($entities);
		this._inputSort.addItems($clonedRows);

		this._$inputContainer.focus();

		// Hide the original rows once the modal has faded out
		setTimeout(function() {
			$rows.addClass('hidden');
		}, 200);
	},

	_removeSelectedEntities: function()
	{
		if (!this._inputSelect.totalSelected)
		{
			return;
		}

		// Find the selected links
		var $entities = this._inputSelect.getSelectedItems(),
			$rows = $entities.closest('tr');

		// Remove them
		this._inputSelect.removeItems($entities);
		this._inputSort.removeItems($rows);
		$rows.remove();

		// Add filler rows?
		var totalSelectedEntities = this._inputSelect.$items.length;
		var missingFillerRows = this._minSlots - totalSelectedEntities;
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
			var $hiddenEntities = this._$modalTbody.children('.hidden').find('.entity');

			for (var i = 0; i < $entities.length; i++)
			{
				var id = $($entities[i]).attr('data-id'),
					$entity = $hiddenEntities.filter('[data-id='+id+']:first');

				$entity.closest('tr').removeClass('hidden');
				this._modalSelect.addItems($entity);
			}
		}

		this._$inputContainer.focus();
	}

});

})(jQuery);
