/** global: Craft */
/** global: Garnish */
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

        rowCount: 0,
        hasMaxRows: false,
        hasMinRows: false,

        radioCheckboxes: null,

        init: function(id, baseName, columns, settings) {
            this.id = id;
            this.baseName = baseName;
            this.columns = columns;
            this.setSettings(settings, Craft.EditableTable.defaults);
            this.radioCheckboxes = {};

            this.$table = $('#' + id);
            this.$tbody = this.$table.children('tbody');
            this.rowCount = this.$tbody.find('tr').length;

            // Is this already an editable table?
            if (this.$table.data('editable-table')) {
                Garnish.log('Double-instantiating an editable table on an element');
                this.$table.data('editable-table').destroy();
            }

            this.$table.data('editable-table', this);

            this.sorter = new Craft.DataTableSorter(this.$table, {
                helperClass: 'editabletablesorthelper',
                copyDraggeeInputValuesToHelper: true
            });

            if (this.isVisible()) {
                this.initialize();
            } else {
                // Give everything a chance to initialize
                setTimeout($.proxy(this, 'initializeIfVisible'), 500);
            }

            if (this.settings.minRows && this.rowCount < this.settings.minRows) {
                for (var i = this.rowCount; i < this.settings.minRows; i++) {
                    this.addRow()
                }
            }
        },

        isVisible: function() {
            return (this.$table.parent().height() > 0);
        },

        initialize: function() {
            if (this.initialized) {
                return false;
            }

            this.initialized = true;
            this.removeListener(Garnish.$win, 'resize');

            var $rows = this.$tbody.children();

            for (var i = 0; i < $rows.length; i++) {
                this.createRowObj($rows[i]);
            }

            this.$addRowBtn = this.$table.next('.add');
            this.updateAddRowButton();
            this.addListener(this.$addRowBtn, 'activate', 'addRow');
            return true;
        },
        initializeIfVisible: function() {
            this.removeListener(Garnish.$win, 'resize');

            if (this.isVisible()) {
                this.initialize();
            } else {
                this.addListener(Garnish.$win, 'resize', 'initializeIfVisible');
            }
        },
        updateAddRowButton: function() {
            if (!this.canAddRow()) {
                this.$addRowBtn.css('opacity', '0.2');
                this.$addRowBtn.css('pointer-events', 'none');
            } else {
                this.$addRowBtn.css('opacity', '1');
                this.$addRowBtn.css('pointer-events', 'auto');
            }
        },
        canDeleteRow: function() {
            return (this.rowCount > this.settings.minRows);
        },
        deleteRow: function(row) {
            if (!this.canDeleteRow()) {
                return;
            }

            this.sorter.removeItems(row.$tr);
            row.$tr.remove();

            this.rowCount--;

            this.updateAddRowButton();
            if (this.rowCount === 0) {
                this.$table.addClass('hidden');
            }

            // onDeleteRow callback
            this.settings.onDeleteRow(row.$tr);

            row.destroy();
        },
        canAddRow: function() {
            if (this.settings.staticRows) {
                return false;
            }

            if (this.settings.maxRows) {
                return (this.rowCount < this.settings.maxRows);
            }

            return true;
        },
        addRow: function(focus, prepend) {
            if (!this.canAddRow()) {
                return;
            }

            var rowId = this.settings.rowIdPrefix + (this.biggestId + 1),
                $tr = this.createRow(rowId, this.columns, this.baseName, $.extend({}, this.settings.defaultValues));

            if (prepend) {
                $tr.prependTo(this.$tbody);
            } else {
                $tr.appendTo(this.$tbody);
            }

            var row = this.createRowObj($tr);
            this.sorter.addItems($tr);

            // Focus the first input in the row
            if (focus !== false) {
                $tr.find('input:visible,textarea:visible,select:visible').first().trigger('focus');
            }

            this.rowCount++;
            this.updateAddRowButton();
            this.$table.removeClass('hidden');

            // onAddRow callback
            this.settings.onAddRow($tr);

            return row;
        },

        createRow: function(rowId, columns, baseName, values) {
            return Craft.EditableTable.createRow(rowId, columns, baseName, values);
        },

        createRowObj: function($tr) {
            return new Craft.EditableTable.Row(this, $tr);
        },

        focusOnPrevRow: function($tr, tdIndex, blurTd) {
            var $prevTr = $tr.prev('tr');
            var prevRow;

            if ($prevTr.length) {
                prevRow = $prevTr.data('editable-table-row');
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
                $input.trigger('focus');
            }
        },

        focusOnNextRow: function($tr, tdIndex, blurTd) {
            var $nextTr = $tr.next('tr');
            var nextRow;

            if ($nextTr.length) {
                nextRow = $nextTr.data('editable-table-row');
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
                $input.trigger('focus');
            }
        },

        importData: function(data, row, tdIndex) {
            let lines = data.split(/\r?\n|\r/);
            for (let i = 0; i < lines.length; i++) {
                let values = lines[i].split("\t");
                for (let j = 0; j < values.length; j++) {
                    let value = values[j];
                    row.$tds.eq(tdIndex + j).find('textarea,input[type!=hidden]')
                        .val(value)
                        .trigger('input');
                }

                // move onto the next row
                let $nextTr = row.$tr.next('tr');
                if ($nextTr.length) {
                    row = $nextTr.data('editable-table-row');
                } else {
                    row = this.addRow(false);
                }
            }
        },
    },
    {
        textualColTypes: ['color', 'date', 'email', 'multiline', 'number', 'singleline', 'template', 'time', 'url'],
        defaults: {
            rowIdPrefix: '',
            defaultValues: {},
            staticRows: false,
            minRows: null,
            maxRows: null,
            onAddRow: $.noop,
            onDeleteRow: $.noop
        },

        createRow: function(rowId, columns, baseName, values) {
            var $tr = $('<tr/>', {
                'data-id': rowId
            });

            for (var colId in columns) {
                if (!columns.hasOwnProperty(colId)) {
                    continue;
                }

                var col = columns[colId],
                    value = (typeof values[colId] !== 'undefined' ? values[colId] : ''),
                    $cell;

                if (col.type === 'heading') {
                    $cell = $('<th/>', {
                        'scope': 'row',
                        'class': col['class'],
                        'html': value
                    });
                } else {
                    var name = baseName + '[' + rowId + '][' + colId + ']';

                    $cell = $('<td/>', {
                        'class': `${col.class} ${col.type}-cell`,
                        'width': col.width
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
                                .append(Craft.ui.createCheckbox({
                                        name: name,
                                        value: col.value || '1',
                                        checked: !!value
                                    })
                                )
                                .appendTo($cell);
                            break;

                        case 'color':
                            Craft.ui.createColorInput({
                                name: name,
                                value: value,
                                small: true
                            }).appendTo($cell);
                            break;

                        case 'date':
                            Craft.ui.createDateInput({
                                name: name,
                                value: value
                            }).appendTo($cell);
                            break;

                        case 'lightswitch':
                            Craft.ui.createLightswitch({
                                name: name,
                                value: col.value || '1',
                                on: !!value,
                                small: true
                            }).appendTo($cell);
                            break;

                        case 'select':
                            Craft.ui.createSelect({
                                name: name,
                                options: col.options,
                                value: value || (function() {
                                    for (var key in col.options) {
                                        if (col.options.hasOwnProperty(key) && col.options[key].default) {
                                            return typeof col.options[key].value !== 'undefined' ? col.options[key].value : key;
                                        }
                                    }
                                    return null;
                                })(),
                                'class': 'small'
                            }).appendTo($cell);
                            break;

                        case 'time':
                            Craft.ui.createTimeInput({
                                name: name,
                                value: value
                            }).appendTo($cell);
                            break;

                        case 'email':
                        case 'url':
                            Craft.ui.createTextInput({
                                name: name,
                                value: value,
                                type: col.type,
                                placeholder: col.placeholder || null,
                            }).appendTo($cell);
                            break;

                        default:
                            $('<textarea/>', {
                                'name': name,
                                'rows': col.rows || 1,
                                'val': value,
                                'placeholder': col.placeholder
                            }).appendTo($cell);
                    }
                }

                $cell.appendTo($tr);
            }

            $('<td/>', {
                'class': 'thin action'
            }).append(
                $('<a/>', {
                    'class': 'move icon',
                    'title': Craft.t('app', 'Reorder')
                })
            ).appendTo($tr);

            $('<td/>', {
                'class': 'thin action'
            }).append(
                $('<a/>', {
                    'class': 'delete icon',
                    'title': Craft.t('app', 'Delete')
                })
            ).appendTo($tr);

            return $tr;
        }
    });

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

        init: function(table, tr) {
            this.table = table;
            this.$tr = $(tr);
            this.$tds = this.$tr.children();
            this.tds = [];
            this.id = this.$tr.attr('data-id');

            this.$tr.data('editable-table-row', this);

            // Get the row ID, sans prefix
            var id = parseInt(this.id.substr(this.table.settings.rowIdPrefix.length));

            if (id > this.table.biggestId) {
                this.table.biggestId = id;
            }

            this.$textareas = $();
            this.niceTexts = [];
            var textareasByColId = {};

            var i = 0;
            var colId, col, td, $textarea, $checkbox;

            for (colId in this.table.columns) {
                if (!this.table.columns.hasOwnProperty(colId)) {
                    continue;
                }

                col = this.table.columns[colId];
                td = this.tds[colId] = this.$tds[i];

                if (Craft.inArray(col.type, Craft.EditableTable.textualColTypes)) {
                    $textarea = $('textarea', td);
                    this.$textareas = this.$textareas.add($textarea);

                    this.addListener($textarea, 'focus', 'onTextareaFocus');
                    this.addListener($textarea, 'mousedown', 'ignoreNextTextareaFocus');

                    this.niceTexts.push(new Garnish.NiceText($textarea, {
                        onHeightChange: $.proxy(this, 'onTextareaHeightChange')
                    }));

                    this.addListener($textarea, 'keypress', {tdIndex: i, type: col.type}, 'handleKeypress');
                    this.addListener($textarea, 'input', {type: col.type}, 'validateValue');
                    $textarea.trigger('input');

                    if (col.type !== 'multiline') {
                        this.addListener($textarea, 'paste', {tdIndex: i, type: col.type}, 'handlePaste');
                    }

                    textareasByColId[colId] = $textarea;
                } else if (col.type === 'checkbox') {
                    $checkbox = $('input[type="checkbox"]', td);

                    if (col.radioMode) {
                        if (typeof this.table.radioCheckboxes[colId] === 'undefined') {
                            this.table.radioCheckboxes[colId] = [];
                        }
                        this.table.radioCheckboxes[colId].push($checkbox[0]);
                        this.addListener($checkbox, 'change', {colId: colId}, 'onRadioCheckboxChange');
                    }

                    if (col.toggle) {
                        this.addListener($checkbox, 'change', {colId: colId}, function(ev) {
                            this.applyToggleCheckbox(ev.data.colId);
                        });
                    }
                }

                if (!$(td).hasClass('disabled')) {
                    this.addListener(td, 'click', {td: td}, function(ev) {
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

                if (col.autopopulate && typeof textareasByColId[col.autopopulate] !== 'undefined' && !textareasByColId[colId].val()) {
                    new Craft.HandleGenerator(textareasByColId[colId], textareasByColId[col.autopopulate], {
                        allowNonAlphaStart: true
                    });
                }
            }

            var $deleteBtn = this.$tr.children().last().find('.delete');
            this.addListener($deleteBtn, 'click', 'deleteRow');

            var $inputs = this.$tr.find('input,textarea,select,.lightswitch');
            this.addListener($inputs, 'focus', function(ev) {
                $(ev.currentTarget).closest('td:not(.disabled)').addClass('focus');
            });
            this.addListener($inputs, 'blur', function(ev) {
                $(ev.currentTarget).closest('td').removeClass('focus');
            });
        },

        onTextareaFocus: function(ev) {
            this.onTextareaHeightChange();

            var $textarea = $(ev.currentTarget);

            if ($textarea.data('ignoreNextFocus')) {
                $textarea.data('ignoreNextFocus', false);
                return;
            }

            setTimeout(function() {
                Craft.selectFullValue($textarea);
            }, 0);
        },

        onRadioCheckboxChange: function(ev) {
            if (ev.currentTarget.checked) {
                for (var i = 0; i < this.table.radioCheckboxes[ev.data.colId].length; i++) {
                    var checkbox = this.table.radioCheckboxes[ev.data.colId][i];
                    checkbox.checked = (checkbox === ev.currentTarget);
                }
            }
        },

        applyToggleCheckbox: function(checkboxColId) {
            var checkboxCol = this.table.columns[checkboxColId];
            var checked = $('input[type="checkbox"]', this.tds[checkboxColId]).prop('checked');
            var colId, colIndex, neg;
            for (var i = 0; i < checkboxCol.toggle.length; i++) {
                colId = checkboxCol.toggle[i];
                colIndex = this.table.colum;
                neg = colId[0] === '!';
                if (neg) {
                    colId = colId.substr(1);
                }
                if ((checked && !neg) || (!checked && neg)) {
                    $(this.tds[colId])
                        .removeClass('disabled')
                        .find('textarea, input').prop('disabled', false);
                } else {
                    $(this.tds[colId])
                        .addClass('disabled')
                        .find('textarea, input').prop('disabled', true);
                }
            }
        },

        ignoreNextTextareaFocus: function(ev) {
            $.data(ev.currentTarget, 'ignoreNextFocus', true);
        },

        handleKeypress: function(ev) {
            var keyCode = ev.keyCode ? ev.keyCode : ev.charCode;
            var ctrl = Garnish.isCtrlKeyPressed(ev);

            // Going to the next/previous row?
            if (keyCode === Garnish.RETURN_KEY && (ev.data.type !== 'multiline' || ctrl)) {
                ev.preventDefault();
                if (ev.shiftKey) {
                    this.table.focusOnPrevRow(this.$tr, ev.data.tdIndex, ev.currentTarget);
                } else {
                    this.table.focusOnNextRow(this.$tr, ev.data.tdIndex, ev.currentTarget);
                }
                return;
            }

            // Was this an invalid number character?
            if (ev.data.type === 'number' && !ctrl && !Craft.inArray(keyCode, Craft.EditableTable.Row.numericKeyCodes)) {
                ev.preventDefault();
            }
        },

        handlePaste: function(ev) {
            let data = Craft.trim(ev.originalEvent.clipboardData.getData('Text'), ' \n\r');
            if (!data.match(/[\t\r\n]/)) {
                return;
            }
            ev.preventDefault();
            this.table.importData(data, this, ev.data.tdIndex);
        },

        validateValue: function(ev) {
            if (ev.data.type === 'multiline') {
                return;
            }

            var safeValue;

            if (ev.data.type === 'number') {
                // Only grab the number at the beginning of the value (if any)
                var match = ev.currentTarget.value.match(/^\s*(-?[\d\\.]*)/);

                if (match !== null) {
                    safeValue = match[1];
                } else {
                    safeValue = '';
                }
            } else {
                // Just strip any newlines
                safeValue = ev.currentTarget.value.replace(/[\r\n]/g, '');
            }

            if (safeValue !== ev.currentTarget.value) {
                ev.currentTarget.value = safeValue;
            }
        },

        onTextareaHeightChange: function() {
            // Keep all the textareas' heights in sync
            var tallestTextareaHeight = -1;

            for (var i = 0; i < this.niceTexts.length; i++) {
                if (this.niceTexts[i].height > tallestTextareaHeight) {
                    tallestTextareaHeight = this.niceTexts[i].height;
                }
            }

            this.$textareas.css('min-height', tallestTextareaHeight);

            // If the <td> is still taller, go with that instead
            var tdHeight = this.$textareas.filter(':visible').first().parent().height();

            if (tdHeight > tallestTextareaHeight) {
                this.$textareas.css('min-height', tdHeight);
            }
        },

        deleteRow: function() {
            this.table.deleteRow(this);
        }
    },
    {
        numericKeyCodes: [9 /* (tab) */, 8 /* (delete) */, 37, 38, 39, 40 /* (arrows) */, 45, 91 /* (minus) */, 46, 190 /* period */, 48, 49, 50, 51, 52, 53, 54, 55, 56, 57 /* (0-9) */]
    });
