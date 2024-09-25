/** global: Craft */
/** global: Garnish */
Craft.ui = {
  createButton: function (config) {
    const $btn = $('<button/>', {
      type: config.type || 'button',
      class: 'btn',
    });
    if (config.id) {
      $btn.attr('id', config.id);
    }
    if (config.class) {
      $btn.addClass(config.class);
    }
    if (config.ariaLabel) {
      $btn.attr('aria-label', config.ariaLabel);
    }
    if (config.role) {
      $btn.attr('role', config.role);
    }
    if (config.html) {
      $btn.html(config.html);
    } else if (config.label) {
      $btn.append($('<div class="label"/>').text(config.label));
    } else {
      $btn.addClass('btn-empty');
    }
    if (config.toggle) {
      $btn.attr('aria-expanded', 'false');
    }
    if (config.controls) {
      $btn.attr('aria-controls', config.controls);
    }
    if (config.data) {
      Object.entries(config.data).forEach((item) => {
        $btn.attr('data-' + item[0], item[1]);
      });
    }
    if (config.spinner) {
      $btn.append($('<div class="spinner spinner-absolute"/>'));
    }
    return $btn;
  },

  createSubmitButton: function (config) {
    const $btn = this.createButton(
      Object.assign({}, config, {
        type: 'submit',
        label: config.label || Craft.t('app', 'Submit'),
      })
    );
    $btn.addClass('submit');
    return $btn;
  },

  createTextInput: function (config) {
    config = $.extend(
      {
        autocomplete: false,
      },
      config
    );
    var $input = $('<input/>', {
      attr: {
        class: 'text',
        type: config.type || 'text',
        inputmode: config.inputmode,
        id: config.id,
        size: config.size,
        name: config.name,
        value: config.value,
        maxlength: config.maxlength,
        autofocus: this.getAutofocusValue(config.autofocus),
        autocomplete:
          typeof config.autocomplete === 'boolean'
            ? config.autocomplete
              ? 'on'
              : 'off'
            : config.autocomplete,
        disabled: this.getDisabledValue(config.disabled),
        'aria-describedby': this.getDescribedByValue(config),
        readonly: config.readonly,
        title: config.title,
        placeholder: config.placeholder,
        step: config.step,
        min: config.min,
        max: config.max,
      },
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
    if (config.describedBy) {
      $input.attr('aria-describedby', config.describedBy);
    }
    if (config.inputAttributes) {
      this.addAttributes($input, config.inputAttributes);
    }

    if (config.showCharsLeft && config.maxlength) {
      $input
        .attr('data-show-chars-left')
        .css(
          'padding-' + (Craft.orientation === 'ltr' ? 'right' : 'left'),
          7.2 * config.maxlength.toString().length + 14 + 'px'
        );
    }

    if (config.placeholder || config.showCharsLeft) {
      new Garnish.NiceText($input);
    }

    if (config.type === 'password') {
      return $('<div class="passwordwrapper"/>').append($input);
    } else {
      return $input;
    }
  },

  createTextField: function (config) {
    if (!config.id) {
      config.id = 'text' + Math.floor(Math.random() * 1000000000);
    }
    return this.createField(this.createTextInput(config), config);
  },

  createPasswordInput(config) {
    return this.createTextInput(
      Object.assign({}, config, {
        type: 'password',
      })
    );
  },

  createPasswordField(config) {
    return this.createTextField(
      Object.assign({}, config, {
        type: 'password',
      })
    );
  },

  createCopyTextInput: function (config) {
    let id = config.id || 'copytext' + Math.floor(Math.random() * 1000000000);
    let buttonId = config.buttonId || `${id}-btn`;

    let $container = $('<div/>', {
      class: 'copytext',
    });

    let $input = this.createTextInput(
      $.extend({}, config, {
        readonly: true,
      })
    ).appendTo($container);

    let $btn = $('<button/>', {
      type: 'button',
      id: buttonId,
      class: 'btn',
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

  createCopyTextBtn: function (config) {
    let id = config.id || 'copytext' + Math.floor(Math.random() * 1000000000);
    let value = config.value;

    const $wrapper = $('<div/>', {
      class: 'copytextbtn-wrapper',
    });

    let $btn = $('<div/>', {
      id,
      class: 'copytextbtn',
      role: 'button',
      title: Craft.t('app', 'Copy to clipboard'),
      tabindex: '0',
    }).appendTo($wrapper);

    if (config.class) {
      $btn.addClass(config.class);
    }

    let $input = $('<input/>', {
      value,
      readonly: true,
      size: value.length,
      tabindex: '-1',
      'aria-hidden': 'true',
      class: 'visually-hidden',
    }).insertBefore($btn);

    const $value = $('<span/>', {
      text: value,
      class: 'copytextbtn__value',
    }).appendTo($btn);

    $('<span/>', {
      class: 'visually-hidden',
      text: Craft.t('app', 'Copy to clipboard'),
    }).appendTo($btn);

    let $icon = $('<span/>', {
      class: 'copytextbtn__icon',
      'data-icon': 'clipboard',
      'aria-hidden': 'true',
    }).appendTo($btn);

    const copyValue = function () {
      $input[0].select();
      document.execCommand('copy');
      Craft.cp.displayNotice(Craft.t('app', 'Copied to clipboard.'));
      $btn.trigger('copy');
      $input[0].setSelectionRange(0, 0);
      $btn.focus();
    };

    $btn.on('activate', () => {
      copyValue();
    });

    $btn.on('keydown', (ev) => {
      if (ev.keyCode === Garnish.SPACE_KEY) {
        copyValue();
        ev.preventDefault();
      }
    });

    return $wrapper;
  },

  createCopyTextField: function (config) {
    if (!config.id) {
      config.id = 'copytext' + Math.floor(Math.random() * 1000000000);
    }
    return this.createField(this.createCopyTextInput(config), config);
  },

  createCopyTextPrompt: function (config) {
    let $container = $('<div/>', {
      class: 'modal fitted',
    });
    let $body = $('<div/>', {
      class: 'body',
    }).appendTo($container);
    this.createCopyTextField(
      $.extend(
        {
          size: Math.max(Math.min(config.value.length, 50), 25),
        },
        config
      )
    ).appendTo($body);

    const $label = $body.find('label');

    // Provide accessible name for modal dialog
    if ($label.length > 0 && $label.attr('id')) {
      $container.attr('aria-labelledby', $label.attr('id'));
    }

    let modal = new Garnish.Modal($container, {
      closeOtherModals: false,
    });
    $container.on('copy', () => {
      modal.hide();
    });
    return $container;
  },

  createTextarea: function (config) {
    var $textarea = $('<textarea/>', {
      class: 'text',
      rows: config.rows || 2,
      cols: config.cols || 50,
      id: config.id,
      name: config.name,
      maxlength: config.maxlength,
      autofocus: config.autofocus && !Garnish.isMobileBrowser(true),
      disabled: !!config.disabled,
      placeholder: config.placeholder,
      html: config.value,
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

  createTextareaField: function (config) {
    if (!config.id) {
      config.id = 'textarea' + Math.floor(Math.random() * 1000000000);
    }
    return this.createField(this.createTextarea(config), config);
  },

  createSelect: function (config) {
    var $container = $('<div/>', {
      class: 'select',
    });

    if (config.class) {
      $container.addClass(config.class);
    }

    var $select = $('<select/>', {
      id: config.id,
      name: config.name,
      autofocus: config.autofocus && Garnish.isMobileBrowser(true),
      disabled: config.disabled,
      'data-target-prefix': config.targetPrefix,
      'aria-labelledby': config.labelledBy,
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
              disabled:
                typeof option.disabled !== 'undefined'
                  ? option.disabled
                  : false,
            });
          }
        } else {
          options.push({
            label: option,
            value: key,
          });
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
          label: option.optgroup,
        }).appendTo($select);
      } else {
        $('<option/>', {
          value: option.value,
          selected: option.value == config.value,
          disabled:
            typeof option.disabled !== 'undefined' ? option.disabled : false,
          html: option.label,
        }).appendTo($optgroup || $select);
      }
    }

    if (config.toggle) {
      $select.addClass('fieldtoggle');
      new Craft.FieldToggle($select);
    }

    return $container;
  },

  createSelectField: function (config) {
    if (!config.id) {
      config.id = 'select' + Math.floor(Math.random() * 1000000000);
    }
    return this.createField(this.createSelect(config), config);
  },

  createCheckbox: function (config) {
    var id = config.id || 'checkbox' + Math.floor(Math.random() * 1000000000);

    var $input = $('<input/>', {
      type: 'checkbox',
      value: typeof config.value !== 'undefined' ? config.value : '1',
      id: id,
      class: 'checkbox',
      name: config.name,
      checked: config.checked ? 'checked' : null,
      autofocus: this.getAutofocusValue(config.autofocus),
      disabled: this.getDisabledValue(config.disabled),
      'data-target': config.toggle,
      'data-reverse-target': config.reverseToggle,
    });

    if (config.class) {
      $input.addClass(config.class);
    }

    if (config.data) {
      Object.entries(config.data).forEach((item) => {
        $input.attr('data-' + item[0], item[1]);
      });
    }

    if (config.aria) {
      Object.entries(config.aria).forEach((item) => {
        $input.attr('aria-' + item[0], item[1]);
      });
    }

    if (config.toggle || config.reverseToggle) {
      $input.addClass('fieldtoggle');
      new Craft.FieldToggle($input);
    }

    var $label = $('<label/>', {
      for: id,
      html: config.label,
    });

    // Should we include a hidden input first?
    if (
      config.name &&
      (config.name.length < 3 || config.name.slice(-2) !== '[]')
    ) {
      return $([
        $('<input/>', {
          type: 'hidden',
          name: config.name,
          value: '',
        })[0],
        $input[0],
        $label[0],
      ]);
    } else {
      return $([$input[0], $label[0]]);
    }
  },

  createCheckboxField: function (config) {
    if (!config.id) {
      config.id = 'checkbox' + Math.floor(Math.random() * 1000000000);
    }

    var fieldClass = ['field', 'checkboxfield'];
    if (config.fieldClass.length > 0) {
      fieldClass = fieldClass.concat(config.fieldClass);
    }
    var $field = $('<div class="' + fieldClass.join(' ') + '"/>', {
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
      $('<div class="instructions"/>')
        .text(config.instructions)
        .appendTo($field);
    }

    return $field;
  },

  createCheckboxSelect: function (config) {
    const $container = $('<div class="checkbox-select"/>');

    if (config.class) {
      $container.addClass(config.class);
    }

    let values = config.values || [];
    let allChecked = false;

    if (config.showAllOption) {
      const allValue = config.allValue || '*';

      if (values === allValue) {
        values = config.options.map((o) => o.value);
        allChecked = true;
      }

      // Create the "All" checkbox
      $('<div/>')
        .appendTo($container)
        .append(
          this.createCheckbox({
            id: config.id,
            class: 'all',
            label: '<b>' + (config.allLabel || Craft.t('app', 'All')) + '</b>',
            name: config.name,
            value: allValue,
            checked: allChecked,
            autofocus: config.autofocus,
          })
        );

      // omit the “all” value from the options
      config.options = config.options.filter((o) => o.value !== allValue);
    } else {
      allChecked = false;
    }

    if (!Array.isArray(values)) {
      values = [];
    }

    if (config.sortable) {
      // Make sure the selected options are listed first
      config.options.sort((a, b) => {
        let aPos = values.indexOf(a.value);
        let bPos = values.indexOf(b.value);
        if (aPos === -1) {
          aPos = values.length;
        }
        if (bPos === -1) {
          bPos = values.length;
        }
        return aPos - bPos;
      });
    }

    // Create the actual options
    for (let i = 0; i < config.options.length; i++) {
      const option = config.options[i];

      const $option = $('<div/>', {
        class: 'checkbox-select-item',
      }).appendTo($container);

      if (config.sortable) {
        $('<div/>', {class: 'icon move'}).appendTo($option);
      }

      this.createCheckbox({
        label: Craft.escapeHtml(option.label),
        name: config.name ? Craft.ensureEndsWith(config.name, '[]') : null,
        value: option.value,
        checked: allChecked || values.includes(option.value),
        disabled: allChecked,
      }).appendTo($option);
    }

    new Garnish.CheckboxSelect($container);

    if (config.sortable) {
      const dragSort = new Garnish.DragSort($container.children(':not(.all)'), {
        handle: '.move',
        axis: 'y',
      });
      $container.data('dragSort', dragSort);
    }

    return $container;
  },

  createCheckboxSelectField: function (config) {
    config.fieldset = true;
    if (!config.id) {
      config.id = 'checkboxselect' + Math.floor(Math.random() * 1000000000);
    }
    return this.createField(this.createCheckboxSelect(config), config);
  },

  createLightswitch: function (config) {
    var value = config.value || '1';
    var indeterminateValue = config.indeterminateValue || '-';

    var $container = $('<button/>', {
      type: 'button',
      class: 'lightswitch',
      'data-value': value,
      'data-indeterminate-value': indeterminateValue,
      id: config.id,
      role: 'switch',
      'aria-checked': config.on
        ? 'true'
        : config.indeterminate
          ? 'mixed'
          : 'false',
      'aria-labelledby': config.labelId,
      'data-target': config.toggle,
      'data-reverse-target': config.reverseToggle,
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
        value: config.on
          ? value
          : config.indeterminate
            ? indeterminateValue
            : '',
        disabled: config.disabled,
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

  createLightswitchField: function (config) {
    if (!config.id) {
      config.id = 'lightswitch' + Math.floor(Math.random() * 1000000000);
    }
    if (!config.labelId) {
      config.labelId = `${config.id}-label`;
    }
    return this.createField(this.createLightswitch(config), config).addClass(
      'lightswitch-field'
    );
  },

  createColorInput: function (config) {
    const id = config.id || 'color' + Math.floor(Math.random() * 1000000000);
    const containerId = config.containerId || id + '-container';
    const name = config.name || null;
    const value = config.value || null;
    const small = config.small || false;
    const autofocus = config.autofocus && Garnish.isMobileBrowser(true);
    const disabled = config.disabled || false;

    const $container = $('<div/>', {
      id: containerId,
      class: 'flex color-container',
    });

    const $colorPreviewContainer = $('<div/>', {
      class: 'color static' + (small ? ' small' : ''),
    }).appendTo($container);

    const $colorPreview = $('<div/>', {
      class: 'color-preview',
      style: config.value ? {backgroundColor: config.value} : null,
    }).appendTo($colorPreviewContainer);

    const $inputContainer = $('<div/>', {
      class: 'color-input-container',
    })
      .append(
        $('<div/>', {
          class: 'color-hex-indicator light code',
          'aria-hidden': 'true',
          text: '#',
        })
      )
      .appendTo($container);

    const $input = this.createTextInput({
      id: id,
      name: name,
      value: Craft.ltrim(value, '#'),
      size: 10,
      class: 'color-input',
      autofocus: autofocus,
      disabled: disabled,
      'aria-label': Craft.t('app', 'Color hex value'),
    }).appendTo($inputContainer);

    new Craft.ColorInput($container);
    return $container;
  },

  createColorField: function (config) {
    config.fieldset = true;
    o;
    if (!config.id) {
      config.id = 'color' + Math.floor(Math.random() * 1000000000);
    }
    return this.createField(this.createColorInput(config), config);
  },

  createDateInput: function (config) {
    const isMobile = Garnish.isMobileBrowser();
    const id =
      (config.id || 'date' + Math.floor(Math.random() * 1000000000)) + '-date';
    const name = config.name || null;
    const inputName = name ? name + '[date]' : null;
    const value =
      config.value && typeof config.value.getMonth === 'function'
        ? config.value
        : null;
    const autofocus = config.autofocus && Garnish.isMobileBrowser(true);
    const disabled = config.disabled || false;

    const $container = $('<div/>', {
      class: 'datewrapper',
    });

    const $input = this.createTextInput({
      id: id,
      type: isMobile ? 'date' : 'text',
      class: isMobile && !value ? 'empty-value' : false,
      name: inputName,
      value: value
        ? isMobile
          ? value.toISOString().split('T')[0]
          : Craft.formatDate(value)
        : '',
      placeholder: ' ',
      autocomplete: false,
      autofocus: autofocus,
      disabled: disabled,
    }).appendTo($container);

    $('<div data-icon="date"></div>').appendTo($container);

    if (name) {
      $('<input/>', {
        type: 'hidden',
        name: name + '[timezone]',
        val: Craft.timezone,
      }).appendTo($container);
    }

    if (isMobile) {
      $input.datetimeinput();
    } else {
      $input.datepicker(
        $.extend(
          {
            defaultDate: value || new Date(),
          },
          Craft.datepickerOptions
        )
      );
    }

    if (config.hasOuterContainer) {
      return $container;
    }

    return $('<div class="datetimewrapper"/>').append($container).datetime();
  },

  createDateField: function (config) {
    if (!config.id) {
      config.id = 'date' + Math.floor(Math.random() * 1000000000);
    }
    return this.createField(this.createDateInput(config), config);
  },

  createDateRangePicker: function (config) {
    var now = new Date();
    var today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    config = $.extend(
      {
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
        startDate: null,
        endDate: null,
      },
      config
    );

    var $menu = $('<div/>', {class: 'menu'});
    var $ul = $('<ul/>', {class: 'padded'}).appendTo($menu);
    var $allOption = $('<a/>')
      .addClass('sel')
      .text(Craft.t('app', 'All'))
      .data('handle', 'all');

    $('<li/>').append($allOption).appendTo($ul);

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
            startDate: new Date(
              now.getFullYear(),
              now.getMonth(),
              now.getDate() - firstDayOffset
            ),
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
            startDate: new Date(
              now.getFullYear(),
              now.getMonth(),
              now.getDate() - 7
            ),
            endDate: today,
          };
          break;
        case 'past30Days':
          option = {
            label: Craft.t('app', 'Past {num} days', {num: 30}),
            startDate: new Date(
              now.getFullYear(),
              now.getMonth(),
              now.getDate() - 30
            ),
            endDate: today,
          };
          break;
        case 'past90Days':
          option = {
            label: Craft.t('app', 'Past {num} days', {num: 90}),
            startDate: new Date(
              now.getFullYear(),
              now.getMonth(),
              now.getDate() - 90
            ),
            endDate: today,
          };
          break;
        case 'pastYear':
          option = {
            label: Craft.t('app', 'Past year'),
            startDate: new Date(
              now.getFullYear(),
              now.getMonth(),
              now.getDate() - 365
            ),
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

    var $flex = $('<div/>', {class: 'flex flex-nowrap padded'}).appendTo($menu);
    var $startDate = this.createDateField({label: Craft.t('app', 'From')})
      .appendTo($flex)
      .find('input');
    var $endDate = this.createDateField({label: Craft.t('app', 'To')})
      .appendTo($flex)
      .find('input');

    // prevent ESC keypresses in the date inputs from closing the menu
    var $dateInputs = $startDate.add($endDate);
    $dateInputs.on('keyup', function (ev) {
      if (
        ev.keyCode === Garnish.ESC_KEY &&
        $(this).data('datepicker') &&
        $(this).data('datepicker').dpDiv.is(':visible')
      ) {
        ev.stopPropagation();
      }
    });

    // prevent clicks in the datepicker divs from closing the menu
    if ($startDate.data('datepicker')) {
      $startDate.data('datepicker').dpDiv.on('mousedown', function (ev) {
        ev.stopPropagation();
      });
    }
    if ($endDate.data('datepicker')) {
      $endDate.data('datepicker').dpDiv.on('mousedown', function (ev) {
        ev.stopPropagation();
      });
    }

    var menu = new Garnish.Menu($menu, {
      onOptionSelect: function (option) {
        var $option = $(option);
        $btn.text($option.text());
        menu.setPositionRelativeToAnchor();
        $menu.find('.sel').removeClass('sel');
        $option.addClass('sel');

        // Update the start/end dates
        if (!$startDate.hasClass('hasDatepicker')) {
          $startDate.val($option.data('startDate'));
          $endDate.val($option.data('endDate'));
        } else {
          $startDate.datepicker('setDate', $option.data('startDate'));
          $endDate.datepicker('setDate', $option.data('endDate'));
        }

        config.onChange(
          $option.data('startDate') || null,
          $option.data('endDate') || null,
          $option.data('handle')
        );
      },
    });

    $dateInputs.on('change', function () {
      let startDate = null;
      let endDate = null;
      // Do the start & end dates match one of our options?
      if (!$startDate.hasClass('hasDatepicker')) {
        let startDateVal = $startDate.val();
        if (startDateVal !== '') {
          startDate = new Date(Date.parse(startDateVal));
        }

        let endDateVal = $endDate.val();
        if (endDateVal !== '') {
          endDate = new Date(Date.parse(endDateVal));
        }
      } else {
        startDate = $startDate.datepicker('getDate');
        endDate = $endDate.datepicker('getDate');
      }

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

    menu.on('hide', function () {
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
      if (!$startDate.hasClass('hasDatepicker')) {
        // we need the date to be in yyyy-mm-dd format
        let offset = config.startDate.getTimezoneOffset();
        let startDate = new Date(
          config.startDate.getTime() - offset * 60 * 1000
        );
        $startDate.val(startDate.toISOString().split('T')[0]);
      } else {
        $startDate.datepicker('setDate', config.startDate);
      }
    }

    if (config.endDate) {
      if (!$endDate.hasClass('hasDatepicker')) {
        // we need the date to be in yyyy-mm-dd format
        let offset = config.endDate.getTimezoneOffset();
        let endDate = new Date(config.endDate.getTime() - offset * 60 * 1000);
        $endDate.val(endDate.toISOString().split('T')[0]);
      } else {
        $endDate.datepicker('setDate', config.endDate);
      }
    }

    if (config.startDate || config.endDate) {
      $dateInputs.trigger('change');
    }

    return $btn;
  },

  createTimeInput: function (config) {
    const isMobile = Garnish.isMobileBrowser();
    const id =
      (config.id || 'time' + Math.floor(Math.random() * 1000000000)) + '-time';
    const name = config.name || null;
    const inputName = name ? name + '[time]' : null;
    const value =
      config.value && typeof config.value.getMonth === 'function'
        ? config.value
        : null;
    const autofocus = config.autofocus && Garnish.isMobileBrowser(true);
    const disabled = config.disabled || false;

    const $container = $('<div/>', {
      class: 'timewrapper',
    });

    const $input = this.createTextInput({
      id: id,
      type: isMobile ? 'time' : 'text',
      class: isMobile && !value ? 'empty-value' : false,
      name: inputName,
      placeholder: ' ',
      autocomplete: false,
      autofocus: autofocus,
      disabled: disabled,
    }).appendTo($container);

    $('<div data-icon="time"></div>').appendTo($container);

    if (name) {
      $('<input/>', {
        type: 'hidden',
        name: name + '[timezone]',
        val: Craft.timezone,
      }).appendTo($container);
    }

    if (isMobile) {
      if (value) {
        $input.val(value.toISOString().split('T')[1]);
      }
      $input.datetimeinput();
    } else {
      $input.timepicker(Craft.timepickerOptions);
      if (value) {
        $input.timepicker(
          'setTime',
          value.getHours() * 3600 + value.getMinutes() * 60 + value.getSeconds()
        );
      }
    }

    if (config.hasOuterContainer) {
      return $container;
    }

    return $('<div class="datetimewrapper"/>').append($container).datetime();
  },

  createTimeField: function (config) {
    if (!config.id) {
      config.id = 'time' + Math.floor(Math.random() * 1000000000);
    }
    return this.createField(this.createTimeInput(config), config);
  },

  createField: function (input, config) {
    const label =
      config.label && config.label !== '__blank__' ? config.label : null;

    const $field = $(config.fieldset ? '<fieldset/>' : '<div/>', {
      class: 'field',
      id: config.fieldId || (config.id ? config.id + '-field' : null),
      'aria-describedby': config.fieldset
        ? this.getDescribedByValue(config)
        : null,
    });

    if (config.first) {
      $field.addClass('first');
    }

    if (config.fieldClass) {
      $field.addClass(config.fieldClass);
    }

    if (label && config.fieldset) {
      $('<legend/>', {
        text: label,
        class: 'visually-hidden',
        'data-label': label,
      }).appendTo($field);
    }

    if (label) {
      const $heading = $('<div class="heading"/>').appendTo($field);

      $(config.fieldset ? '<legend/>' : '<label/>', {
        id:
          config.labelId ||
          (config.id
            ? `${config.id}-${config.fieldset ? 'legend' : 'label'}`
            : null),
        class: config.required ? 'required' : null,
        for: (!config.fieldset && config.id) || null,
        text: label,
      }).appendTo($heading);
    }

    if (config.instructions) {
      $('<div class="instructions"/>')
        .text(config.instructions)
        .attr('id', this.getInstructionsId(config))
        .appendTo($field);
    }

    $('<div class="input"/>').append(input).appendTo($field);

    if (config.tip) {
      const $tip = $('<p class="notice has-icon"/>');
      $('<span class="icon" aria-hidden="true"/>').appendTo($tip);
      $('<span class="visually-hidden"/>')
        .text(Craft.t('app', 'Tip') + ': ')
        .appendTo($tip);
      $('<span/>').text(config.tip).appendTo($tip);
      $tip.appendTo($field);
    }

    if (config.warning) {
      const $warning = $('<p class="warning has-icon"/>');
      $('<span class="icon" aria-hidden="true"/>').appendTo($warning);
      $('<span class="visually-hidden"/>')
        .text(Craft.t('app', 'Warning') + ': ')
        .appendTo($warning);
      $('<span/>').text(config.warning).appendTo($warning);
      $warning.appendTo($field);
    }

    if (config.errors) {
      this.addErrorsToField($field, config.errors);
    }

    return $field;
  },

  addAttributes: function ($element, attributes) {
    for (const name in attributes) {
      const value = attributes[name];
      if (typeof value === 'boolean') {
        if (value) {
          $element.attr(name, '');
        }
      } else if ($.isPlainObject(value)) {
        if (['aria', 'data', 'data-ng', 'ng'].includes(name)) {
          for (const n in value) {
            let v = value[n];
            if (typeof v === 'object') {
              $element.attr(`${name}-${n}`, JSON.stringify(v));
            } else if (typeof v === 'boolean') {
              if (v) {
                $element.attr(`${name}-${n}`, '');
              }
            } else if (v !== null) {
              $element.attr(`${name}-${n}`, v);
            }
          }
        } else if (name === 'class') {
          $element.addClass(value);
        } else if (name === 'style') {
          $element.css(value);
        } else {
          $element.attr(name, value);
        }
      }
    }
  },

  createErrorList: function (errors, fieldErrorsId) {
    const $list = $('<ul class="errors" tabindex="-1"/>');
    if (fieldErrorsId) {
      $list.attr('id', fieldErrorsId);
    }

    if (errors) {
      this.addErrorsToList($list, errors);
    }

    return $list;
  },

  addErrorsToList: function ($list, errors) {
    for (var i = 0; i < errors.length; i++) {
      $('<li/>').text(errors[i].replaceAll('*', '')).appendTo($list);
    }
  },

  addErrorsToField: function ($field, errors) {
    if (!errors) {
      return;
    }

    this.clearErrorsFromField($field);

    $field.addClass('has-errors');
    $field.children('.input').addClass('errors prevalidate');

    const fieldId = $field.attr('id');
    let fieldErrorsId = '';
    if (fieldId) {
      fieldErrorsId = fieldId.replace(new RegExp(`(-field)$`), '-errors');
    }

    let $errors = $field.children('ul.errors');

    if (!$errors.length) {
      $errors = this.createErrorList(null, fieldErrorsId).appendTo($field);
    }

    this.addErrorsToList($errors, errors);
  },

  clearErrorsFromField: function ($field) {
    $field.removeClass('has-errors');
    $field.children('.input').removeClass('errors prevalidate');
    $field.children('ul.errors').remove();
  },

  clearErrorSummary: function ($body) {
    $body.find('.error-summary').remove();
  },

  setFocusOnErrorSummary: function ($body) {
    const errorSummaryContainer = $body.find('.error-summary');
    if (errorSummaryContainer.length > 0) {
      errorSummaryContainer.focus();

      // start listening for clicks on summary errors
      errorSummaryContainer.find('a').on('click', (ev) => {
        if ($(ev.currentTarget).hasClass('cross-site-validate') == false) {
          ev.preventDefault();
          this.anchorSummaryErrorToField(ev.currentTarget, $body);
        }
      });
    }
  },

  findErrorsContainerByErrorKey: function ($body, fieldErrorKey) {
    let errorsElement = $body
      .find(`[data-error-key="${fieldErrorKey}"]`)
      .find('ul.errors');

    return $(errorsElement);
  },

  anchorSummaryErrorToField: function (error, $body) {
    const fieldErrorKey = $(error).attr('data-field-error-key');

    if (!fieldErrorKey) {
      return;
    }

    const $fieldErrorsContainer = this.findErrorsContainerByErrorKey(
      $body,
      fieldErrorKey
    );

    if ($fieldErrorsContainer) {
      // check if we need to switch tabs first
      const fieldTabAnchors = this.findTabAnchorForField(
        $fieldErrorsContainer,
        $body
      );

      if (fieldTabAnchors.length > 0) {
        for (let i = 0; i < fieldTabAnchors.length; i++) {
          let $tabAnchor = $(fieldTabAnchors[i]);
          if ($tabAnchor.attr('aria-selected') == 'false') {
            $tabAnchor.click();
          }
        }
      }

      // check if the parents are collapsed - if yes, expand
      let $collapsedParents = $fieldErrorsContainer.parents(
        '.collapsed, .is-collapsed'
      );
      if ($collapsedParents.length > 0) {
        // expand in the reverse order - from outside in!
        for (let i = $collapsedParents.length; i > 0; i--) {
          let $item = $($collapsedParents[i - 1]);
          if ($item.data('block') != undefined) {
            $item.data('block').expand();
          } else {
            $item.find('.titlebar').trigger('doubletap');
          }
        }
      }

      // focus on the field container that contains the error
      let $field = $fieldErrorsContainer.parents('.field:first');
      if ($field.is(':visible')) {
        $field.attr('tabindex', '-1').focus();
      } else {
        // wait in case the field isn't yet visible; (MatrixInput.expand() has a timeout of 200)
        setTimeout(() => {
          $field.attr('tabindex', '-1').focus();
        }, 201);
      }
    }
  },

  findTabAnchorForField: function ($container, $body) {
    const fieldTabDivs = $container.parents(
      `div[data-id^=tab][role="tabpanel"]`
    );

    let fieldTabAnchors = [];
    fieldTabDivs.each((i, tabDiv) => {
      let tabAnchor = $body
        .find('[role="tablist"]')
        .find('a[href="#' + $(tabDiv).attr('id') + '"]');
      fieldTabAnchors.push(tabAnchor);
    });

    return fieldTabAnchors;
  },

  getInstructionsId: function (config) {
    return config.id
      ? `${config.id}-instructions`
      : `${Math.floor(Math.random() * 1000000000)}-instructions`;
  },

  getAutofocusValue: function (autofocus) {
    return autofocus && !Garnish.isMobileBrowser(true) ? 'autofocus' : null;
  },

  getDisabledValue: function (disabled) {
    return disabled ? 'disabled' : null;
  },

  getDescribedByValue: function (config) {
    let value = '';

    if (config.instructions) {
      value += this.getInstructionsId(config);
    }

    if (value.length) {
      return value;
    }

    return null;
  },
};
