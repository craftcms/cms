/** global: Craft */
/** global: Garnish */
Craft.ui =
    {
        createTextInput: function(config) {
            var $input = $('<input/>', {
                attr: {
                    'class': 'text',
                    type: (config.type || 'text'),
                    inputmode: config.inputmode,
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
                    placeholder: config.placeholder,
                    step: config.step,
                    min: config.min,
                    max: config.max
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
            if (!config.id) {
                config.id = 'text' + Math.floor(Math.random() * 1000000000);
            }
            return this.createField(this.createTextInput(config), config);
        },

        createCopyTextInput: function(config) {
            let id = config.id || 'copytext' + Math.floor(Math.random() * 1000000000);
            let buttonId = config.buttonId || `${id}-btn`;

            let $container = $('<div/>', {
                'class': 'copytext',
            });

            let $input = this.createTextInput($.extend({}, config, {
                readonly: true,
            })).appendTo($container);

            let $btn = $('<button/>', {
                type: 'button',
                id: buttonId,
                'class': 'btn',
                'data-icon': 'clipboard',
                title: Craft.t('app', 'Copy to clipboard'),
                'aria-label': Craft.t('app', 'Copy to clipboard'),
            }).appendTo($container);

            $btn.on('click', () => {
                $input[0].select();
                document.execCommand('copy');
                Craft.cp.displayNotice(Craft.t('app', 'Copied to clipboard.'));
                $container.trigger('copy');
                $input[0].setSelectionRange(0, 0);
            });

            return $container;
        },

        createCopyTextField: function(config) {
            if (!config.id) {
                config.id = 'copytext' + Math.floor(Math.random() * 1000000000);
            }
            return this.createField(this.createCopyTextInput(config), config);
        },

        createCopyTextPrompt: function(config) {
            let $container = $('<div/>', {
                'class': 'modal fitted',
            });
            let $body = $('<div/>', {
                'class': 'body',
            }).appendTo($container);
            this.createCopyTextField($.extend({
                size: Math.max(Math.min(config.value.length, 50), 25),
            }, config)).appendTo($body);
            let modal = new Garnish.Modal($container, {
                closeOtherModals: false,
            });
            $container.on('copy', () => {
                modal.hide();
            })
            return $container;
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
            if (!config.id) {
                config.id = 'textarea' + Math.floor(Math.random() * 1000000000);
            }
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

            // Normalize the options into an array
            if ($.isPlainObject(config.options)) {
                let options = [];
                for (var key in config.options) {
                    if (!config.options.hasOwnProperty(key)) {
                        continue;
                    }
                    let option = config.options[key];
                    if ($.isPlainObject(option)) {
                        if (typeof option.optgroup !== 'undefined') {
                            options.push(option);
                        } else {
                            options.push({
                                label: option.label,
                                value: typeof option.value !== 'undefined' ? option.value : key,
                                disabled: typeof option.disabled !== 'undefined' ? option.disabled : false,
                            });
                        }
                    } else {
                        options.push({
                            label: option,
                            value: key,
                        })
                    }
                }
                config.options = options;
            }

            var $optgroup = null;

            for (let i = 0; i < config.options.length; i++) {
                let option = config.options[i];

                // Starting a new <optgroup>?
                if (typeof option.optgroup !== 'undefined') {
                    $optgroup = $('<optgroup/>', {
                        'label': option.label
                    }).appendTo($select);
                } else {
                    $('<option/>', {
                        'value': option.value,
                        'selected': (option.value == config.value),
                        'disabled': typeof option.disabled !== 'undefined' ? option.disabled : false,
                        'html':  option.label
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
            if (!config.id) {
                config.id = 'select' + Math.floor(Math.random() * 1000000000);
            }
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
                html: config.label,
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
            if (!config.id) {
                config.id = 'checkbox' + Math.floor(Math.random() * 1000000000);
            }

            var $field = $('<div class="field checkboxfield"/>', {
                id: `${config.id}-field`,
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
            var $container = $('<fieldset class="checkbox-select"/>');

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
            if (!config.id) {
                config.id = 'checkboxselect' + Math.floor(Math.random() * 1000000000);
            }
            return this.createField(this.createCheckboxSelect(config), config);
        },

        createLightswitch: function(config) {
            var value = config.value || '1';
            var indeterminateValue = config.indeterminateValue || '-';

            var $container = $('<button/>', {
                'type': 'button',
                'class': 'lightswitch',
                'data-value': value,
                'data-indeterminate-value': indeterminateValue,
                id: config.id,
                role: 'checkbox',
                'aria-checked': config.on ? 'true' : (config.indeterminate ? 'mixed' : 'false'),
                'aria-labelledby': config.labelId,
                'data-target': config.toggle,
                'data-reverse-target': config.reverseToggle
            });

            if (config.on) {
                $container.addClass('on');
            } else if (config.indeterminate) {
                $container.addClass('indeterminate');
            }

            if (config.small) {
                $container.addClass('small');
            }

            if (config.disabled) {
                $container.addClass('disabled');
            }

            $(
                '<div class="lightswitch-container">' +
                '<div class="handle"></div>' +
                '</div>'
            ).appendTo($container);

            if (config.name) {
                $('<input/>', {
                    type: 'hidden',
                    name: config.name,
                    value: config.on ? value : (config.indeterminate ? indeterminateValue : ''),
                    disabled: config.disabled
                }).appendTo($container);
            }

            if (config.toggle || config.reverseToggle) {
                $container.addClass('fieldtoggle');
                new Craft.FieldToggle($container);
            }

            new Craft.LightSwitch($container, {
                onChange: config.onChange || $.noop,
            });

            return $container;
        },

        createLightswitchField: function(config) {
            if (!config.id) {
                config.id = 'lightswitch' + Math.floor(Math.random() * 1000000000);
            }
            return this.createField(this.createLightswitch(config), config)
                .addClass('lightswitch-field');
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
            if (!config.id) {
                config.id = 'color' + Math.floor(Math.random() * 1000000000);
            }
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
            if (!config.id) {
                config.id = 'date' + Math.floor(Math.random() * 1000000000);
            }
            return this.createField(this.createDateInput(config), config);
        },

        createDateRangePicker: function(config) {
            var now = new Date();
            var today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
            config = $.extend({
                class: '',
                options: [
                    'today',
                    'thisWeek',
                    'thisMonth',
                    'thisYear',
                    'past7Days',
                    'past30Days',
                    'past90Days',
                    'pastYear',
                ],
                onChange: $.noop,
                selected: null,
                startDate:null,
                endDate: null,
            }, config);

            var $menu = $('<div/>', {'class': 'menu'});
            var $ul = $('<ul/>', {'class': 'padded'}).appendTo($menu);
            var $allOption = $('<a/>')
                .addClass('sel')
                .text(Craft.t('app', 'All'))
                .data('handle', 'all');

            $('<li/>')
                .append($allOption)
                .appendTo($ul);

            var option;
            var selectedOption;
            for (var i = 0; i < config.options.length; i++) {
                var handle = config.options[i];
                switch (handle) {
                    case 'today':
                        option = {
                            label: Craft.t('app', 'Today'),
                            startDate: today,
                            endDate: today,
                        };
                        break;
                    case 'thisWeek':
                        var firstDayOffset = now.getDay() - Craft.datepickerOptions.firstDay;
                        if (firstDayOffset < 0) {
                            firstDayOffset += 7;
                        }
                        option = {
                            label: Craft.t('app', 'This week'),
                            startDate: new Date(now.getFullYear(), now.getMonth(), now.getDate() - firstDayOffset),
                            endDate: today,
                        };
                        break;
                    case 'thisMonth':
                        option = {
                            label: Craft.t('app', 'This month'),
                            startDate: new Date(now.getFullYear(), now.getMonth()),
                            endDate: today,
                        };
                        break;
                    case 'thisYear':
                        option = {
                            label: Craft.t('app', 'This year'),
                            startDate: new Date(now.getFullYear(), 0),
                            endDate: today,
                        };
                        break;
                    case 'past7Days':
                        option = {
                            label: Craft.t('app', 'Past {num} days', {num: 7}),
                            startDate: new Date(now.getFullYear(), now.getMonth(), now.getDate() - 7),
                            endDate: today,
                        };
                        break;
                    case 'past30Days':
                        option = {
                            label: Craft.t('app', 'Past {num} days', {num: 30}),
                            startDate: new Date(now.getFullYear(), now.getMonth(), now.getDate() - 30),
                            endDate: today,
                        };
                        break;
                    case 'past90Days':
                        option = {
                            label: Craft.t('app', 'Past {num} days', {num: 90}),
                            startDate: new Date(now.getFullYear(), now.getMonth(), now.getDate() - 90),
                            endDate: today,
                        };
                        break;
                    case 'pastYear':
                        option = {
                            label: Craft.t('app', 'Past year'),
                            startDate: new Date(now.getFullYear(), now.getMonth(), now.getDate() - 365),
                            endDate: today,
                        };
                        break;
                }

                var $li = $('<li/>');
                var $a = $('<a/>', {text: option.label})
                    .data('handle', handle)
                    .data('startDate', option.startDate)
                    .data('endDate', option.endDate)
                    .data('startTime', option.startDate ? option.startDate.getTime() : null)
                    .data('endTime', option.endDate ? option.endDate.getTime() : null);

                if (config.selected && handle == config.selected) {
                    selectedOption = $a[0];
                }

                $li.append($a);
                $li.appendTo($ul);
            }

            $('<hr/>').appendTo($menu);

            var $flex = $('<div/>', {'class': 'flex flex-nowrap padded'}).appendTo($menu);
            var $startDate = this.createDateField({label: Craft.t('app', 'From')}).appendTo($flex).find('input');
            var $endDate = this.createDateField({label: Craft.t('app', 'To')}).appendTo($flex).find('input');

            // prevent ESC keypresses in the date inputs from closing the menu
            var $dateInputs = $startDate.add($endDate);
            $dateInputs.on('keyup', function(ev) {
                if (ev.keyCode === Garnish.ESC_KEY && $(this).data('datepicker').dpDiv.is(':visible')) {
                    ev.stopPropagation();
                }
            });

            // prevent clicks in the datepicker divs from closing the menu
            $startDate.data('datepicker').dpDiv.on('mousedown', function(ev) {
                ev.stopPropagation();
            });
            $endDate.data('datepicker').dpDiv.on('mousedown', function(ev) {
                ev.stopPropagation();
            });

            var menu = new Garnish.Menu($menu, {
                onOptionSelect: function(option) {
                    var $option = $(option);
                    $btn.text($option.text());
                    menu.setPositionRelativeToAnchor();
                    $menu.find('.sel').removeClass('sel');
                    $option.addClass('sel');

                    // Update the start/end dates
                    $startDate.datepicker('setDate', $option.data('startDate'));
                    $endDate.datepicker('setDate', $option.data('endDate'));

                    config.onChange($option.data('startDate') || null, $option.data('endDate') || null, $option.data('handle'));
                }
            });

            $dateInputs.on('change', function() {
                // Do the start & end dates match one of our options?
                let startDate = $startDate.datepicker('getDate');
                let endDate = $endDate.datepicker('getDate');
                let startTime = startDate ? startDate.getTime() : null;
                let endTime = endDate ? endDate.getTime() : null;

                let $options = $ul.find('a');
                let $option;
                let foundOption = false;

                for (let i = 0; i < $options.length; i++) {
                    $option = $options.eq(i);
                    if (
                        startTime === ($option.data('startTime') || null) &&
                        endTime === ($option.data('endTime') || null)
                    ) {
                        menu.selectOption($option[0]);
                        foundOption = true;
                        config.onChange(null, null, $option.data('handle'));
                        break;
                    }
                }

                if (!foundOption) {
                    $menu.find('.sel').removeClass('sel');
                    $flex.addClass('sel');

                    if (!startTime && !endTime) {
                        $btn.text(Craft.t('app', 'All'));
                    } else if (startTime && endTime) {
                        $btn.text($startDate.val() + ' - ' + $endDate.val());
                    } else if (startTime) {
                        $btn.text(Craft.t('app', 'From {date}', {date: $startDate.val()}));
                    } else {
                        $btn.text(Craft.t('app', 'To {date}', {date: $endDate.val()}));
                    }
                    menu.setPositionRelativeToAnchor();

                    config.onChange(startDate, endDate, 'custom');
                }
            });

            menu.on('hide', function() {
                $startDate.datepicker('hide');
                $endDate.datepicker('hide');
            });

            let btnClasses = 'btn menubtn';
            if (config.class) {
                btnClasses = btnClasses + ' ' + config.class;
            }

            let $btn = $('<button/>', {
                type: 'button',
                class: btnClasses,
                'data-icon': 'date',
                text: Craft.t('app', 'All'),
            });

            new Garnish.MenuBtn($btn, menu);

            if (selectedOption) {
                menu.selectOption(selectedOption);
            }

            if (config.startDate) {
                $startDate.datepicker('setDate', config.startDate);
            }

            if (config.endDate) {
                $endDate.datepicker('setDate', config.endDate);
            }

            if (config.startDate || config.endDate) {
                $dateInputs.trigger('change');
            }

            return $btn;
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
            if (!config.id) {
                config.id = 'time' + Math.floor(Math.random() * 1000000000);
            }
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

            if (label) {
                var $heading = $('<div class="heading"/>').appendTo($field);

                var $label = $('<label/>', {
                    'id': config.labelId || (config.id ? `${config.id}-label` : null),
                    'class': (config.required ? 'required' : null),
                    'for': config.id,
                    text: label
                }).appendTo($heading);
            }

            if (config.instructions) {
                $('<div class="instructions"/>').text(config.instructions).appendTo($field);
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
        },
    };
