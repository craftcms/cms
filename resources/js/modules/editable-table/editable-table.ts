import {Base} from '@craftcms/garnish';
import type CraftCombobox from '@craftcms/ui/components/combobox/combobox';
import type CraftTextExpander from '@craftcms/ui/components/text-expander/text-expander';
import '@craftcms/ui/components/text-expander/text-expander';
import {editableTableData, editableTableRowData} from './support';
import type {
  EditableTableColumn,
  EditableTableColumns,
  EditableTableOption,
  EditableTableOptions,
  EditableTableRow,
  EditableTableValue,
  EditableTableSettings,
} from './types';
import {type ReorderDirection} from '@craftcms/ui';

// `Craft`, `$` (jQuery), and `Garnish` remain page globals. This class extends the
// modern `@craftcms/garnish` `Base` but still orchestrates jQuery Craft/Garnish
// widgets, so jQuery survives at those seams and the public `$`-prefixed properties
// stay jQuery for external legacy consumers (e.g. `TableFieldSettings`).
declare const Craft: any;
declare const Garnish: any;
declare const $: any;

const noop = (): void => {};

function defaultOptionValue(
  options: EditableTableOptions | EditableTableOption[] | undefined
): EditableTableValue | null {
  if (Array.isArray(options)) {
    return options.find((option) => option.default)?.value ?? null;
  }
  for (const [key, option] of Object.entries(options ?? {})) {
    if (option.default) {
      return option.value ?? key;
    }
  }
  return null;
}

/** Column types rendered as text inputs, with row-nav, paste-import, and validation. */
const TEXTUAL_COL_TYPES = [
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
] as const;

/**
 * Editable table — a port of the legacy jQuery `Craft.EditableTable` onto
 * `@craftcms/garnish` `Base`.
 *
 * Setup lives in {@link init}, and the constructor only runs it for the **leaf**
 * class (`new.target` guard). This lets the compat layer wrap the class so legacy
 * `.extend({ init() { … this.base(…) } })` subclasses keep working — their `init`
 * reshapes the args, and the compat trampoline (not our constructor) invokes it.
 * Modern ES subclasses call `this.init(...)` from their own leaf constructor.
 */
export class EditableTable extends Base<EditableTableSettings> {
  static readonly textualColTypes = TEXTUAL_COL_TYPES;

  static defaults: EditableTableSettings = {
    rowIdPrefix: '',
    defaultValues: {},
    allowAdd: false,
    allowReorder: false,
    allowDelete: false,
    minRows: null,
    maxRows: null,
    lazyInitRows: true,
    onAddRow: noop,
    onDeleteRow: noop,
    staticRows: false,
    includeRowId: false,
    maxRowId: null,
  };

  initialized = false;

  id: string | null = null;
  baseName: string | null = null;
  columns: EditableTableColumns | null = null;
  sorter: any = null;
  biggestId = -1;

  $table: any = null;
  $tbody: any = null;
  $addRowBtn: any = null;
  $tableParent: any = null;
  $statusMessage: any = null;

  rowCount = 0;
  hasMaxRows = false;
  hasMinRows = false;

  radioCheckboxes: Record<string, HTMLInputElement[]> = {};

  constructor(
    id?: string,
    baseName?: string,
    columns?: EditableTableColumns,
    settings?: Partial<EditableTableSettings>
  ) {
    super();
    if (new.target === EditableTable) {
      this.init(id!, baseName!, columns!, settings);
    }
  }

