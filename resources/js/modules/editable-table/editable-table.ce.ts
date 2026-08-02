import {getPostData} from '@craftcms/garnish';
import {ControllerElement} from '@/common/web-components';
import {EditableTable} from './editable-table';
import type {
  EditableTableColumn,
  EditableTableColumns,
  EditableTableSettings,
} from './types';

type RowValue = Record<string, any>;

const nestedColumns: EditableTableColumns = {
  label: {
    type: 'singleline',
    heading: 'Option Label',
    autopopulate: 'value',
  },
  value: {type: 'singleline', heading: 'Value', code: true},
  default: {
    type: 'checkbox',
    heading: 'Default?',
    radioMode: true,
  },
};

let nextNestedTableId = 0;

export default class CraftEditableTable extends ControllerElement<EditableTable> {
  protected readonly rootSelector = 'table';
  private nestedTables = new Map<HTMLElement, EditableTable>();

  get keyed(): boolean {
    return this.hasAttribute('keyed');
  }

  protected create(table: HTMLTableElement): EditableTable {
    const settings = this.settings;

    if (settings.staticRows) {
      return {
        baseName: this.getAttribute('name'),
        destroy() {},
      } as EditableTable;
    }

    return new EditableTable(
      table.id,
      this.getAttribute('name') ?? '',
      this.columns,
      {
        ...settings,
        onAddRow: ($row: any) => this.attachNestedTable($row[0], {}),
        onDeleteRow: ($row: any) => this.destroyNestedTable($row[0]),
      }
    );
  }

  protected override booted(): void {
    const values = this.settings.values ?? [];

    this.querySelectorAll<HTMLElement>(
      ':scope table > tbody > tr[data-id]'
    ).forEach((row) => {
      const key = row.dataset.id!;
      const value = Array.isArray(values) ? values[Number(key)] : values[key];

      this.attachNestedTable(row, value ?? {});
    });
    (window as any).Craft?.initUiElements?.(this);
  }

  serialize(): RowValue[] | Record<string, RowValue> {
    const table = this.querySelector<HTMLTableElement>('table');

    if (!table) {
      return this.keyed ? {} : [];
    }

    const rows = this.valueAtName(
      (window as any).Craft?.expandPostArray?.(getPostData(table)) ?? {}
    );
    const orderedRows = Array.from(
      table.querySelectorAll<HTMLElement>(':scope > tbody > tr[data-id]')
    ).map((row) => {
      const key = row.dataset.id!;
      const value = this.normalizeRow(rows[key] ?? {});

      return {key, value};
    });

    return this.keyed
      ? Object.fromEntries(orderedRows.map(({key, value}) => [key, value]))
      : orderedRows.map(({value}) => value);
  }

  setName(name: string): void {
    const previousName = this.getAttribute('name') ?? '';

    if (name === previousName) {
      return;
    }

    this.querySelectorAll<
      HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement
    >('[name]').forEach((input) => {
      if (
        input.name === previousName ||
        input.name.startsWith(`${previousName}[`)
      ) {
        input.name = `${name}${input.name.slice(previousName.length)}`;
      }
    });
    this.setAttribute('name', name);

    if (this.instance) {
      this.instance.baseName = name;
    }
    this.nestedTables.forEach((table, row) => {
      table.baseName = `${name}[${row.dataset.id}][options]`;
    });
  }

  setColumns(columns: EditableTableColumns): void {
    const value = this.serialize();
    const rows = Array.isArray(value)
      ? value.map((row, index) => ({key: String(index), row}))
      : Object.entries(value).map(([key, row]) => ({key, row}));

    this.setAttribute('cols', JSON.stringify(columns));
    this.nestedTables.forEach((table) => table.destroy());
    this.nestedTables.clear();
    this.restart(() => {
      const table = this.querySelector<HTMLTableElement>('table')!;
      const header = table.tHead?.rows[0] ?? table.createTHead().insertRow();
      const body = table.tBodies[0]!;

      const headings = Object.values(columns).map((column) => {
        const cell = document.createElement('th');
        cell.scope = 'col';
        cell.textContent = column.heading ?? '';

        return cell;
      });

      const actionCount =
        Number(this.settings.allowDelete) + Number(this.settings.allowReorder);

      if (actionCount) {
        headings.push(this.actionHeading(actionCount));
      }

      if (this.settings.includeRowId) {
        const rowIdHeading = document.createElement('th');
        rowIdHeading.className = 'hidden';
        rowIdHeading.textContent = 'Row ID';
        headings.push(rowIdHeading);
      }

      header.replaceChildren(...headings);
      body.replaceChildren(
        ...rows.map(
          ({key, row}) =>
            EditableTable.createRow(
              key,
              columns,
              this.getAttribute('name') ?? '',
              row,
              this.settings.allowReorder,
              this.settings.allowDelete,
              false,
              this.settings.includeRowId
            )[0]
        )
      );
    });
  }

