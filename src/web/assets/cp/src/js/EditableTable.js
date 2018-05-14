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
            return (this.$table.height() > 0);
        },

        initialize: function() {
            if (this.initialized) {
                return;
            }

            this.initialized = true;
            this.removeListener(Garnish.$win, 'resize');

            var $rows = this.$tbody.children();

            for (var i = 0; i < $rows.length; i++) {
                new Craft.EditableTable.Row(this, $rows[i]);
            }

            this.$addRowBtn = this.$table.next('.add');
            this.updateAddRowButton();
            this.addListener(this.$addRowBtn, 'activate', 'addRow');
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
            // onDeleteRow callback
            this.settings.onDeleteRow(row.$tr);
        },
        canAddRow: function() {
            if (this.settings.maxRows) {
                return (this.rowCount < this.settings.maxRows);
            }

            return true;
        },
        addRow: function() {
            if (!this.canAddRow()) {
                return;
            }

            var rowId = this.settings.rowIdPrefix + (this.biggestId + 1),
                $tr = this.createRow(rowId, this.columns, this.baseName, $.extend({}, this.settings.defaultValues));

            $tr.appendTo(this.$tbody);
            new Craft.EditableTable.Row(this, $tr);
            this.sorter.addItems($tr);

            // Focus the first input in the row
            $tr.find('input,textarea,select').first().trigger('focus');

            this.rowCount++;
            this.updateAddRowButton();

            // onAddRow callback
            this.settings.onAddRow($tr);
        },

        createRow: function(rowId, columns, baseName, values) {
            return Craft.EditableTable.createRow(rowId, columns, baseName, values);
        }
    },
    {
        textualColTypes: ['color', 'date', 'multiline', 'number', 'singleline', 'time'],
        defaults: {
            rowIdPrefix: '',
            defaultValues: {},
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
                    var name = baseName + '[' + rowId + '][' + colId + ']',
                        textual = Craft.inArray(col.type, Craft.EditableTable.textualColTypes);

                    $cell = $('<td/>', {
                        'class': col['class'],
                        'width': col.width
                    });

                    if (textual) {
                        $cell.addClass('textual');
                    }

                    if (col.code) {
                        $cell.addClass('code');
                    }

                    switch (col.type) {
                        case 'checkbox':
                            Craft.ui.createCheckbox({
                                name: name,
                                value: col.value || '1',
                                checked: !!value
                            }).appendTo($cell);
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
                                value: value,
                                'class': 'small'
                            }).appendTo($cell);
                            break;

                        case 'time':
                            Craft.ui.createTimeInput({
                                name: name,
                                value: value
                            }).appendTo($cell);
                            break;

                        default:
                            $('<textarea/>', {
                                'name': name,
                                'rows': 1,
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
        $textareas: null,
        $deleteBtn: null,

        init: function(table, tr) {
            this.table = table;
            this.$tr = $(tr);
            this.$tds = this.$tr.children();

            // Get the row ID, sans prefix
            var id = parseInt(this.$tr.attr('data-id').substr(this.table.settings.rowIdPrefix.length));

            if (id > this.table.biggestId) {
                this.table.biggestId = id;
            }

            this.$textareas = $();
            this.niceTexts = [];
            var textareasByColId = {};

            var i = 0;
            var colId, col;

            for (colId in this.table.columns) {
                if (!this.table.columns.hasOwnProperty(colId)) {
                    continue;
                }

                col = this.table.columns[colId];

                if (Craft.inArray(col.type, Craft.EditableTable.textualColTypes)) {
                    var $textarea = $('textarea, input.text', this.$tds[i]);
                    this.$textareas = this.$textareas.add($textarea);

                    this.addListener($textarea, 'focus', 'onTextareaFocus');
                    this.addListener($textarea, 'mousedown', 'ignoreNextTextareaFocus');

                    this.niceTexts.push(new Garnish.NiceText($textarea, {
                        onHeightChange: $.proxy(this, 'onTextareaHeightChange')
                    }));

                    if (col.type === 'singleline' || col.type === 'number') {
                        this.addListener($textarea, 'keypress', {type: col.type}, 'validateKeypress');
                        this.addListener($textarea, 'textchange', {type: col.type}, 'validateValue');
                        $textarea.trigger('textchange');
                    }

                    textareasByColId[colId] = $textarea;
                } else if (col.type === 'checkbox' && col.radioMode) {
                    var $checkbox = $('input[type="checkbox"]', this.$tds[i]);
                    if (typeof this.table.radioCheckboxes[colId] === 'undefined') {
                        this.table.radioCheckboxes[colId] = [];
                    }
                    this.table.radioCheckboxes[colId].push($checkbox[0]);

                    this.addListener($checkbox, 'change', {colId: colId}, 'onRadioCheckboxChange');
                }

                i++;
            }

            // Now that all of the text cells have been nice-ified, let's normalize the heights
            this.onTextareaHeightChange();

            // Now look for any autopopulate columns
            for (colId in this.table.columns) {
                if (!this.table.columns.hasOwnProperty(colId)) {
                    continue;
                }

                col = this.table.columns[colId];

                if (col.autopopulate && typeof textareasByColId[col.autopopulate] !== 'undefined' && !textareasByColId[colId].val()) {
                    new Craft.HandleGenerator(textareasByColId[colId], textareasByColId[col.autopopulate]);
                }
            }

            var $deleteBtn = this.$tr.children().last().find('.delete');
            this.addListener($deleteBtn, 'click', 'deleteRow');
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

        ignoreNextTextareaFocus: function(ev) {
            $.data(ev.currentTarget, 'ignoreNextFocus', true);
        },

        validateKeypress: function(ev) {
            var keyCode = ev.keyCode ? ev.keyCode : ev.charCode;

            if (!Garnish.isCtrlKeyPressed(ev) && (
                    (keyCode === Garnish.RETURN_KEY) ||
                    (ev.data.type === 'number' && !Craft.inArray(keyCode, Craft.EditableTable.Row.numericKeyCodes))
                )) {
                ev.preventDefault();
            }
        },

        validateValue: function(ev) {
            var safeValue;

            if (ev.data.type === 'number') {
                // Only grab the number at the beginning of the value (if any)
                var match = ev.currentTarget.value.match(/^\s*(-?[\d\\.]*)/);

                if (match !== null) {
                    safeValue = match[1];
                }
                else {
                    safeValue = '';
                }
            }
            else {
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