  init(
    id: string,
    baseName: string,
    columns: EditableTableColumns,
    settings?: Partial<EditableTableSettings>
  ): void {
    this.id = id;
    this.baseName = baseName;
    this.columns = columns;
    this.setSettings(settings, EditableTable.defaults);
    this.radioCheckboxes = {};

    this.$table = $('#' + id);
    this.$tbody = this.$table.children('tbody');
    this.$tableParent = this.$table.parent();
    this.$statusMessage = this.$tableParent.find('[data-status-message]');
    const $rows = this.$tbody.children();
    this.rowCount = $rows.length;

    // Is this already an editable table?
    const tableEl: Element | undefined = this.$table[0];
    if (tableEl && editableTableData.get(tableEl)) {
      console.warn('Double-instantiating an editable table on an element');
      editableTableData.get(tableEl)!.destroy();
    }

    if (tableEl) {
      editableTableData.set(tableEl, this);
    }

    if (Craft.hasMousePointerEvents()) {
      this.sorter = new Craft.DataTableSorter(this.$table, {
        handle: 'craft-reorder-button',
        helperClass: 'editabletablesorthelper',
        copyDraggeeInputValuesToHelper: true,
        onSortChange: () => {
          this.updateAllRows();
        },
      });
    } else {
      $rows.find('.move').hide();
    }

    for (let i = 0; i < $rows.length; i++) {
      const $row = $rows.eq(i);
      const rowId = parseInt(
        $row.attr('data-id').substring(this.settings!.rowIdPrefix.length)
      );
      if (rowId > this.biggestId) {
        this.biggestId = rowId;
      }
    }

    if (this.isVisible()) {
      this.initialize();
    } else {
      // Give everything a chance to initialize
      window.setTimeout(this.initializeIfVisible.bind(this), 500);
    }

    if (this.settings!.minRows && this.rowCount < this.settings!.minRows) {
      for (let i = this.rowCount; i < this.settings!.minRows; i++) {
        this.addRow();
      }
    }
  }

  isVisible(): boolean {
    return this.$table.parent().height() > 0;
  }