  private get columns(): EditableTableColumns {
    return this.jsonAttr('cols') as EditableTableColumns;
  }

  private get settings(): Partial<EditableTableSettings> {
    return this.jsonAttr('settings');
  }

  private valueAtName(value: Record<string, any>): Record<string, RowValue> {
    const path = (this.getAttribute('name') ?? '')
      .replaceAll(']', '')
      .split('[')
      .filter(Boolean);

    return (
      path.reduce<any>((current, segment) => current?.[segment], value) ?? {}
    );
  }

  private normalizeRow(row: RowValue): RowValue {
    const value = {...row};

    for (const [key, column] of Object.entries(this.columns)) {
      if (column.type === 'heading') {
        delete value[key];
      } else if (column.type === 'checkbox' || column.type === 'lightswitch') {
        value[key] = Boolean(value[key]);
      } else if (column.type === 'select') {
        const option = Object.values(column.options ?? {}).find(
          ({value: optionValue}: any) =>
            String(optionValue ?? '') === String(value[key] ?? '')
        ) as {value?: unknown} | undefined;

        value[key] = option?.value ?? value[key] ?? null;
      }

      if (
        column.nestedOptions &&
        value.options &&
        !Array.isArray(value.options)
      ) {
        value.options = Object.values(value.options);
      }
    }

    return value;
  }

  private actionHeading(colSpan: number): HTMLTableCellElement {
    const heading = document.createElement('th');
    const label = document.createElement('span');

    heading.scope = 'colgroup';
    heading.colSpan = colSpan;
    label.className = 'sr-only';
    label.textContent =
      (window as any).Craft?.t?.('app', 'Row actions') ?? 'Row actions';
    heading.append(label);

    return heading;
  }

  private attachNestedTable(row: HTMLElement, rowValue: RowValue): void {
    const nestedColumnIndex = Object.values(this.columns).findIndex(
      (column) => column.nestedOptions
    );

    if (nestedColumnIndex === -1 || this.nestedTables.has(row)) {
      return;
    }

    const cell = row.children[nestedColumnIndex] as HTMLTableCellElement;
    const container = document.createElement('div');
    const table = document.createElement('table');
    const header = table.createTHead().insertRow();
    const body = table.createTBody();
    const baseName = `${this.getAttribute('name') ?? ''}[${row.dataset.id}][options]`;
    const staticRows = this.settings.staticRows ?? false;

    container.className = 'input';
    container.dataset.tableNestedOptions = '';
    container.hidden = this.rowType(row, rowValue) !== 'select';
    table.id = `editable-table-options-${nextNestedTableId++}`;
    table.className = 'editable cp-table cp-table--editable w-full';
    Object.values(nestedColumns).forEach((column) => {
      const heading = document.createElement('th');
      heading.scope = 'col';
      heading.textContent =
        (window as any).Craft?.t?.('app', column.heading) ?? column.heading;
      header.append(heading);
    });
    if (!staticRows) {
      header.append(this.actionHeading(2));
    }
    const values = Array.isArray(rowValue.options) ? rowValue.options : [];

    values.forEach((value, index) => {
      body.append(
        EditableTable.createRow(
          String(index),
          nestedColumns,
          baseName,
          value,
          !staticRows,
          !staticRows,
          staticRows
        )[0]
      );
    });
    container.append(table);

    if (staticRows) {
      container
        .querySelectorAll<
          HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement
        >('input, select, textarea')
        .forEach((input) => {
          input.disabled = true;
        });
    }

    if (!staticRows) {
      const button = document.createElement('craft-button');
      button.setAttribute('type', 'button');
      button.setAttribute('command', '--add-row');
      button.textContent =
        (window as any).Craft?.t?.('app', 'Add an option') ?? 'Add an option';
      container.append(button);
    }

    cell.append(container);

    if (staticRows) {
      return;
    }

    const nestedTable = new EditableTable(table.id, baseName, nestedColumns, {
      allowAdd: !staticRows,
      allowDelete: !staticRows,
      allowReorder: !staticRows,
      staticRows,
    });
    nestedTable.on('*', (event: {type: string}) => {
      this.dispatchEvent(
        new CustomEvent(event.type, {bubbles: true, detail: event})
      );
    });
    this.nestedTables.set(row, nestedTable);

    row.querySelector('select')?.addEventListener('change', () => {
      container.hidden = this.rowType(row) !== 'select';
    });
  }

  private destroyNestedTable(row: HTMLElement): void {
    this.nestedTables.get(row)?.destroy();
    this.nestedTables.delete(row);
  }

  private rowType(row: HTMLElement, fallback: RowValue = {}): string {
    return (
      row.querySelector<HTMLSelectElement>('select')?.value ??
      String(fallback.type ?? '')
    );
  }

  override disconnectedCallback(): void {
    this.nestedTables.forEach((table) => table.destroy());
    this.nestedTables.clear();
    super.disconnectedCallback();
  }
}
