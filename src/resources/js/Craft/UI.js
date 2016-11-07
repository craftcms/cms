Craft.ui =
{
	createTextInput: function(config)
	{
		var $input = $('<input/>', {
			'class': 'text',
			type: (config.type || 'text'),
			id: config.id,
			size: config.size,
			name: config.name,
			value: config.value,
			maxlength: config.maxlength,
			'data-show-chars-left': config.showCharsLeft,
			autofocus: this.getAutofocusValue(config.autofocus),
			autocomplete: (typeof config.autocomplete === typeof undefined || !config.autocomplete ? 'off' : null),
			disabled: this.getDisabledValue(config.disabled),
			readonly: config.readonly,
			title: config.title,
			placeholder: config.placeholder
		});

		if (config.class) $input.addClass(config.class);
		if (config.placeholder) $input.addClass('nicetext');
		if (config.type == 'password') $input.addClass('password');
		if (config.disabled) $input.addClass('disabled');
		if (!config.size) $input.addClass('fullwidth');

		if (config.showCharsLeft && config.maxlength)
		{
			$input.css('padding-'+(Craft.orientation == 'ltr' ? 'right' : 'left'), (7.2*config.maxlength.toString().length+14)+'px');
		}

		if (config.placeholder || config.showCharsLeft)
		{
			new Garnish.NiceText($input);
		}

		if (config.type == 'password')
		{
			return $('<div class="passwordwrapper"/>').append($input);
		}
		else
		{
			return $input;
		}
	},

	createTextField: function(config)
	{
		return this.createField(this.createTextInput(config), config);
	},

	createCheckbox: function(config)
	{
		var id = (config.id || 'checkbox'+Math.floor(Math.random() * 1000000000));

		var $input = $('<input/>', {
			type: 'checkbox',
			value: (typeof config.value !== typeof undefined ? config.value : '1'),
			id: id,
			'class': 'checkbox',
			name: config.name,
			checked: (config.checked ? 'checked' : null),
			autofocus: this.getAutofocusValue(config.autofocus),
			disabled: this.getDisabledValue(config.disabled),
			'data-target': config.toggle,
			'data-reverse-target': config.reverseToggle
		});

		if (config.class) $input.addClass(config.class);

		if (config.toggle || config.reverseToggle)
		{
			$input.addClass('fieldtoggle');
			new Craft.FieldToggle($input);
		}

		var $label = $('<label/>', {
			'for': id,
			text: config.label
		});

		// Should we include a hidden input first?
		if (config.name && (config.name.length < 3 || config.name.substr(-2) != '[]'))
		{
			return $([
				$('<input/>', {
					type: 'hidden',
					name: config.name,
					value: ''
				})[0],
				$input[0],
				$label[0]
			]);
		}
		else
		{
			return $([
				$input[0],
				$label[0]
			]);
		}
	},

	createCheckboxField: function(config)
	{
		var $field = $('<div class="field checkboxfield"/>', {
			id: (cofig.id ? config.id+'-field' : null)
		});

		if (config.first) $field.addClass('first');
		if (config.instructions) $field.addClass('has-instructions');

		this.createCheckbox(config).appendTo($field);

		if (config.instructions)
		{
			$('<div class="instructions"/>').text(config.instructions).appendTo($field);
		}

		return $field;
	},

	createCheckboxSelect: function(config)
	{
		var allValue = (config.allValue || '*'),
			allChecked = (!config.values || config.values == config.allValue);

		var $container = $('<div class="checkbox-select"/>');
		if (config.class) $container.addClass(config.class);

		// Create the "All" checkbox
		$('<div/>').appendTo($container).append(
			this.createCheckbox({
				id:        config.id,
				'class':   'all',
				label:     '<b>'+(config.allLabel || Craft.t('All'))+'</b>',
				name:      config.name,
				value:     allValue,
				checked:   allChecked,
				autofocus: config.autofocus
			})
		);

		// Create the actual options
		for (var i = 0; i < config.options.length; i++)
		{
			var option = config.options[i];

			if (option.value == allValue)
			{
				continue;
			}

			$('<div/>').appendTo($container).append(
				this.createCheckbox({
					label:    option.label,
					name:     (config.name ? config.name+'[]' : null),
					value:    option.value,
					checked:  (allChecked || Craft.inArray(option.value, config.values)),
					disabled: allChecked
				})
			);
		}

		new Garnish.CheckboxSelect($container);

		return $container;
	},

	createCheckboxSelectField: function(config)
	{
		return this.createField(this.createCheckboxSelect(config), config);
	},

	createField: function(input, config)
	{
		var label = (config.label && config.label != '__blank__' ? config.label : null),
			locale = (Craft.isLocalized && config.locale ? config.locale : null);

		var $field = $('<div/>', {
			'class': 'field',
			'id': config.fieldId || (config.id ? config.id+'-field' : null)
		});

		if (config.first) $field.addClass('first');

		if (label || config.instructions)
		{
			var $heading = $('<div class="heading"/>').appendTo($field);

			if (label)
			{
				var $label = $('<label/>', {
					'id': config.labelId || (config.id ? config.id+'-label' : null),
					'class': (config.required ? 'required' : null),
					'for': config.id,
					text: label
				}).appendTo($heading);

				if (locale)
				{
					$('<span class="locale"/>').text(locale).appendTo($label);
				}
			}

			if (config.instructions)
			{
				$('<div class="instructions"/>').text(config.instructions).appendTo($heading);
			}
		}

		$('<div class="input"/>').append(input).appendTo($field);

		if (config.warning)
		{
			$('<p class="warning"/>').text(config.warning).appendTo($field);
		}

		if (config.errors)
		{
			this.addErrorsToField($field, config.errors);
		}

		return $field;
	},

	createErrorList: function(errors)
	{
		var $list = $('<ul class="errors"/>');

		if (errors)
		{
			this.addErrorsToList($list, errors);
		}

		return $list;
	},

	addErrorsToList: function($list, errors)
	{
		for (var i = 0; i < errors.length; i++)
		{
			$('<li/>').text(errors[i]).appendTo($list);
		}
	},

	addErrorsToField: function($field, errors)
	{
		if (!errors)
		{
			return;
		}

		$field.addClass('has-errors');
		$field.children('.input').addClass('errors');

		var $errors = $field.children('ul.errors');

		if (!$errors.length)
		{
			$errors = this.createErrorList().appendTo($field);
		}

		this.addErrorsToList($errors, errors);
	},

	clearErrorsFromField: function($field)
	{
		$field.removeClass('has-errors');
		$field.children('.input').removeClass('errors');
		$field.children('ul.errors').remove();
	},

	getAutofocusValue: function(autofocus)
	{
		return (autofocus && !Garnish.isMobileBrowser(true) ? 'autofocus' : null);
	},

	getDisabledValue: function(disabled)
	{
		return (disabled ? 'disabled' : null);
	}
};