  initialize(): boolean {
    if (this.initialized) {
      return false;
    }

    this.initialized = true;
    this.removeListener(window, 'resize');

    const $container = this.$table.closest('.input, craft-field');
    if ($container.length && this.$table.width() > $container.width()) {
      $container.css('overflow-x', 'auto');
    }

    this.$addRowBtn = $container.find('[command="--add-row"]');
    this.updateAddRowButton();

    // If there's only one row, disable the action button
    const $actionButtons = this.$tbody.find('.action-btn');
    if (this.rowCount === 1) {
      $actionButtons.attr('disabled', 'disabled').addClass('disabled');
    }

    this.addListener(this.$addRowBtn, 'activate', 'addRow');

    // don't allow lazyInitRows if any of the columns are radio checkboxes
    this.settings!.lazyInitRows =
      this.settings!.lazyInitRows &&
      !Object.entries(this.columns!).some(
        ([, col]) => col.type === 'checkbox' && col.radioMode
      );

    if (this.settings!.lazyInitRows) {
      // Lazily create the row objects
      this.addListener(
        this.$tbody.add($actionButtons),
        'keypress,keyup,change,focus,blur,click,mousedown,mouseup',
        (ev: any) => {
          const $target = $(ev.target);
          const $tr = $target.closest('tr');
          if ($tr.length && !editableTableRowData.get($tr[0])) {
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
  }

  initializeIfVisible(): void {
    this.removeListener(window, 'resize');

    if (this.isVisible()) {
      this.initialize();
    } else {
      this.addListener(window, 'resize', 'initializeIfVisible');
    }
  }

  updateAddRowButton(): void {
    if (!this.canAddRow()) {
      this.$addRowBtn.css('opacity', '0.2');
      this.$addRowBtn.css('pointer-events', 'none');
      this.$addRowBtn.attr('aria-disabled', 'true');
    } else {
      this.$addRowBtn.css('opacity', '1');
      this.$addRowBtn.css('pointer-events', 'auto');
      this.$addRowBtn.attr('aria-disabled', 'false');
    }
  }

  updateAllRows(): void {
    if (this.settings!.staticRows) {
      return;
    }
    const $rows = this.$table.find('> tbody > tr');
    for (let i = 0; i < $rows.length; i++) {
      this.updateRow($rows.eq(i));
    }
  }

  updateRow($row: any): void {
    if (this.settings!.staticRows) {
      return;
    }

    const $deleteBtn = $row
      .children('td.action')
      .find('[command="--delete-row"]');
    const $actionsBtn = $row.children('td.action').find('.action-btn');

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
      } else {
        $actionsBtn.removeAttr('disabled').removeClass('disabled');
      }
    }
  }

  /**
   * @deprecated Use {@link updateRow} with the row's `<tr>` instead.
   */
  updateDeleteRowButton(rowId: string): void {
    this.updateRow(this.$table.find(`tr[data-id="${rowId}"]`));
  }

  updateStatusMessage(): void {
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
  }

  canDeleteRow(): boolean {
    if (!this.settings!.allowDelete) {
      return false;
    }

    return this.rowCount > (this.settings!.minRows ?? 0);
  }

  deleteRow(row: Row): void {
    if (!this.canDeleteRow()) {
      this.updateStatusMessage();
      return;
    }

    this.sorter?.removeItems(row.$tr);
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
    this.settings!.onDeleteRow(row.$tr);
    this.trigger('deleteRow', {$tr: row.$tr});

    row.destroy();
  }

  canAddRow(): boolean {
    if (!this.settings!.allowAdd) {
      return false;
    }

    if (this.settings!.maxRows) {
      return this.rowCount < this.settings!.maxRows;
    }

    return true;
  }

  addRow(focus?: boolean, prepend?: boolean): Row | undefined {
    if (!this.canAddRow()) {
      this.updateStatusMessage();
      return;
    }

    const rowId = this.settings!.rowIdPrefix + (this.biggestId + 1);
    const $tr = this.createRow(
      rowId,
      this.columns!,
      this.baseName!,
      Object.assign({}, this.settings!.defaultValues)
    );

    if (prepend) {
      $tr.prependTo(this.$tbody);
    } else {
      $tr.appendTo(this.$tbody);
    }

    const row = this.createRowObj($tr);
    this.sorter?.addItems($tr);

    // Focus the first input in the row
    if (focus !== false) {
      $tr.find('input:visible,textarea:visible,select:visible').first().focus();
    }

    this.rowCount++;
    this.updateAllRows();
    this.updateAddRowButton();
    this.$table.removeClass('hidden');

    // onAddRow callback
    this.settings!.onAddRow($tr);
    this.trigger('addRow', {$tr});

    return row;
  }

  createRow(
    rowId: string,
    columns: EditableTableColumns,
    baseName: string,
    values: EditableTableRow
  ): any {
    return EditableTable.createRow(
      rowId,
      columns,
      baseName,
      values,
      this.settings!.allowReorder,
      this.settings!.allowDelete,
      this.settings!.staticRows,
      this.settings!.includeRowId
    );
  }

  getRowObj($tr: any): Row {
    return editableTableRowData.get($($tr)[0]) || this.createRowObj($tr);
  }

  createRowObj($tr: any): Row {
    return new Row(this, $tr);
  }

  focusOnPrevRow($tr: any, tdIndex: number, blurTd: HTMLElement): void {
    const $prevTr = $tr.prev('tr');
    let prevRow: Row | undefined;

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
      if ($prevTr.length) {
        this.focusOnPrevRow($prevTr, tdIndex, blurTd);
      }
      return;
    }

    const $input = $('textarea,input.text', prevRow.$tds[tdIndex]);
    if ($input.length) {
      blurTd?.dispatchEvent(new Event('blur'));
      $input.focus();
    }
  }

  focusOnNextRow($tr: any, tdIndex: number, blurTd: HTMLElement): void {
    const $nextTr = $tr.next('tr');
    let nextRow: Row | undefined;

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
      if ($nextTr.length) {
        this.focusOnNextRow($nextTr, tdIndex, blurTd);
      }
      return;
    }

    const $input = $('textarea,input.text', nextRow.$tds[tdIndex]);
    if ($input.length) {
      blurTd?.dispatchEvent(new Event('blur'));
      $input.focus();
    }
  }

