(function($){


/**
 * Matrix configurator class
 */
Craft.MatrixConfigurator = Garnish.Base.extend({

	fieldTypeInfo: null,

	inputNamePrefix: null,
	inputIdPrefix: null,

	$container: null,

	$recordTypesColumnContainer: null,
	$fieldsColumnContainer: null,
	$fieldSettingsColumnContainer: null,

	$recordTypeItemsContainer: null,
	$fieldItemsContainer: null,
	$fieldSettingItemsContainer: null,

	$newRecordTypeBtn: null,
	$newFieldBtn: null,

	recordTypes: null,
	selectedRecordType: null,
	recordTypeSort: null,
	totalNewRecordTypes: 0,

	init: function(fieldTypeInfo, inputNamePrefix)
	{
		this.fieldTypeInfo = fieldTypeInfo;

		this.inputNamePrefix = inputNamePrefix;
		this.inputIdPrefix = Craft.formatInputId(this.inputNamePrefix);

		this.$container = $('.matrix-configurator:first .input:first');

		this.$recordTypesColumnContainer = this.$container.children('.record-types').children();
		this.$fieldsColumnContainer = this.$container.children('.fields').children();
		this.$fieldSettingsColumnContainer = this.$container.children('.field-settings').children();

		this.$recordTypeItemsContainer = this.$recordTypesColumnContainer.children('.items');
		this.$fieldItemsContainer = this.$fieldsColumnContainer.children('.items');
		this.$fieldSettingItemsContainer = this.$fieldSettingsColumnContainer.children('.items');

		this.$newRecordTypeBtn = this.$recordTypeItemsContainer.children('.btn');
		this.$newFieldBtn = this.$fieldItemsContainer.children('.btn');

		// Find the existing record types
		this.recordTypes = {};

		var $recordTypeItems = this.$recordTypeItemsContainer.children('.matrixconfigitem');

		for (var i = 0; i < $recordTypeItems.length; i++)
		{
			var $item = $($recordTypeItems[i]),
				id = $item.data('id');

			this.recordTypes[id] = new RecordType(this, $item);

			// Is this a new record type?
			var newMatch = (typeof id == 'string' && id.match(/new(\d+)/));

			if (newMatch && newMatch[1] > this.totalNewRecordTypes)
			{
				this.totalNewRecordTypes = parseInt(newMatch[1]);
			}
		}

		this.recordTypeSort = new Garnish.DragSort($recordTypeItems, {
			caboose: '<div/>',
			handle: '.move',
			axis: 'y'
		});

		this.addListener(this.$newRecordTypeBtn, 'click', 'addRecordType');
		this.addListener(this.$newFieldBtn, 'click', 'addFieldToSelectedRecordType');
	},

	getFieldTypeInfo: function(type)
	{
		for (var i = 0; i < this.fieldTypeInfo.length; i++)
		{
			if (this.fieldTypeInfo[i].type == type)
			{
				return this.fieldTypeInfo[i];
			}
		}
	},

	addRecordType: function()
	{
		var recordTypeSettingsModal = this.getRecordTypeSettingsModal();

		this.recordTypeSettingsModal.show();

		this.recordTypeSettingsModal.onSubmit = $.proxy(function(name, handle)
		{
			this.totalNewRecordTypes++;
			var id = 'new'+this.totalNewRecordTypes;

			var $item = $(
				'<div class="matrixconfigitem mci-recordtype" data-id="'+id+'">' +
					'<div class="name"></div>' +
					'<div class="handle code"></div>' +
					'<div class="actions">' +
						'<a class="move icon" title="'+Craft.t('Reorder')+'"></a>' +
						'<a class="settings icon" title="'+Craft.t('Settings')+'"></a>' +
					'</div>' +
					'<input class="hidden" name="types[Matrix][recordTypes]['+id+'][name]">' +
					'<input class="hidden" name="types[Matrix][recordTypes]['+id+'][handle]">' +
				'</div>'
			).insertBefore(this.$newRecordTypeBtn);

			this.recordTypes[id] = new RecordType(this, $item);
			this.recordTypes[id].applySettings(name, handle);
			this.recordTypes[id].select();
			this.recordTypes[id].addField();

			this.recordTypeSort.addItems($item);
		}, this);
	},

	addFieldToSelectedRecordType: function()
	{
		if (this.selectedRecordType)
		{
			this.selectedRecordType.addField();
		}
	},

	getRecordTypeSettingsModal: function()
	{
		if (!this.recordTypeSettingsModal)
		{
			this.recordTypeSettingsModal = new RecordTypeSettingsModal();
		}

		return this.recordTypeSettingsModal;
	}
});


/**
 * Record type settings modal class
 */
var RecordTypeSettingsModal = Garnish.Modal.extend({

	init: function()
	{
		this.base();

		this.$form = $('<form class="modal"/>').appendTo(Garnish.$bod);
		this.setContainer(this.$form);

		this.$body = $('<div class="body"/>').appendTo(this.$form);
		this.$nameField = $('<div class="field"/>').appendTo(this.$body);
		this.$nameHeading = $('<div class="heading"/>').appendTo(this.$nameField);
		this.$nameLabel = $('<label for="new-record-type-name">'+Craft.t('Name')+'</label>').appendTo(this.$nameHeading);
		this.$nameInstructions = $('<p class="instructions">'+Craft.t('What this record type will be called in the CP.')+'</p>').appendTo(this.$nameHeading);
		this.$nameInputContainer = $('<div class="input"/>').appendTo(this.$nameField);
		this.$nameInput = $('<input type="text" class="text fullwidth" id="new-record-type-name"/>').appendTo(this.$nameInputContainer);
		this.$nameErrorList = $('<ul class="errors"/>').appendTo(this.$nameInputContainer).hide();
		this.$handleField = $('<div class="field"/>').appendTo(this.$body);
		this.$handleHeading = $('<div class="heading"/>').appendTo(this.$handleField);
		this.$handleLabel = $('<label for="new-record-type-handle">'+Craft.t('Handle')+'</label>').appendTo(this.$handleHeading);
		this.$handleInstructions = $('<p class="instructions">'+Craft.t('How you’ll refer to this record type in the templates.')+'</p>').appendTo(this.$handleHeading);
		this.$handleInputContainer = $('<div class="input"/>').appendTo(this.$handleField);
		this.$handleInput = $('<input type="text" class="text fullwidth code" id="new-record-type-handle"/>').appendTo(this.$handleInputContainer);
		this.$handleErrorList = $('<ul class="errors"/>').appendTo(this.$handleInputContainer).hide();
		this.$deleteBtn = $('<a class="error left hidden" style="line-height: 30px;">'+Craft.t('Delete')+'</a>').appendTo(this.$body);
		this.$buttons = $('<div class="buttons right" style="margin-top: 0;"/>').appendTo(this.$body);
		this.$cancelBtn = $('<div class="btn">'+Craft.t('Cancel')+'</div>').appendTo(this.$buttons);
		this.$submitBtn = $('<input type="submit" class="btn submit"/>').appendTo(this.$buttons);

		this.handleGenerator = new Craft.HandleGenerator(this.$nameInput, this.$handleInput);

		this.addListener(this.$cancelBtn, 'click', 'hide');
		this.addListener(this.$form, 'submit', 'onFormSubmit');
		this.addListener(this.$deleteBtn, 'click', 'onDeleteClick');
	},

	onFormSubmit: function(ev)
	{
		ev.preventDefault();

		// Prevent multi form submits with the return key
		if (!this.visible)
		{
			return;
		}

		if (this.handleGenerator.listening)
		{
			// Give the handle a chance to catch up with the input
			this.handleGenerator.updateTarget();
		}

		// Basic validation
		var name = Craft.trim(this.$nameInput.val()),
			handle = Craft.trim(this.$handleInput.val());

		if (!name || !handle)
		{
			Garnish.shake(this.$form);
		}
		else
		{
			this.hide();
			this.onSubmit(name, handle);
		}
	},

	onDeleteClick: function()
	{
		if (confirm(Craft.t('Are you sure you want to delete this record type?')))
		{
			this.hide();
			this.onDelete();
		}
	},

	show: function(name, handle, errors)
	{
		this.$nameInput.val(typeof name == 'string' ? name : '');
		this.$handleInput.val(typeof handle == 'string' ? handle : '');

		if (!handle)
		{
			this.handleGenerator.startListening();
		}
		else
		{
			this.handleGenerator.stopListening();
		}

		if (typeof name == 'undefined')
		{
			this.$deleteBtn.addClass('hidden');
			this.$submitBtn.val(Craft.t('Create'));
		}
		else
		{
			this.$deleteBtn.removeClass('hidden');
			this.$submitBtn.val(Craft.t('Save'));
		}

		this.displayErrors('name', (errors ? errors.name : null));
		this.displayErrors('handle', (errors ? errors.handle : null));

		if (!Garnish.isMobileBrowser())
		{
			setTimeout($.proxy(function() {
				this.$nameInput.focus()
			}, this), 100);
		}

		this.base();
	},

	displayErrors: function(attr, errors)
	{
		var $input = this['$'+attr+'Input'],
			$errorList = this['$'+attr+'ErrorList'];

		$errorList.children().remove();

		if (errors)
		{
			$input.addClass('error');
			$errorList.show();

			for (var i = 0; i < errors.length; i++)
			{
				$('<li/>').text(errors[i]).appendTo($errorList);
			}
		}
		else
		{
			$input.removeClass('error');
			$errorList.hide();
		}
	}

});


/**
 * Record type class
 */
var RecordType = Garnish.Base.extend({

	configurator: null,
	id: null,
	errors: null,

	inputNamePrefix: null,
	inputIdPrefix: null,

	$item: null,
	$nameLabel: null,
	$handleLabel: null,
	$nameHiddenInput: null,
	$handleHiddenInput: null,
	$settingsBtn: null,
	$fieldItemsContainer: null,
	$fieldSettingsContainer: null,

	fields: null,
	selectedField: null,
	fieldSort: null,
	totalNewFields: 0,
	fieldSettings: null,

	init: function(configurator, $item)
	{
		this.configurator = configurator;
		this.$item = $item;
		this.id = this.$item.data('id');
		this.errors = this.$item.data('errors');

		this.inputNamePrefix = this.configurator.inputNamePrefix+'[recordTypes]['+this.id+']';
		this.inputIdPrefix = this.configurator.inputIdPrefix+'-recordTypes-'+this.id;

		this.$nameLabel = this.$item.children('.name');
		this.$handleLabel = this.$item.children('.handle');
		this.$nameHiddenInput = this.$item.find('input[name$="[name]"]:first');
		this.$handleHiddenInput = this.$item.find('input[name$="[handle]"]:first');
		this.$settingsBtn = this.$item.find('.settings');

		// Find the field items container if it exists, otherwise create it
		this.$fieldItemsContainer = this.configurator.$fieldItemsContainer.children('[data-id="'+this.id+'"]:first');

		if (!this.$fieldItemsContainer.length)
		{
			this.$fieldItemsContainer = $('<div data-id="'+this.id+'"/>').insertBefore(this.configurator.$newFieldBtn);
		}

		// Find the field settings container if it exists, otherwise create it
		this.$fieldSettingsContainer = this.configurator.$fieldSettingItemsContainer.children('[data-id="'+this.id+'"]:first');

		if (!this.$fieldSettingsContainer.length)
		{
			this.$fieldSettingsContainer = $('<div data-id="'+this.id+'"/>').appendTo(this.configurator.$fieldSettingItemsContainer);
		}

		// Find the existing fields
		this.fields = {};

		var $fieldItems = this.$fieldItemsContainer.children();

		for (var i = 0; i < $fieldItems.length; i++)
		{
			var $fieldItem = $($fieldItems[i]),
				id = $fieldItem.data('id');

			this.fields[id] = new Field(this.configurator, this, $fieldItem);

			// Is this a new field?
			var newMatch = (typeof id == 'string' && id.match(/new(\d+)/));

			if (newMatch && newMatch[1] > this.totalNewFields)
			{
				this.totalNewFields = parseInt(newMatch[1]);
			}
		}

		this.addListener(this.$item, 'click', 'select');
		this.addListener(this.$settingsBtn, 'click', 'showSettings');

		this.fieldSort = new Garnish.DragSort($fieldItems, {
			caboose: '<div/>',
			handle: '.move',
			axis: 'y',
			onSortChange: $.proxy(function() {
				// Adjust the field setting containers to match the new sort order
				for (var i = 0; i < this.fieldSort.$items.length; i++)
				{
					var $item = $(this.fieldSort.$items[i]),
						id = $item.data('id'),
						field = this.fields[id];

					field.$fieldSettingsContainer.appendTo(this.$fieldSettingsContainer);
				}
			}, this)
		});
	},

	select: function()
	{
		if (this.configurator.selectedRecordType == this)
		{
			return;
		}

		if (this.configurator.selectedRecordType)
		{
			this.configurator.selectedRecordType.deselect();
		}

		this.configurator.$fieldsColumnContainer.removeClass('hidden');
		this.$fieldItemsContainer.removeClass('hidden');
		this.$item.addClass('sel');
		this.configurator.selectedRecordType = this;
	},

	deselect: function()
	{
		this.$item.removeClass('sel');
		this.configurator.$fieldsColumnContainer.addClass('hidden');
		this.$fieldItemsContainer.addClass('hidden');
		this.$fieldSettingsContainer.addClass('hidden');
		this.configurator.selectedRecordType = null;

		if (this.selectedField)
		{
			this.selectedField.deselect();
		}
	},

	showSettings: function()
	{
		var recordTypeSettingsModal = this.configurator.getRecordTypeSettingsModal();
		recordTypeSettingsModal.show(this.$nameHiddenInput.val(), this.$handleHiddenInput.val(), this.errors);
		recordTypeSettingsModal.onSubmit = $.proxy(this, 'applySettings');
		recordTypeSettingsModal.onDelete = $.proxy(this, 'delete');
	},

	applySettings: function(name, handle)
	{
		if (this.errors)
		{
			this.errors = null;
			this.$settingsBtn.removeClass('error');
		}

		this.$nameLabel.text(name);
		this.$handleLabel.text(handle);
		this.$nameHiddenInput.val(name);
		this.$handleHiddenInput.val(handle);
	},

	addField: function()
	{
		this.totalNewFields++;
		var id = 'new'+this.totalNewFields;

		var $item = $(
			'<div class="matrixconfigitem mci-field" data-id="'+id+'">' +
				'<div class="name">&nbsp;</div>' +
				'<div class="handle code">&nbsp;</div>' +
				'<div class="actions">' +
					'<a class="move icon" title="'+Craft.t('Reorder')+'"></a>' +
				'</div>' +
			'</div>'
		).appendTo(this.$fieldItemsContainer);

		this.fields[id] = new Field(this.configurator, this, $item);
		this.fields[id].select();

		this.fieldSort.addItems($item);
	},

	delete: function()
	{
		this.deselect();
		this.$item.remove();
		this.$fieldItemsContainer.remove();
		this.$fieldSettingsContainer.remove();

		this.configurator.recordTypes[this.id] = null;
		delete this.configurator.recordTypes[this.id];
	}

});


Field = Garnish.Base.extend({

	configurator: null,
	recordType: null,
	id: null,

	inputNamePrefix: null,
	inputIdPrefix: null,

	selectedFieldType: null,
	initializedFieldTypeSettings: null,

	$item: null,
	$nameLabel: null,
	$handleLabel: null,

	$fieldSettingsContainer: null,
	$nameInput: null,
	$handleInput: null,
	$requiredCheckbox: null,
	$typeSelect: null,
	$typeSettingsContainer: null,
	$deleteBtn: null,

	init: function(configurator, recordType, $item)
	{
		this.configurator = configurator;
		this.recordType = recordType;
		this.$item = $item;
		this.id = this.$item.data('id');

		this.inputNamePrefix = this.recordType.inputNamePrefix+'[fields]['+this.id+']';
		this.inputIdPrefix = this.recordType.inputIdPrefix+'-fields-'+this.id;

		this.initializedFieldTypeSettings = {};

		this.$nameLabel = this.$item.children('.name');
		this.$handleLabel = this.$item.children('.handle');

		// Find the field settings container if it exists, otherwise create it
		this.$fieldSettingsContainer = this.recordType.$fieldSettingsContainer.children('[data-id="'+this.id+'"]:first');

		var isNew = (!this.$fieldSettingsContainer.length);

		if (isNew)
		{
			this.$fieldSettingsContainer = $(this.getDefaultFieldSettingsHtml()).appendTo(this.recordType.$fieldSettingsContainer);
		}

		this.$nameInput = this.$fieldSettingsContainer.find('input[name$="[name]"]:first');
		this.$handleInput = this.$fieldSettingsContainer.find('input[name$="[handle]"]:first');
		this.$requiredCheckbox = this.$fieldSettingsContainer.find('input[type="checkbox"][name$="[required]"]:first');
		this.$typeSelect = this.$fieldSettingsContainer.find('select[name$="[type]"]:first');
		this.$typeSettingsContainer = this.$fieldSettingsContainer.children('.fieldtype-settings:first');
		this.$deleteBtn = this.$fieldSettingsContainer.children('a.delete:first');

		if (isNew)
		{
			this.setFieldType('PlainText');
		}
		else
		{
			this.selectedFieldType = this.$typeSelect.val();
			this.initializedFieldTypeSettings[this.selectedFieldType] = this.$typeSettingsContainer.children();
		}

		if (!this.$handleInput.val())
		{
			new Craft.HandleGenerator(this.$nameInput, this.$handleInput);
		}

		this.addListener(this.$item, 'click', 'select');
		this.addListener(this.$nameInput, 'textchange', 'updateNameLabel');
		this.addListener(this.$handleInput, 'textchange', 'updateHandleLabel');
		this.addListener(this.$requiredCheckbox, 'change', 'updateRequiredIcon');
		this.addListener(this.$typeSelect, 'change', 'onTypeSelectChange');
		this.addListener(this.$deleteBtn, 'click', 'confirmDelete');
	},

	select: function()
	{
		if (this.recordType.selectedField == this)
		{
			return;
		}

		if (this.recordType.selectedField)
		{
			this.recordType.selectedField.deselect();
		}

		this.configurator.$fieldSettingsColumnContainer.removeClass('hidden');
		this.recordType.$fieldSettingsContainer.removeClass('hidden');
		this.$fieldSettingsContainer.removeClass('hidden');
		this.$item.addClass('sel');
		this.recordType.selectedField = this;

		if (!Garnish.isMobileBrowser())
		{
			setTimeout($.proxy(function() {
				this.$nameInput.focus()
			}, this), 100);
		}
	},

	deselect: function()
	{
		this.$item.removeClass('sel');
		this.configurator.$fieldSettingsColumnContainer.addClass('hidden');
		this.recordType.$fieldSettingsContainer.addClass('hidden');
		this.$fieldSettingsContainer.addClass('hidden');
		this.recordType.selectedField = null;
	},

	updateNameLabel: function()
	{
		this.$nameLabel.html(Craft.escapeHtml(this.$nameInput.val())+'&nbsp;');
	},

	updateHandleLabel: function()
	{
		this.$handleLabel.html(Craft.escapeHtml(this.$handleInput.val())+'&nbsp;');
	},

	updateRequiredIcon: function()
	{
		if (this.$requiredCheckbox.prop('checked'))
		{
			this.$nameLabel.addClass('required');
		}
		else
		{
			this.$nameLabel.removeClass('required');
		}
	},

	onTypeSelectChange: function()
	{
		this.setFieldType(this.$typeSelect.val());
	},

	setFieldType: function(type)
	{
		if (this.selectedFieldType)
		{
			this.initializedFieldTypeSettings[this.selectedFieldType].detach();
		}

		this.selectedFieldType = type;
		this.$typeSelect.val(type);

		var firstTime = (typeof this.initializedFieldTypeSettings[type] == 'undefined');

		if (firstTime)
		{
			var info = this.configurator.getFieldTypeInfo(type),
				bodyHtml = this.getParsedFieldTypeHtml(info.settingsBodyHtml),
				footHtml = this.getParsedFieldTypeHtml(info.settingsFootHtml),
				$body = $('<div>'+bodyHtml+'</div>');

			this.initializedFieldTypeSettings[type] = $body;
		}
		else
		{
			var $body = this.initializedFieldTypeSettings[type];
		}

		$body.appendTo(this.$typeSettingsContainer);

		if (firstTime)
		{
			Craft.initUiElements($body);
			$('body').append(footHtml);
		}
	},

	getParsedFieldTypeHtml: function(html)
	{
		if (typeof html == 'string')
		{
			html = html.replace(/__RECORD_TYPE__/g, this.recordType.id);
			html = html.replace(/__FIELD__/g, this.id);
		}
		else
		{
			html = '';
		}

		return html;
	},

	getDefaultFieldSettingsHtml: function()
	{
		var html =
			'<div data-id="'+this.id+'">' +
				'<div class="field" id="'+this.inputIdPrefix+'-name-field">' +
					'<div class="heading">' +
						'<label class="required" for="'+this.inputIdPrefix+'-name">'+Craft.t('Name')+'</label>' +
					'</div>' +
					'<div class="input">' +
						'<input class="text fullwidth" type="text" id="'+this.inputIdPrefix+'-name" name="'+this.inputNamePrefix+'[name]" autofocus="" autocomplete="off"/>' +
					'</div>' +
				'</div>' +
				'<div class="field" id="'+this.inputIdPrefix+'-handle-field">' +
					'<div class="heading">' +
						'<label class="required" for="'+this.inputIdPrefix+'-handle">'+Craft.t('Handle')+'</label>' +
					'</div>' +
					'<div class="input">' +
						'<input class="text fullwidth code" type="text" id="'+this.inputIdPrefix+'-handle" name="'+this.inputNamePrefix+'[handle]" autofocus="" autocomplete="off"/>' +
					'</div>' +
				'</div>' +
				'<div class="field checkbox">' +
					'<label>' +
						'<input type="hidden" name="'+this.inputNamePrefix+'[required]" value=""/>' +
						'<input type="checkbox" value="1" name="'+this.inputNamePrefix+'[required]"/> ' +
						Craft.t('This field is required') +
					'</label>' +
				'</div>';

		if (Craft.hasPackage('Localize'))
		{
			html +=
				'<div class="field checkbox">' +
					'<label>' +
						'<input type="hidden" name="'+this.inputNamePrefix+'[translatable]" value=""/>' +
						'<input type="checkbox" value="1" name="'+this.inputNamePrefix+'[translatable]"/> ' +
						Craft.t('This field is translatable') +
					'</label>' +
				'</div>';
		}

		html +=
				'<hr/>' +

				'<div class="field" id="type-field">' +
					'<div class="heading">' +
						'<label for="type">'+Craft.t('Field Type')+'</label>' +
					'</div>' +
					'<div class="input">' +
						'<div class="select">' +
							'<select id="type" class="fieldtoggle" name="'+this.inputNamePrefix+'[type]">';

		for (var i = 0; i < this.configurator.fieldTypeInfo.length; i++)
		{
			var info = this.configurator.fieldTypeInfo[i],
				selected = (info.type == 'PlainText');

			html +=
								'<option value="'+info.type+'"'+(selected ? ' selected=""' : '')+'>'+info.name+'</option>';
		}

		html +=
							'</select>' +
						'</div>' +
					'</div>' +
				'</div>' +
				'<div class="fieldtype-settings"/>' +
				'<hr/>' +
				'<a class="error delete">'+Craft.t('Delete')+'</a>' +
			'</div>';

		return html;
	},

	confirmDelete: function()
	{
		if (confirm(Craft.t('Are you sure you want to delete this field?')))
		{
			this.delete();
		}
	},

	delete: function()
	{
		this.deselect();
		this.$item.remove();
		this.$fieldSettingsContainer.remove();

		this.recordType.fields[this.id] = null;
		delete this.recordType.fields[this.id];
	}

});


})(jQuery);
