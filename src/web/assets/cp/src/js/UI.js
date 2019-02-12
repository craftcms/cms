/** global: Craft */
/** global: Garnish */
Craft.ui =
    {
        createTextInput: function(config) {
            var $input = $('<input/>', {
                attr: {
                    'class': 'text',
                    type: (config.type || 'text'),
                    id: config.id,
                    size: config.size,
                    name: config.name,
                    value: config.value,
                    maxlength: config.maxlength,
                    autofocus: this.getAutofocusValue(config.autofocus),
                    autocomplete: (typeof config.autocomplete === 'undefined' || !config.autocomplete ? 'off' : null),
                    disabled: this.getDisabledValue(config.disabled),
                    readonly: config.readonly,
                    title: config.title,
                    placeholder: config.placeholder
                }
            });

            if (config.class) {
                $input.addClass(config.class);
            }
            if (config.placeholder) {
                $input.addClass('nicetext');
            }
            if (config.type === 'password') {
                $input.addClass('password');
            }
            if (config.disabled) {
                $input.addClass('disabled');
            }
            if (!config.size) {
                $input.addClass('fullwidth');
            }

            if (config.showCharsLeft && config.maxlength) {
                $input
                    .attr('data-show-chars-left')
                    .css('padding-' + (Craft.orientation === 'ltr' ? 'right' : 'left'), (7.2 * config.maxlength.toString().length + 14) + 'px');
            }

            if (config.placeholder || config.showCharsLeft) {
                new Garnish.NiceText($input);
            }

            if (config.type === 'password') {
                return $('<div class="passwordwrapper"/>').append($input);
            }
            else {
                return $input;
            }
        },

        createTextField: function(config) {
            return this.createField(this.createTextInput(config), config);
        },

        createTextarea: function(config) {
            var $textarea = $('<textarea/>', {
                'class': 'text',
                'rows': config.rows || 2,
                'cols': config.cols || 50,
                'id': config.id,
                'name': config.name,
                'maxlength': config.maxlength,
                'autofocus': config.autofocus && !Garnish.isMobileBrowser(true),
                'disabled': !!config.disabled,
                'placeholder': config.placeholder,
                'html': config.value
            });

            if (config.showCharsLeft) {
                $textarea.attr('data-show-chars-left', '');
            }

            if (config.class) {
                $textarea.addClass(config.class);
            }

            if (!config.size) {
                $textarea.addClass('fullwidth');
            }

            return $textarea;
        },

        createTextareaField: function(config) {
            return this.createField(this.createTextarea(config), config);
        },

        createSelect: function(config) {
            var $container = $('<div/>', {
                'class': 'select'
            });

            if (config.class) {
                $container.addClass(config.class);
            }

            var $select = $('<select/>', {
                'id': config.id,
                'name': config.name,
                'autofocus': config.autofocus && Garnish.isMobileBrowser(true),
                'disabled': config.disabled,
                'data-target-prefix': config.targetPrefix
            }).appendTo($container);

            var $optgroup = null;

            for (var key in config.options) {
                if (!config.options.hasOwnProperty(key)) {
                    continue;
                }

                var option = config.options[key];

                // Starting a new <optgroup>?
                if (typeof option.optgroup !== 'undefined') {
                    $optgroup = $('<optgroup/>', {
                        'label': option.label
                    }).appendTo($select);
                } else {
                    var optionLabel = (typeof option.label !== 'undefined' ? option.label : option),
                        optionValue = (typeof option.value !== 'undefined' ? option.value : key),
                        optionDisabled = (typeof option.disabled !== 'undefined' ? option.disabled : false);

                    $('<option/>', {
                        'value': optionValue,
                        'selected': (optionValue == config.value),
                        'disabled': optionDisabled,
                        'html': optionLabel
                    }).appendTo($optgroup || $select);
                }
            }

            if (config.toggle) {
                $select.addClass('fieldtoggle');
                new Craft.FieldToggle($select);
            }

            return $container;
        },

        createSelectField: function(config) {
            return this.createField(this.createSelect(config), config);
        },

        createCheckbox: function(config) {
            var id = (config.id || 'checkbox' + Math.floor(Math.random() * 1000000000));

            var $input = $('<input/>', {
                type: 'checkbox',
                value: (typeof config.value !== 'undefined' ? config.value : '1'),
                id: id,
                'class': 'checkbox',
                name: config.name,
                checked: (config.checked ? 'checked' : null),
                autofocus: this.getAutofocusValue(config.autofocus),
                disabled: this.getDisabledValue(config.disabled),
                'data-target': config.toggle,
                'data-reverse-target': config.reverseToggle
            });

            if (config.class) {
                $input.addClass(config.class);
            }

            if (config.toggle || config.reverseToggle) {
                $input.addClass('fieldtoggle');
                new Craft.FieldToggle($input);
            }

            var $label = $('<label/>', {
                'for': id,
                text: config.label
            });

            // Should we include a hidden input first?
            if (config.name && (config.name.length < 3 || config.name.substr(-2) !== '[]')) {
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
            else {
                return $([
                    $input[0],
                    $label[0]
                ]);
            }
        },

        createCheckboxField: function(config) {
            var $field = $('<div class="field checkboxfield"/>', {
                id: (config.id ? config.id + '-field' : null)
            });

            if (config.first) {
                $field.addClass('first');
            }
            if (config.instructions) {
                $field.addClass('has-instructions');
            }

            this.createCheckbox(config).appendTo($field);

            if (config.instructions) {
                $('<div class="instructions"/>').text(config.instructions).appendTo($field);
            }

            return $field;
        },

        createCheckboxSelect: function(config) {
            var $container = $('<div class="checkbox-select"/>');

            if (config.class) {
                $container.addClass(config.class);
            }

            var allValue, allChecked;

            if (config.showAllOption) {
                allValue = (config.allValue || '*');
                allChecked = (config.values == allValue);

                // Create the "All" checkbox
                $('<div/>').appendTo($container).append(
                    this.createCheckbox({
                        id: config.id,
                        'class': 'all',
                        label: '<b>' + (config.allLabel || Craft.t('app', 'All')) + '</b>',
                        name: config.name,
                        value: allValue,
                        checked: allChecked,
                        autofocus: config.autofocus
                    })
                );
            } else {
                allChecked = false;
            }

            // Create the actual options
            for (var i = 0; i < config.options.length; i++) {
                var option = config.options[i];

                if (option.value == allValue) {
                    continue;
                }

                $('<div/>').appendTo($container).append(
                    this.createCheckbox({
                        label: option.label,
                        name: (config.name ? config.name + '[]' : null),
                        value: option.value,
                        checked: (allChecked || Craft.inArray(option.value, config.values)),
                        disabled: allChecked
                    })
                );
            }

            new Garnish.CheckboxSelect($container);

            return $container;
        },

        createCheckboxSelectField: function(config) {
            return this.createField(this.createCheckboxSelect(config), config);
        },

        createLightswitch: function(config) {
            var value = config.value || '1';

            var $container = $('<div/>', {
                'class': 'lightswitch',
                tabindex: '0',
                'data-value': value,
                id: config.id,
                'aria-labelledby': config.labelId,
                'data-target': config.toggle,
                'data-reverse-target': config.reverseToggle
            });

            if (config.on) {
                $container.addClass('on');
            }

            if (config.small) {
                $container.addClass('small');
            }

            if (config.disabled) {
                $container.addClass('disabled');
            }

            $(
                '<div class="lightswitch-container">' +
                '<div class="label on"></div>' +
                '<div class="handle"></div>' +
                '<div class="label off"></div>' +
                '</div>'
            ).appendTo($container);

            if (config.name) {
                $('<input/>', {
                    type: 'hidden',
                    name: config.name,
                    value: (config.on ? value : ''),
                    disabled: config.disabled
                }).appendTo($container);
            }

            if (config.toggle || config.reverseToggle) {
                $container.addClass('fieldtoggle');
                new Craft.FieldToggle($container);
            }

            return $container.lightswitch();
        },

        createLightswitchField: function(config) {
            return this.createField(this.createLightswitch(config), config);
        },

        createColorInput: function(config) {
            var id = (config.id || 'color' + Math.floor(Math.random() * 1000000000));
            var containerId = config.containerId || id + '-container';
            var name = config.name || null;
            var value = config.value || null;
            var small = config.small || false;
            var autofocus = config.autofocus && Garnish.isMobileBrowser(true);
            var disabled = config.disabled || false;

            var $container = $('<div/>', {
                id: containerId,
                'class': 'flex color-container'
            });

            var $colorPreviewContainer = $('<div/>', {
                'class': 'color static' + (small ? ' small' : '')
            }).appendTo($container);

            var $colorPreview = $('<div/>', {
                'class': 'color-preview',
                style: config.value ? {backgroundColor: config.value} : null
            }).appendTo($colorPreviewContainer);

            var $input = this.createTextInput({
                id: id,
                name: name,
                value: value,
                size: 10,
                'class': 'color-input',
                autofocus: autofocus,
                disabled: disabled
            }).appendTo($container);

            new Craft.ColorInput($container);
            return $container;
        },

        createColorField: function(config) {
            return this.createField(this.createColorInput(config), config);
        },

        createDateInput: function(config) {
            var id = (config.id || 'date' + Math.floor(Math.random() * 1000000000))+'-date';
            var name = config.name || null;
            var inputName = name ? name+'[date]' : null;
            var value = config.value && typeof config.value.getMonth === 'function' ? config.value : null;
            var formattedValue = value ? Craft.formatDate(value) : null;
            var autofocus = config.autofocus && Garnish.isMobileBrowser(true);
            var disabled = config.disabled || false;

            var $container = $('<div/>', {
                'class': 'datewrapper'
            });

            var $input = this.createTextInput({
                id: id,
                name: inputName,
                value: formattedValue,
                placeholder: ' ',
                autocomplete: false,
                autofocus: autofocus,
                disabled: disabled
            }).appendTo($container);

            $('<div data-icon="date"></div>').appendTo($container);

            if (name) {
                $('<input/>', {
                    type: 'hidden',
                    name: name+'[timezone]',
                    val: Craft.timezone
                }).appendTo($container);
            }

            $input.datepicker($.extend({
                defaultDate: value || new Date()
            }, Craft.datepickerOptions));

            return $container;
        },

        createDateField: function(config) {
            return this.createField(this.createDateInput(config), config);
        },

        createTimeInput: function(config) {
            var id = (config.id || 'time' + Math.floor(Math.random() * 1000000000))+'-time';
            var name = config.name || null;
            var inputName = name ? name+'[time]' : null;
            var value = config.value && typeof config.value.getMonth === 'function' ? config.value : null;
            var autofocus = config.autofocus && Garnish.isMobileBrowser(true);
            var disabled = config.disabled || false;

            var $container = $('<div/>', {
                'class': 'timewrapper'
            });

            var $input = this.createTextInput({
                id: id,
                name: inputName,
                placeholder: ' ',
                autocomplete: false,
                autofocus: autofocus,
                disabled: disabled
            }).appendTo($container);

            $('<div data-icon="time"></div>').appendTo($container);

            if (name) {
                $('<input/>', {
                    type: 'hidden',
                    name: name+'[timezone]',
                    val: Craft.timezone
                }).appendTo($container);
            }

            $input.timepicker(Craft.timepickerOptions);
            if (value) {
                $input.timepicker('setTime', value.getHours()*3600 + value.getMinutes()*60 + value.getSeconds());
            }

            return $container;
        },

        createTimeField: function(config) {
            return this.createField(this.createTimeInput(config), config);
        },

        createField: function(input, config) {
            var label = (config.label && config.label !== '__blank__' ? config.label : null),
                siteId = (Craft.isMultiSite && config.siteId ? config.siteId : null);

            var $field = $('<div/>', {
                'class': 'field',
                'id': config.fieldId || (config.id ? config.id + '-field' : null)
            });

            if (config.first) {
                $field.addClass('first');
            }

            if (label || config.instructions) {
                var $heading = $('<div class="heading"/>').appendTo($field);

                if (label) {
                    var $label = $('<label/>', {
                        'id': config.labelId || (config.id ? config.id + '-label' : null),
                        'class': (config.required ? 'required' : null),
                        'for': config.id,
                        text: label
                    }).appendTo($heading);

                    if (siteId) {
                        for (var i = 0; i < Craft.sites.length; i++) {
                            if (Craft.sites[i].id == siteId) {
                                $('<span class="site"/>').text(Craft.sites[i].name).appendTo($label);
                                break;
                            }
                        }
                    }
                }

                if (config.instructions) {
                    $('<div class="instructions"/>').text(config.instructions).appendTo($heading);
                }
            }

            $('<div class="input"/>').append(input).appendTo($field);

            if (config.warning) {
                $('<p class="warning"/>').text(config.warning).appendTo($field);
            }

            if (config.errors) {
                this.addErrorsToField($field, config.errors);
            }

            return $field;
        },

        createErrorList: function(errors) {
            var $list = $('<ul class="errors"/>');

            if (errors) {
                this.addErrorsToList($list, errors);
            }

            return $list;
        },

        addErrorsToList: function($list, errors) {
            for (var i = 0; i < errors.length; i++) {
                $('<li/>').text(errors[i]).appendTo($list);
            }
        },

        addErrorsToField: function($field, errors) {
            if (!errors) {
                return;
            }

            $field.addClass('has-errors');
            $field.children('.input').addClass('errors');

            var $errors = $field.children('ul.errors');

            if (!$errors.length) {
                $errors = this.createErrorList().appendTo($field);
            }

            this.addErrorsToList($errors, errors);
        },

        clearErrorsFromField: function($field) {
            $field.removeClass('has-errors');
            $field.children('.input').removeClass('errors');
            $field.children('ul.errors').remove();
        },

        getAutofocusValue: function(autofocus) {
            return (autofocus && !Garnish.isMobileBrowser(true) ? 'autofocus' : null);
        },

        getDisabledValue: function(disabled) {
            return (disabled ? 'disabled' : null);
        }
    };