  importData(data: string, row: Row, tdIndex: number): void {
    const lines = data.split(/\r?\n|\r/);
    for (let i = 0; i < lines.length; i++) {
      const values = lines[i]!.split('\t');
      for (let j = 0; j < values.length; j++) {
        const value = values[j];
        const $inputs = row.$tds
          .eq(tdIndex + j)
          .find('textarea,input[type!=hidden]');
        $inputs.val(value);
        // Base binds listeners natively, so a jQuery `.trigger` won't reach them.
        $inputs.each((_: number, el: HTMLElement) => {
          el.dispatchEvent(new Event('input', {bubbles: true}));
        });
      }

      // move onto the next row
      const $nextTr = row.$tr.next('tr');
      if ($nextTr.length) {
        row = this.getRowObj($nextTr);
      } else {
        const nextRow = this.addRow(false);
        if (!nextRow) {
          break;
        }
        row = nextRow;
      }
    }
  }

  override destroy(): void {
    const tableEl: Element | undefined = this.$table?.[0];
    if (tableEl) {
      editableTableData.delete(tableEl);
    }
    super.destroy();
  }

  /**
   * Build a row `<tr>`. Kept as jQuery / `Craft.ui.*` assembly and returns a jQuery
   * `<tr>` — the legacy contract external callers like `TableFieldSettings` rely on.
   */
  static createRow(
    rowId: string,
    columns: EditableTableColumns,
    baseName: string,
    values: EditableTableRow,
    allowReorder?: boolean,
    allowDelete?: boolean,
    staticRows = false,
    includeRowId = false
  ): any {
    void staticRows;

    const $tr = $('<tr/>', {
      'data-id': rowId,
    });

    for (const colId in columns) {
      if (!Object.prototype.hasOwnProperty.call(columns, colId)) {
        continue;
      }

      const col: EditableTableColumn = columns[colId]!;
      const value = values[colId] === undefined ? '' : values[colId];
      let $cell;

      if (col.type === 'heading') {
        $cell = $('<th/>', {
          scope: 'row',
          class: col['class'],
          html: value,
        });
      } else {
        let name = `${baseName}[${rowId}][${colId}]`;

        $cell = $('<td/>', {
          class: `${col.class ?? ''} ${col.type}-cell`,
          width: col.width,
        });

        if (Craft.inArray(col.type, EditableTable.textualColTypes)) {
          $cell.addClass('textual');
        }

        if (col.code) {
          $cell.addClass('code');
        }

        let textExpanderTarget: HTMLInputElement | HTMLTextAreaElement | null =
          null;

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

          case 'icon':
            Craft.ui
              .createIconPicker({
                name: name,
                value: value instanceof Object ? null : value,
                small: true,
              })
              .appendTo($cell);
            break;

          case 'color':
            Craft.ui
              .createColorInput({
                name: name,
                value: value instanceof Object ? null : value,
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
                value: value || defaultOptionValue(col.options),
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
          case 'url': {
            const $input = Craft.ui
              .createTextInput({
                name: name,
                value: value instanceof Object ? null : value,
                type: col.type,
                placeholder: col.placeholder || null,
              })
              .appendTo($cell);
            textExpanderTarget = $input[0];
            break;
          }

          case 'autosuggest':
          case 'template': {
            if (col.textExpanderTriggers) {
              const $input = Craft.ui
                .createTextInput({
                  name,
                  value: value instanceof Object ? null : value,
                  placeholder: col.placeholder || null,
                })
                .appendTo($cell);
              textExpanderTarget = $input[0];
              break;
            }

            const combobox = document.createElement(
              'craft-combobox'
            ) as CraftCombobox;
            combobox.name = name;
            combobox.label = col.heading ?? colId;
            combobox.options = Array.isArray(col.options)
              ? col.options.map((option) => ({
                  label: option.label ?? String(option.value ?? ''),
                  value: String(option.value ?? ''),
                }))
              : [];
            combobox.modelValue = String(value ?? '');
            combobox.showAllOnEmpty = true;
            combobox.setAttribute('label-sr-only', '');
            $cell.append(combobox);
            break;
          }

          default:
            if (col.type === 'number' && col.locale) {
              $('<input/>', {
                type: 'hidden',
                name: `${name}[locale]`,
                value: col.locale,
              }).appendTo($cell);
              name = `${name}[value]`;
            }

            // oxlint-disable-next-line no-case-declarations
            const $textarea = $('<textarea/>', {
              name: name,
              rows: col.rows || 1,
              val: value instanceof Object ? null : value,
              placeholder: col.placeholder,
            }).appendTo($cell);
            textExpanderTarget = $textarea[0];

            if (col.code) {
              $textarea.attr({
                autocomplete: 'off',
                autocorrect: 'off',
                autocapitalize: 'off',
                spellcheck: 'false',
              });
            }
        }

        if (col.textExpanderTriggers && textExpanderTarget) {
          textExpanderTarget.id = `editable-table-input-${crypto.randomUUID()}`;
          textExpanderTarget.setAttribute('aria-label', col.heading ?? colId);
          const expander = document.createElement(
            'craft-text-expander'
          ) as CraftTextExpander;
          expander.for = textExpanderTarget.id;
          expander.triggers = col.textExpanderTriggers;
          $cell.append(expander);
        }
      }

      $cell.appendTo($tr);
    }

    if (allowReorder) {
      const $td = $('<td/>', {class: 'thin action'}).appendTo($tr);
      const $div = $('<div/>', {
        class: 'flex gap-2 items-center justify-end self-end',
      });

      if (Craft.hasMousePointerEvents()) {
        $div.append(document.createElement('craft-reorder-button'));
      }

      $div.appendTo($td);
    }

    if (allowDelete) {
      $('<td/>', {
        class: 'thin action',
      })
        .append(
          $('<craft-button/>', {
            type: 'button',
            icon: 'x',
            size: 'small',
            variant: 'danger-plain',
            'aria-label': Craft.t('app', 'Delete'),
            command: '--delete-row',
          })
        )
        .appendTo($tr);
    }

    if (includeRowId) {
      $('<input/>', {
        type: 'hidden',
        name: `${baseName}[${rowId}][rowId]`,
        value: values.rowId ?? Craft.uuid(),
      }).appendTo($tr);
    }

    return $tr;
  }
}

/**
 * A single row within an {@link EditableTable}; port of `Craft.EditableTable.Row`.
 * Uses the same `new.target` construction contract as {@link EditableTable} so the
 * compat layer can wrap it for legacy `.extend()` subclasses.
 */
export class Row extends Base {
  table: EditableTable;
  id: string | null = null;
  niceTexts: any[] = [];

