/* jshint -W083 */
/**
 * Editable table class
 */
Craft.EditableTable = Garnish.Base.extend(
  {
    initialized: false,

    id: null,
    baseName: null,
    columns: null,
    sorter: null,
    biggestId: -1,

    $table: null,
    $tbody: null,
    $addRowBtn: null,
    $tableParent: null,
    $statusMessage: null,

    rowCount: 0,
    hasMaxRows: false,
    hasMinRows: false,

    radioCheckboxes: null,

    init: function (id, baseName, columns, settings) {
      this.id = id;
      this.baseName = baseName;
      this.columns = columns;
      this.setSettings(settings, Craft.EditableTable.defaults);
      this.radioCheckboxes = {};

      this.$table = $('#' + id);
      this.$tbody = this.$table.children('tbody');
      this.$tableParent = this.$table.parent();
      this.$statusMessage = this.$tableParent.find('[data-status-message]');
      const $rows = this.$tbody.children();
      this.rowCount = $rows.length;

      // Is this already an editable table?
      if (this.$table.data('editable-table')) {
        console.warn('Double-instantiating an editable table on an element');
        this.$table.data('editable-table').destroy();
      }

      this.$table.data('editable-table', this);

      this.sorter = new Craft.DataTableSorter(this.$table, {
        helperClass: 'editabletablesorthelper',
        copyDraggeeInputValuesToHelper: true,
        onSortChange: () => {
          this.updateAllRows();
        },
      });

      for (let i = 0; i < $rows.length; i++) {
        const $row = $rows.eq(i);
        const id = parseInt(
          $row.attr('data-id').substring(this.settings.rowIdPrefix.length)
        );
        if (id > this.biggestId) {
          this.biggestId = id;
        }
      }

      if (this.isVisible()) {
        this.initialize();
      } else {
        // Give everything a chance to initialize
        window.setTimeout(this.initializeIfVisible.bind(this), 500);
      }

      if (this.settings.minRows && this.rowCount < this.settings.minRows) {
        for (var i = this.rowCount; i < this.settings.minRows; i++) {
          this.addRow();
        }
      }
    },

    isVisible: function () {
      return this.$table.parent().height() > 0;
    },

    initialize: function () {
      if (this.initialized) {
        return false;
      }

      this.initialized = true;
      this.removeListener(Garnish.$win, 'resize');

      const $container = this.$table.parent('.input');
      if ($container.length && this.$table.width() > $container.width()) {
        $container.css('overflow-x', 'auto');
      }

      this.$addRowBtn = this.$table.next('.add');
      this.updateAddRowButton();

      // If there's only one row, disable the action button
      const $actionButtons = this.$tbody.find('.action-btn');
      if (this.rowCount === 1) {
        $actionButtons.attr('disabled', 'disabled').addClass('disabled');
      }

      this.addListener(this.$addRowBtn, 'activate', 'addRow');

      // don't allow lazyInitRows if any of the columns are radio checkboxes
      this.settings.lazyInitRows =
        this.settings.lazyInitRows &&
        !Object.entries(this.columns).some(
          ([colId, col]) => col.type === 'checkbox' && col.radioMode
        );

      if (this.settings.lazyInitRows) {
        // Lazily create the row objects
        this.addListener(
          this.$tbody.add($actionButtons),
          'keypress,keyup,change,focus,blur,click,mousedown,mouseup',
          (ev) => {
            const $target = $(ev.target);
            const $tr = $target.closest('tr');
            if ($tr.length && !$tr.data('editable-table-row')) {
              const $textarea = $target.hasClass('editable-table-preview')
                ? $target.next()
                : null;
              this.createRowObj($tr);
              setTimeout(() => {
                if ($textarea && !$textarea.is(':focus')) {
                  $textarea.focus();
                }
              }, 100);
            }
          }
        );
      } else {
        const $rows = this.$tbody.children();
        for (let i = 0; i < $rows.length; i++) {
          this.createRowObj($rows.eq(i));
        }
      }

      return true;
    },
    initializeIfVisible: function () {
      this.removeListener(Garnish.$win, 'resize');

      if (this.isVisible()) {
        this.initialize();
      } else {
        this.addListener(Garnish.$win, 'resize', 'initializeIfVisible');
      }
    },
    updateAddRowButton: function () {
      if (!this.canAddRow()) {
        this.$addRowBtn.css('opacity', '0.2');
        this.$addRowBtn.css('pointer-events', 'none');
        this.$addRowBtn.attr('aria-disabled', 'true');
      } else {
        this.$addRowBtn.css('opacity', '1');
        this.$addRowBtn.css('pointer-events', 'auto');
        this.$addRowBtn.attr('aria-disabled', 'false');
      }
    },
    updateAllRows: function () {
      if (this.settings.staticRows) {
        return;
      }
      const $rows = this.$table.find('> tbody > tr');
      for (let i = 0; i < $rows.length; i++) {
        this.updateRow($rows.eq(i));
      }
    },
    updateRow: function ($row) {
      if (this.settings.staticRows) {
        return;
      }

      const $deleteBtn = $row.find('button.delete');
      const $actionsBtn = $row.find('button.action-btn');

      if ($deleteBtn.length) {
        $deleteBtn.attr(
          'aria-label',
          Craft.t('app', 'Delete row {index}', {
            index: $row.index() + 1,
          })
        );
        if (this.canDeleteRow()) {
          $deleteBtn.removeAttr('disabled').removeClass('disabled');
        } else {
          $deleteBtn.attr('disabled', 'disabled').addClass('disabled');
        }
      }

      if ($actionsBtn.length) {
        const name = `${Craft.t('app', 'Row {index}', {
          index: $row.index() + 1,
        })} ${Craft.t('app', 'Actions')}`;
        $actionsBtn.attr('aria-label', name);

        if (this.rowCount === 1) {
          $actionsBtn.attr('disabled', 'disabled').addClass('disabled');
          $actionsBtn.attr('disabled', 'disabled').addClass('disabled');
        } else {
          $actionsBtn.removeAttr('disabled').removeClass('disabled');
        }
      }
    },
    /**
     * @deprecated
     */
    updateDeleteRowButton: function (rowId) {
      this.updateRow(this.$table.find(`tr[data-id="${rowId}"]`));
    },
    updateStatusMessage: function () {
      this.$statusMessage.empty();
      let message;

      if (!this.canAddRow()) {
        message = Craft.t(
          'app',
          'Row could not be added. Maximum number of rows reached.'
        );
      } else {
        message = Craft.t(
          'app',
          'Row could not be deleted. Minimum number of rows reached.'
        );
      }

      setTimeout(() => {
        this.$statusMessage.text(message);
      }, 250);
    },
    canDeleteRow: function () {
      if (!this.settings.allowDelete) {
        return false;
      }

      return this.rowCount > this.settings.minRows;
    },
    deleteRow: function (row) {
      if (!this.canDeleteRow()) {
        this.updateStatusMessage();
        return;
      }

      this.sorter.removeItems(row.$tr);
      row.$tr.remove();

      this.rowCount--;

      this.updateAllRows();
      this.updateAddRowButton();

      if (this.rowCount === 0) {
        this.$table.addClass('hidden');
        this.$addRowBtn.focus();
      } else {
        // Focus element in previous row
        this.$tbody.find(':focusable').last().focus();
      }

      // onDeleteRow callback
      this.settings.onDeleteRow(row.$tr);

      row.destroy();
    },
    canAddRow: function () {
      if (!this.settings.allowAdd) {
        return false;
      }

      if (this.settings.maxRows) {
        return this.rowCount < this.settings.maxRows;
      }

      return true;
    },
    addRow: function (focus, prepend) {
      if (!this.canAddRow()) {
        this.updateStatusMessage();
        return;
      }

      var rowId = this.settings.rowIdPrefix + (this.biggestId + 1),
        $tr = this.createRow(
          rowId,
          this.columns,
          this.baseName,
          $.extend({}, this.settings.defaultValues)
        );

      if (prepend) {
        $tr.prependTo(this.$tbody);
      } else {
        $tr.appendTo(this.$tbody);
      }

      var row = this.createRowObj($tr);
      this.sorter.addItems($tr);

      // Focus the first input in the row
      if (focus !== false) {
        $tr
          .find('input:visible,textarea:visible,select:visible')
          .first()
          .focus();
      }

      this.rowCount++;
      this.updateAllRows();
      this.updateAddRowButton();
      this.$table.removeClass('hidden');

      // onAddRow callback
      this.settings.onAddRow($tr);

      return row;
    },

    createRow: function (rowId, columns, baseName, values) {
      return Craft.EditableTable.createRow(
        rowId,
        columns,
        baseName,
        values,
        this.settings.allowReorder,
        this.settings.allowDelete
      );
    },

    getRowObj: function ($tr) {
      return $tr.data('editable-table-row') || this.createRowObj($tr);
    },

    createRowObj: function ($tr) {
      return new Craft.EditableTable.Row(this, $tr);
    },

    focusOnPrevRow: function ($tr, tdIndex, blurTd) {
      var $prevTr = $tr.prev('tr');
      var prevRow;

      if ($prevTr.length) {
        prevRow = this.getRowObj($prevTr);
      } else {
        prevRow = this.addRow(false, true);
      }

      // Focus on the same cell in the previous row
      if (!prevRow) {
        return;
      }

      if (!prevRow.$tds[tdIndex]) {
        return;
      }

      if ($(prevRow.$tds[tdIndex]).hasClass('disabled')) {
        if ($prevTr) {
          this.focusOnPrevRow($prevTr, tdIndex, blurTd);
        }
        return;
      }

      var $input = $('textarea,input.text', prevRow.$tds[tdIndex]);
      if ($input.length) {
        $(blurTd).trigger('blur');
        $input.focus();
      }
    },

    focusOnNextRow: function ($tr, tdIndex, blurTd) {
      var $nextTr = $tr.next('tr');
      var nextRow;

      if ($nextTr.length) {
        nextRow = this.getRowObj($nextTr);
      } else {
        nextRow = this.addRow(false);
      }

      // Focus on the same cell in the next row
      if (!nextRow) {
        return;
      }

      if (!nextRow.$tds[tdIndex]) {
        return;
      }

      if ($(nextRow.$tds[tdIndex]).hasClass('disabled')) {
        if ($nextTr) {
          this.focusOnNextRow($nextTr, tdIndex, blurTd);
        }
        return;
      }

      var $input = $('textarea,input.text', nextRow.$tds[tdIndex]);
      if ($input.length) {
        $(blurTd).trigger('blur');
        $input.focus();
      }
    },

    importData: function (data, row, tdIndex) {
      let lines = data.split(/\r?\n|\r/);
      for (let i = 0; i < lines.length; i++) {
        let values = lines[i].split('\t');
        for (let j = 0; j < values.length; j++) {
          let value = values[j];
          row.$tds
            .eq(tdIndex + j)
            .find('textarea,input[type!=hidden]')
            .val(value)
            .trigger('input');
        }

        // move onto the next row
        let $nextTr = row.$tr.next('tr');
        if ($nextTr.length) {
          row = this.getRowObj($nextTr);
        } else {
          row = this.addRow(false);
        }
      }
    },

    destroy: function () {
      this.$table.removeData('editable-table');
      this.base();
    },
  },
  {
    textualColTypes: [
      'autosuggest',
      'color',
      'date',
      'email',
      'multiline',
      'number',
      'singleline',
      'template',
      'time',
      'url',
    ],
    defaults: {
      rowIdPrefix: '',
      defaultValues: {},
      allowAdd: false,
      allowReorder: false,
      allowDelete: false,
      minRows: null,
      maxRows: null,
      lazyInitRows: true,
      onAddRow: $.noop,
      onDeleteRow: $.noop,
    },

    createRow: function (
      rowId,
      columns,
      baseName,
      values,
      allowReorder,
      allowDelete
    ) {
      var $tr = $('<tr/>', {
        'data-id': rowId,
      });

      for (var colId in columns) {
        if (!columns.hasOwnProperty(colId)) {
          continue;
        }

        var col = columns[colId],
          value = typeof values[colId] !== 'undefined' ? values[colId] : '',
          $cell;

        if (col.type === 'heading') {
          $cell = $('<th/>', {
            scope: 'row',
            class: col['class'],
            html: value,
          });
        } else {
          var name = baseName + '[' + rowId + '][' + colId + ']';

          $cell = $('<td/>', {
            class: `${col.class ?? ''} ${col.type}-cell`,
            width: col.width,
          });

          if (Craft.inArray(col.type, Craft.EditableTable.textualColTypes)) {
            $cell.addClass('textual');
          }

          if (col.code) {
            $cell.addClass('code');
          }

          switch (col.type) {
            case 'checkbox':
              $('<div class="checkbox-wrapper"/>')
                .append(
                  Craft.ui.createCheckbox({
                    name: name,
                    value: col.value || '1',
                    checked: !!value,
                  })
                )
                .appendTo($cell);
              break;

            case 'color':
              Craft.ui
                .createColorInput({
                  name: name,
                  value: typeof value !== 'object' ? value : null,
                  small: true,
                })
                .appendTo($cell);
              break;

            case 'date':
              Craft.ui
                .createDateInput({
                  name: name,
                  value: value,
                })
                .appendTo($cell);
              break;

            case 'lightswitch':
              Craft.ui
                .createLightswitch({
                  name: name,
                  value: col.value || '1',
                  on: !!value,
                  small: true,
                })
                .appendTo($cell);
              break;

            case 'select':
              Craft.ui
                .createSelect({
                  name: name,
                  options: col.options,
                  value:
                    value ||
                    (function () {
                      for (var key in col.options) {
                        if (
                          col.options.hasOwnProperty(key) &&
                          col.options[key].default
                        ) {
                          return typeof col.options[key].value !== 'undefined'
                            ? col.options[key].value
                            : key;
                        }
                      }
                      return null;
                    })(),
                  class: 'small',
                })
                .appendTo($cell);
              break;

            case 'time':
              Craft.ui
                .createTimeInput({
                  name: name,
                  value: value,
                })
                .appendTo($cell);
              break;

            case 'email':
            case 'url':
              Craft.ui
                .createTextInput({
                  name: name,
                  value: typeof value !== 'object' ? value : null,
                  type: col.type,
                  placeholder: col.placeholder || null,
                })
                .appendTo($cell);
              break;

            default:
              $('<textarea/>', {
                name: name,
                rows: col.rows || 1,
                val: typeof value !== 'object' ? value : null,
                placeholder: col.placeholder,
              }).appendTo($cell);
          }
        }

        $cell.appendTo($tr);
      }

      if (allowReorder) {
        const containerId = `menu-${Math.floor(Math.random() * 1000000)}`;
        const $actionsBtn = $('<button/>', {
          class: 'btn menu-btn action-btn',
          type: 'button',
          title: Craft.t('app', 'Actions'),
          'aria-controls': containerId,
          'data-disclosure-trigger': 'true',
        });
        const $menuContainer = $('<div/>', {
          id: containerId,
          class: 'menu menu--disclosure',
        });

        const $td = $('<td/>', {
          class: 'thin action',
        }).appendTo($tr);

        $('<div/>', {
          class: 'flex flex-nowrap',
        })
          .append(
            $('<a/>', {
              class: 'move icon',
              title: Craft.t('app', 'Reorder'),
              role: 'button',
              type: 'button',
            })
          )
          .append($actionsBtn)
          .append($menuContainer)
          .appendTo($td);

        const menu = $actionsBtn.disclosureMenu().data('disclosureMenu');

        menu.addItems([
          {
            icon: 'arrow-up',
            label: Craft.t('app', 'Move up'),
            attributes: {'data-action': 'moveUp'},
          },
          {
            icon: 'arrow-down',
            label: Craft.t('app', 'Move down'),
            attributes: {'data-action': 'moveDown'},
          },
        ]);
      }

      if (allowDelete) {
        $('<td/>', {
          class: 'thin action',
        })
          .append(
            $('<button/>', {
              class: 'delete icon',
              title: Craft.t('app', 'Delete'),
              type: 'button',
            })
          )
          .appendTo($tr);
      }

      return $tr;
    },
  }
);