  $tr: any = null;
  $tds: any = null;
  tds: Record<string, HTMLElement> = {};
  $textareas: any = null;
  $deleteBtn: any = null;

  actionDisclosure: any = null;
  $actionMenu: any = null;
  $actionMenuOptions: any = null;

  get prevRow(): any {
    return this.$tr.prev('tr');
  }

  get nextRow(): any {
    return this.$tr.next('tr');
  }

  constructor(table?: EditableTable, tr?: any) {
    super();
    // Assigned here (not just in init) so TS sees it as definitely assigned.
    this.table = table!;
    if (new.target === Row) {
      this.init(table!, tr);
    }
  }

  init(table: EditableTable, tr: any): void {
    this.table = table;
    this.$tr = $(tr);
    this.$tds = this.$tr.children();
    this.tds = {};
    this.id = this.$tr.attr('data-id');

    editableTableRowData.set(this.$tr[0], this);

    if (!Craft.hasMousePointerEvents()) {
      this.$tr.find('.move').hide();
    }

    // Get the row ID, sans prefix
    const id = parseInt(
      this.id!.substring(this.table.settings!.rowIdPrefix.length)
    );

    if (id > this.table.biggestId) {
      this.table.biggestId = id;
    }

    this.$textareas = $();
    this.niceTexts = [];
    const textInputsByColId: Record<string, JQuery> = {};

    let i = 0;
    let colId: string;
    let col: EditableTableColumn;
    let td: HTMLElement;
    let $checkbox: any;

    for (colId in this.table.columns!) {
      if (!Object.prototype.hasOwnProperty.call(this.table.columns, colId)) {
        continue;
      }

      col = this.table.columns![colId]!;
      td = this.tds[colId] = this.$tds[i];

      if (Craft.inArray(col.type, EditableTable.textualColTypes)) {
        $('.editable-table-preview', td).remove();
        let $input;
        if (col.type === 'color') {
          $input = $('input.color-input', td);
        } else {
          $input = $('textarea', td);
          this.$textareas = this.$textareas.add($input);
          if (Garnish.NiceText instanceof Function) {
            this.niceTexts.push(
              new Garnish.NiceText($input, {
                onHeightChange: this.onTextareaHeightChange.bind(this),
              })
            );
          }
        }

        this.addListener($input, 'focus', 'onTextareaFocus');
        this.addListener($input, 'mousedown', 'ignoreNextTextareaFocus');
        this.addListener(
          this.$tr.find('craft-reorder-button'),
          'reorder',
          'onReorder'
        );

        this.addListener(
          $input,
          'keypress',
          {tdIndex: i, type: col.type},
          'handleKeypress'
        );
        this.addListener($input, 'input', {type: col.type}, 'validateValue');
        $input.each((_: number, el: HTMLElement) => {
          el.dispatchEvent(new Event('input', {bubbles: true}));
        });

        if (col.type !== 'multiline') {
          this.addListener(
            $input,
            'paste',
            {tdIndex: i, type: col.type},
            'handlePaste'
          );
        }

        textInputsByColId[colId] = $input;
      } else if (col.type === 'checkbox') {
        $checkbox = $('input[type="checkbox"]', td);

        if (col.radioMode) {
          if (this.table.radioCheckboxes[colId] === undefined) {
            this.table.radioCheckboxes[colId] = [];
          }
          this.table.radioCheckboxes[colId]!.push($checkbox[0]);
          this.addListener(
            $checkbox,
            'change',
            {colId},
            'onRadioCheckboxChange'
          );
        }

        if (col.toggle) {
          this.addListener($checkbox, 'change', {colId}, (ev: any) => {
            this.applyToggleCheckbox(ev.data.colId);
          });
        }
      }

      if (!$(td).hasClass('disabled')) {
        this.addListener(td, 'click', {td}, (ev: any) => {
          if (ev.target === ev.data.td) {
            $(ev.data.td).find('textarea,input,select,.lightswitch').focus();
          }
        });
      }

      i++;
    }

    // Now that all of the text cells have been nice-ified, normalize the heights
    this.onTextareaHeightChange();

    // See if we need to apply any checkbox toggles now that all TDs are indexed
    for (colId in this.table.columns!) {
      if (!Object.prototype.hasOwnProperty.call(this.table.columns, colId)) {
        continue;
      }
      col = this.table.columns![colId]!;
      if (col.type === 'checkbox' && col.toggle) {
        this.applyToggleCheckbox(colId);
      }
    }

    // Now look for any autopopulate columns
    for (colId in this.table.columns!) {
      if (!Object.prototype.hasOwnProperty.call(this.table.columns, colId)) {
        continue;
      }

      col = this.table.columns![colId]!;
      const input = textInputsByColId[colId];
      const sourceInput = col.autopopulate
        ? textInputsByColId[col.autopopulate]
        : undefined;

      if (
        col.autopopulate &&
        input &&
        sourceInput &&
        !input.val() &&
        !sourceInput.val()
      ) {
        new Craft.HandleGenerator(input, sourceInput, {
          allowNonAlphaStart: true,
        });
      }
    }

    const $deleteBtn = this.$tr
      .children('td.action')
      .find('[command="--delete-row"]');
    this.addListener($deleteBtn, 'click', 'deleteRow');

    const $inputs = this.$tr.find('input,textarea,select,.lightswitch');
    this.addListener($inputs, 'focus', (ev: any) => {
      $(ev.currentTarget).closest('td:not(.disabled)').addClass('focus');
    });
    this.addListener($inputs, 'blur', (ev: any) => {
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

      this.$actionMenuOptions = this.$actionMenu.find('button[]');

      this.addListener(
        this.$actionMenuOptions,
        'activate',
        'handleActionClick'
      );
    }
  }

  updateDisclosureMenu(): void {
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
  }

  handleActionClick(event: Event): void {
    event.preventDefault();
    if (event.target instanceof HTMLElement) {
      this.onActionSelect(event.target);
    }
  }

  onReorder(event: CustomEvent<{direction: ReorderDirection}>): void {
    if (event.detail.direction === 'down') {
      this.moveDown();
    } else {
      this.moveUp();
    }
  }

  onActionSelect(option: HTMLElement): void {
    const $option = $(option);
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
  }

  moveUp(): void {
    const $prev = this.prevRow;
    if ($prev.length) {
      this.$tr.insertBefore($prev);
      this.table.updateAllRows();
    }
  }

  moveDown(): void {
    const $next = this.nextRow;
    if ($next.length) {
      this.$tr.insertAfter($next);
      this.table.updateAllRows();
    }
  }

  onTextareaFocus(ev: any): void {
    this.onTextareaHeightChange();

    const $textarea = $(ev.currentTarget);

    if ($textarea.data('ignoreNextFocus')) {
      $textarea.data('ignoreNextFocus', false);
      return;
    }

    window.setTimeout(function () {
      Craft.selectFullValue($textarea);
    }, 0);
  }

  onRadioCheckboxChange(ev: any): void {
    if (ev.currentTarget.checked) {
      const checkboxes = this.table.radioCheckboxes[ev.data.colId] ?? [];
      for (const checkbox of checkboxes) {
        checkbox.checked = checkbox === ev.currentTarget;
      }
    }
  }

  applyToggleCheckbox(checkboxColId: string): void {
    const checkboxCol = this.table.columns![checkboxColId]!;
    const checked = $('input[type="checkbox"]', this.tds[checkboxColId]).prop(
      'checked'
    );
    let neg: boolean;
    for (let colId of checkboxCol.toggle ?? []) {
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
  }

  ignoreNextTextareaFocus(ev: any): void {
    $.data(ev.currentTarget, 'ignoreNextFocus', true);
  }

  handleKeypress(ev: any): void {
    const keyCode = ev.keyCode ? ev.keyCode : ev.charCode;
    const ctrl = Garnish.isCtrlKeyPressed(ev);

    // Going to the next/previous row?
    if (
      keyCode === Garnish.RETURN_KEY &&
      (ev.data.type !== 'multiline' || ctrl)
    ) {
      ev.preventDefault();
      ev.stopPropagation();
      if (ev.shiftKey) {
        this.table.focusOnPrevRow(this.$tr, ev.data.tdIndex, ev.currentTarget);
      } else {
        this.table.focusOnNextRow(this.$tr, ev.data.tdIndex, ev.currentTarget);
      }
    }
  }

  handlePaste(ev: any): void {
    // Native listener, so the event is native — read `clipboardData` directly.
    const data = Craft.trim(ev.clipboardData.getData('Text'), ' \n\r');
    if (!data.match(/[\t\r\n]/)) {
      return;
    }
    ev.preventDefault();
    this.table.importData(data, this, ev.data.tdIndex);
  }

  validateValue(ev: any): void {
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
  }

  onTextareaHeightChange(): void {
    // Keep all the textareas' heights in sync
    let tallestTextareaHeight = -1;

    for (let i = 0; i < this.niceTexts.length; i++) {
      if (this.niceTexts[i].height > tallestTextareaHeight) {
        tallestTextareaHeight = this.niceTexts[i].height;
      }
    }

    this.$textareas.css('min-height', tallestTextareaHeight);

    // If the <td> is still taller, go with that instead
    const tdHeight = this.$textareas
      .filter(':visible')
      .first()
      .parent()
      .height();

    if (tdHeight > tallestTextareaHeight) {
      this.$textareas.css('min-height', tdHeight);
    }
  }

  deleteRow(): void {
    this.table.deleteRow(this);
  }

  override destroy(): void {
    const trEl: Element | undefined = this.$tr?.[0];
    if (trEl) {
      editableTableRowData.delete(trEl);
    }
    super.destroy();
  }
}