/**
 * Editable table row class
 */
Craft.EditableTable.Row = Garnish.Base.extend(
  {
    table: null,
    id: null,
    niceTexts: null,

    $tr: null,
    $tds: null,
    tds: null,
    $textareas: null,
    $deleteBtn: null,

    get prevRow() {
      return this.$tr.prev('tr');
    },

    get nextRow() {
      return this.$tr.next('tr');
    },

    init: function (table, tr) {
      this.table = table;
      this.$tr = $(tr);
      this.$tds = this.$tr.children();
      this.tds = [];
      this.id = this.$tr.attr('data-id');

      this.$tr.data('editable-table-row', this);

      // Get the row ID, sans prefix
      var id = parseInt(
        this.id.substring(this.table.settings.rowIdPrefix.length)
      );

      if (id > this.table.biggestId) {
        this.table.biggestId = id;
      }

      this.$textareas = $();
      this.niceTexts = [];
      var textareasByColId = {};

      var i = 0;
      var colId, col, td, $checkbox;

      for (colId in this.table.columns) {
        if (!this.table.columns.hasOwnProperty(colId)) {
          continue;
        }

        col = this.table.columns[colId];
        td = this.tds[colId] = this.$tds[i];

        if (Craft.inArray(col.type, Craft.EditableTable.textualColTypes)) {
          $('.editable-table-preview', td).remove();
          const $textarea = $('textarea', td);
          this.$textareas = this.$textareas.add($textarea);

          this.addListener($textarea, 'focus', 'onTextareaFocus');
          this.addListener($textarea, 'mousedown', 'ignoreNextTextareaFocus');

          this.niceTexts.push(
            new Garnish.NiceText($textarea, {
              onHeightChange: this.onTextareaHeightChange.bind(this),
            })
          );

          this.addListener(
            $textarea,
            'keypress',
            {tdIndex: i, type: col.type},
            'handleKeypress'
          );
          this.addListener(
            $textarea,
            'input',
            {type: col.type},
            'validateValue'
          );
          $textarea.trigger('input');

          if (col.type !== 'multiline') {
            this.addListener(
              $textarea,
              'paste',
              {tdIndex: i, type: col.type},
              'handlePaste'
            );
          }

          textareasByColId[colId] = $textarea;
        } else if (col.type === 'checkbox') {
          $checkbox = $('input[type="checkbox"]', td);

          if (col.radioMode) {
            if (typeof this.table.radioCheckboxes[colId] === 'undefined') {
              this.table.radioCheckboxes[colId] = [];
            }
            this.table.radioCheckboxes[colId].push($checkbox[0]);
            this.addListener(
              $checkbox,
              'change',
              {colId},
              'onRadioCheckboxChange'
            );
          }

          if (col.toggle) {
            this.addListener($checkbox, 'change', {colId}, function (ev) {
              this.applyToggleCheckbox(ev.data.colId);
            });
          }
        }

        if (!$(td).hasClass('disabled')) {
          this.addListener(td, 'click', {td}, function (ev) {
            if (ev.target === ev.data.td) {
              $(ev.data.td).find('textarea,input,select,.lightswitch').focus();
            }
          });
        }

        i++;
      }

      // Now that all of the text cells have been nice-ified, let's normalize the heights
      this.onTextareaHeightChange();

      // See if we need to apply any checkbox toggles now that we've indexed all the TDs
      for (colId in this.table.columns) {
        if (!this.table.columns.hasOwnProperty(colId)) {
          continue;
        }
        col = this.table.columns[colId];
        if (col.type === 'checkbox' && col.toggle) {
          this.applyToggleCheckbox(colId);
        }
      }

      // Now look for any autopopulate columns
      for (colId in this.table.columns) {
        if (!this.table.columns.hasOwnProperty(colId)) {
          continue;
        }

        col = this.table.columns[colId];

        if (
          col.autopopulate &&
          typeof textareasByColId[col.autopopulate] !== 'undefined' &&
          !textareasByColId[colId].val() &&
          !textareasByColId[col.autopopulate].val()
        ) {
          new Craft.HandleGenerator(
            textareasByColId[colId],
            textareasByColId[col.autopopulate],
            {
              allowNonAlphaStart: true,
            }
          );
        }
      }

      var $deleteBtn = this.$tr.children().last().find('.delete');
      this.addListener($deleteBtn, 'click', 'deleteRow');

      var $inputs = this.$tr.find('input,textarea,select,.lightswitch');
      this.addListener($inputs, 'focus', function (ev) {
        $(ev.currentTarget).closest('td:not(.disabled)').addClass('focus');
      });
      this.addListener($inputs, 'blur', function (ev) {
        $(ev.currentTarget).closest('td').removeClass('focus');
      });

      // Action menu modification
      const $actionMenuBtn = this.$tr.find('> .action .action-btn');

      if ($actionMenuBtn.length) {
        this.actionDisclosure =
          $actionMenuBtn.data('trigger') ||
          new Garnish.DisclosureMenu($actionMenuBtn);
        this.$actionMenu = this.actionDisclosure.$container;

        this.actionDisclosure.on('show', () => {
          this.updateDisclosureMenu();

          // Fixes issue focusing caused by hiding button
          const $focusableBtn = Garnish.firstFocusableElement(this.$actionMenu);
          $focusableBtn.focus();
        });

        this.$actionMenuOptions = this.$actionMenu.find('button[data-action]');

        this.addListener(
          this.$actionMenuOptions,
          'activate',
          this.handleActionClick
        );
      }
    },

    updateDisclosureMenu: function () {
      if (this.prevRow.length) {
        this.$actionMenu
          .find('button[data-action=moveUp]:first')
          .parent()
          .removeClass('hidden');
      } else {
        this.$actionMenu
          .find('button[data-action=moveUp]:first')
          .parent()
          .addClass('hidden');
      }
      if (this.nextRow.length) {
        this.$actionMenu
          .find('button[data-action=moveDown]:first')
          .parent()
          .removeClass('hidden');
      } else {
        this.$actionMenu
          .find('button[data-action=moveDown]:first')
          .parent()
          .addClass('hidden');
      }
    },

    handleActionClick: function (event) {
      event.preventDefault();
      this.onActionSelect(event.target);
    },

    onActionSelect: function (option) {
      $option = $(option);
      switch ($option.data('action')) {
        case 'moveUp': {
          this.moveUp();
          break;
        }

        case 'moveDown': {
          this.moveDown();
          break;
        }
      }

      this.actionDisclosure.hide();
    },

    moveUp: function () {
      let $prev = this.prevRow;
      if ($prev.length) {
        this.$tr.insertBefore($prev);
        this.table.updateAllRows();
      }
    },

    moveDown: function () {
      let $next = this.nextRow;
      if ($next.length) {
        this.$tr.insertAfter($next);
        this.table.updateAllRows();
      }
    },

    onTextareaFocus: function (ev) {
      this.onTextareaHeightChange();

      var $textarea = $(ev.currentTarget);

      if ($textarea.data('ignoreNextFocus')) {
        $textarea.data('ignoreNextFocus', false);
        return;
      }

      window.setTimeout(function () {
        Craft.selectFullValue($textarea);
      }, 0);
    },

    onRadioCheckboxChange: function (ev) {
      if (ev.currentTarget.checked) {
        for (
          var i = 0;
          i < this.table.radioCheckboxes[ev.data.colId].length;
          i++
        ) {
          var checkbox = this.table.radioCheckboxes[ev.data.colId][i];
          checkbox.checked = checkbox === ev.currentTarget;
        }
      }
    },

    applyToggleCheckbox: function (checkboxColId) {
      var checkboxCol = this.table.columns[checkboxColId];
      var checked = $('input[type="checkbox"]', this.tds[checkboxColId]).prop(
        'checked'
      );
      var colId, colIndex, neg;
      for (var i = 0; i < checkboxCol.toggle.length; i++) {
        colId = checkboxCol.toggle[i];
        colIndex = this.table.colum;
        neg = colId[0] === '!';
        if (neg) {
          colId = colId.substring(1);
        }
        if ((checked && !neg) || (!checked && neg)) {
          $(this.tds[colId])
            .removeClass('disabled')
            .find('textarea, input')
            .prop('disabled', false);
        } else {
          $(this.tds[colId])
            .addClass('disabled')
            .find('textarea, input')
            .prop('disabled', true);
        }
      }
    },

    ignoreNextTextareaFocus: function (ev) {
      $.data(ev.currentTarget, 'ignoreNextFocus', true);
    },

    handleKeypress: function (ev) {
      var keyCode = ev.keyCode ? ev.keyCode : ev.charCode;
      var ctrl = Garnish.isCtrlKeyPressed(ev);

      // Going to the next/previous row?
      if (
        keyCode === Garnish.RETURN_KEY &&
        (ev.data.type !== 'multiline' || ctrl)
      ) {
        ev.preventDefault();
        if (ev.shiftKey) {
          this.table.focusOnPrevRow(
            this.$tr,
            ev.data.tdIndex,
            ev.currentTarget
          );
        } else {
          this.table.focusOnNextRow(
            this.$tr,
            ev.data.tdIndex,
            ev.currentTarget
          );
        }
        return;
      }
    },

    handlePaste: function (ev) {
      let data = Craft.trim(
        ev.originalEvent.clipboardData.getData('Text'),
        ' \n\r'
      );
      if (!data.match(/[\t\r\n]/)) {
        return;
      }
      ev.preventDefault();
      this.table.importData(data, this, ev.data.tdIndex);
    },

    validateValue: function (ev) {
      if (ev.data.type === 'multiline') {
        return;
      }

      if (ev.data.type === 'number') {
        Craft.filterNumberInputVal(ev.currentTarget);
        return;
      }

      // Strip any newlines
      const safeValue = ev.currentTarget.value.replace(/[\r\n]/g, '');
      if (safeValue !== ev.currentTarget.value) {
        ev.currentTarget.value = safeValue;
      }
    },

    onTextareaHeightChange: function () {
      // Keep all the textareas' heights in sync
      var tallestTextareaHeight = -1;

      for (var i = 0; i < this.niceTexts.length; i++) {
        if (this.niceTexts[i].height > tallestTextareaHeight) {
          tallestTextareaHeight = this.niceTexts[i].height;
        }
      }

      this.$textareas.css('min-height', tallestTextareaHeight);

      // If the <td> is still taller, go with that instead
      var tdHeight = this.$textareas
        .filter(':visible')
        .first()
        .parent()
        .height();

      if (tdHeight > tallestTextareaHeight) {
        this.$textareas.css('min-height', tdHeight);
      }
    },

    deleteRow: function () {
      this.table.deleteRow(this);
    },
  },
  {
    /** @deprecated */
    numericKeyCodes: [
      9 /* (tab) */, 8 /* (delete) */, 37, 38, 39, 40 /* (arrows) */, 45,
      91 /* (minus) */, 46, 190 /* period */, 48, 49, 50, 51, 52, 53, 54, 55,
      56, 57 /* (0-9) */,
    ],
  }
);
