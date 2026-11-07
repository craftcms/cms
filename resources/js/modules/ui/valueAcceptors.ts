/**
 * Which values the built-in controls take from other controls of their kind,
 * decided from the value itself and the control's own settings: a value a
 * control couldn't have been given by hand, such as multi-line text for a
 * single-line input or an option it doesn't have, isn't pasted into it.
 *
 * Anything that depends on more than the value, such as the elements a
 * relation field can select, is settled by the controls' value scopes.
 */

import {moneyAmount} from './money';
import type {UiControlPayload, UiValue, UiValues} from './types';
import type {UiValueAcceptor} from './valueAccess';

type Props = Record<string, unknown>;
type Scalar = string | number;

function propsOf(control: UiControlPayload<object>): Props {
  return control.props as Props;
}

function isBlank(value: UiValue | undefined): boolean {
  return (
    value === null ||
    value === undefined ||
    value === '' ||
    (Array.isArray(value) && value.length === 0)
  );
}

function isRecord(value: UiValue | undefined): value is UiValues {
  return (
    typeof value === 'object' &&
    value !== null &&
    !Array.isArray(value) &&
    !(value instanceof File)
  );
}

function isScalar(value: UiValue | undefined): value is Scalar {
  return typeof value === 'string' || typeof value === 'number';
}

function isNumeric(value: UiValue | undefined): boolean {
  return (
    isScalar(value) &&
    String(value).trim() !== '' &&
    !Number.isNaN(Number(value))
  );
}

/**
 * Whether `text` fits in `maxLength` characters, counted in code points, as
 * PHP's `mb_strlen()` counts them.
 */
function fitsLength(text: string, maxLength: unknown): boolean {
  return typeof maxLength !== 'number' || Array.from(text).length <= maxLength;
}

/**
 * Whether `value` is between `min` and `max`: as numbers when they're
 * numbers, or numeric strings beside a numeric value, and as strings
 * otherwise, like dates and times are.
 */
function withinBounds(value: Scalar, min: unknown, max: unknown): boolean {
  return [min, max].every((bound, index) => {
    const numeric =
      typeof bound === 'number' ||
      (isNumeric(bound as UiValue) && isNumeric(value));
    const order = numeric
      ? Number(value) - Number(bound)
      : typeof bound === 'string' && bound !== ''
        ? String(value).localeCompare(bound)
        : null;

    return order === null || (index === 0 ? order >= 0 : order <= 0);
  });
}

function isSingleLine(text: string): boolean {
  return !/[\r\n]/.test(text);
}

/** Whether `text` has the format an input of `inputType` checks for. */
function fitsInputType(text: string, inputType: unknown): boolean {
  switch (inputType) {
    case 'email':
      return /^[^\s@]+@[^\s@]+$/.test(text);
    case 'url':
      return URL.canParse(text);
    default:
      return true;
  }
}

/** The values of a list of options, flattening any option groups. */
function optionValues(options: unknown): Set<string> {
  const list = Array.isArray(options)
    ? options
    : options && typeof options === 'object'
      ? Object.values(options)
      : [];

  return new Set(
    list.flatMap((option: {value?: unknown; options?: unknown}) =>
      option && typeof option === 'object'
        ? 'options' in option
          ? [...optionValues(option.options)]
          : option.value === undefined || option.value === null
            ? []
            : [String(option.value as Scalar)]
        : []
    )
  );
}

/**
 * Whether a choice has the shape a control holds: a list of values when it
 * takes several, and one value otherwise.
 */
function fitsShape(value: UiValue, multiple: unknown): boolean {
  return Array.isArray(value)
    ? Boolean(multiple) && value.every(isScalar)
    : !multiple && isScalar(value);
}

/** Whether a choice of `values` fits the options a control offers. */
function fitsOptions(value: UiValue, options: unknown): boolean {
  const offered = optionValues(options);

  return (Array.isArray(value) ? value : [value]).every(
    (item) => isScalar(item) && offered.has(String(item))
  );
}

/** Plain text, and the inputs built on it, such as numbers, dates and times. */
const text: UiValueAcceptor = (control, {value}) => {
  if (isBlank(value)) {
    return true;
  }

  const {maxLength, min, max, inputType} = propsOf(control);

  return (
    isScalar(value) &&
    isSingleLine(String(value)) &&
    fitsInputType(String(value), inputType) &&
    fitsLength(String(value), maxLength) &&
    withinBounds(value, min, max)
  );
};

const number: UiValueAcceptor = (control, snapshot) =>
  isBlank(snapshot.value) ||
  (isNumeric(snapshot.value) && text(control, snapshot));

const textarea: UiValueAcceptor = (control, {value}) => {
  const {maxLength, singleLine} = propsOf(control);

  return (
    isBlank(value) ||
    (typeof value === 'string' &&
      (!singleLine || isSingleLine(value)) &&
      fitsLength(value, maxLength))
  );
};

const markdown: UiValueAcceptor = (control, {value}) =>
  isBlank(value) ||
  (typeof value === 'string' && fitsLength(value, propsOf(control).maxLength));

const choice: UiValueAcceptor = (control, {value}) => {
  const {options, multiple, allValue, allowCustom} = propsOf(control);

  if (isBlank(value) || (multiple && allValue && value === allValue)) {
    return true;
  }

  // Options added by hand can be added here too.
  return (
    fitsShape(value, multiple) &&
    (Boolean(allowCustom) || fitsOptions(value, options))
  );
};

/**
 * Comboboxes' `limit` caps the options they list at once, not how many can
 * be chosen, so it doesn't apply here.
 */
const combobox: UiValueAcceptor = (control, {value}) => {
  const {options, multiple, requireOptionMatch} = propsOf(control);

  return (
    isBlank(value) ||
    (fitsShape(value, multiple) &&
      (!requireOptionMatch || fitsOptions(value, options)))
  );
};

/** Dates and times, checked against the control's earliest and latest dates. */
const dateTime: UiValueAcceptor = (control, {value}) => {
  if (isBlank(value)) {
    return true;
  }

  if (!isRecord(value)) {
    return false;
  }

  const {date} = value;
  const {min, max} = propsOf(control);

  return (
    isBlank(date) || (typeof date === 'string' && withinBounds(date, min, max))
  );
};

const colorSelect: UiValueAcceptor = (control, {value}) => {
  const {colors, allowTransparent} = propsOf(control);

  return (
    isBlank(value) ||
    (value === 'transparent' && Boolean(allowTransparent)) ||
    (typeof value === 'string' &&
      (!Array.isArray(colors) || colors.includes(value)))
  );
};

const color: UiValueAcceptor = (control, {value}) =>
  isBlank(value) ||
  (typeof value === 'string' &&
    // Without an alpha channel, a translucent color can't be entered.
    (Boolean(propsOf(control).alpha) ||
      !/^#?(?:[0-9a-f]{4}|[0-9a-f]{8})$/i.test(value)));

const elementSelect: UiValueAcceptor = (control, {value}) => {
  const {limit, single} = propsOf(control);
  const count = Array.isArray(value) ? value.length : isBlank(value) ? 0 : 1;

  return count <= (single ? 1 : typeof limit === 'number' ? limit : Infinity);
};

const link: UiValueAcceptor = (control, {value}) => {
  if (isBlank(value)) {
    return true;
  }

  if (!isRecord(value)) {
    return false;
  }

  if (isBlank(value.value)) {
    return true;
  }

  const {types} = propsOf(control);

  return (
    Array.isArray(types) &&
    types.some((type: {id?: unknown}) => type?.id === value.type)
  );
};

const money: UiValueAcceptor = (control, {value}) => {
  const amount = isRecord(value) ? value.value : value;

  if (isBlank(amount)) {
    return true;
  }

  // Amounts are formatted for the locale they were entered in, such as `1,999`.
  const locale = isRecord(value) ? value.locale : propsOf(control).locale;
  const number = isScalar(amount)
    ? moneyAmount(
        String(amount),
        typeof locale === 'string' ? locale : undefined
      )
    : null;
  const {min, max} = propsOf(control);

  return number !== null && withinBounds(number, min, max);
};

/** Whether a table cell's value is one its column's input can hold. */
function fitsColumn(
  cell: UiValue,
  column: {type?: unknown; options?: unknown}
): boolean {
  if (isBlank(cell)) {
    return true;
  }

  switch (column.type) {
    case 'checkbox':
    case 'lightswitch':
      return [true, false, '1', 1, 0].includes(cell as never);
    case 'select':
      return isScalar(cell) && optionValues(column.options).has(String(cell));
    case 'number':
      return isNumeric(cell);
    // Amounts are held with the locale they're formatted for.
    case 'money': {
      const amount = isRecord(cell) ? cell.value : cell;
      const locale = isRecord(cell) ? cell.locale : undefined;

      return (
        isBlank(amount) ||
        (isScalar(amount) &&
          moneyAmount(
            String(amount),
            typeof locale === 'string' ? locale : undefined
          ) !== null)
      );
    }
    case 'singleline':
    case 'email':
    case 'url':
      return isScalar(cell) && isSingleLine(String(cell));
    default:
      return true;
  }
}

const table: UiValueAcceptor = (control, {value}) => {
  if (isBlank(value)) {
    return true;
  }

  if (!isRecord(value) && !Array.isArray(value)) {
    return false;
  }

  const rows: UiValue[] = Object.values(value);

  if (rows.length === 0) {
    return true;
  }

  const {columns, keyed, maxRows, includeRowId} = propsOf(control) as {
    columns?: Record<
      string,
      {type?: unknown; options?: unknown; handle?: unknown}
    >;
    keyed?: boolean;
    maxRows?: number;
    includeRowId?: boolean | string;
  };
  const rowIdKey = typeof includeRowId === 'string' ? includeRowId : 'rowId';
  // Table fields' rows hold each cell under its column's handle too.
  const handles = new Set(
    Object.values(columns ?? {}).map((column) => column.handle)
  );

  return (
    // A keyed table holds its rows by key, and others in a list.
    Array.isArray(value) !== Boolean(keyed) &&
    (typeof maxRows !== 'number' || rows.length <= maxRows) &&
    rows.every(
      (row) =>
        isRecord(row) &&
        Object.entries(row).every(([key, cell]) =>
          columns?.[key]
            ? fitsColumn(cell, columns[key])
            : handles.has(key) || (Boolean(includeRowId) && key === rowIdKey)
        )
    )
  );
};

export const builtInValueAcceptors: Record<string, UiValueAcceptor> = {
  'craft:text': text,
  'craft:number': number,
  'craft:range': number,
  'craft:date': text,
  'craft:time': text,
  'craft:date-time': dateTime,
  'craft:slug': text,
  'craft:textarea': textarea,
  'craft:markdown': markdown,
  'craft:choice': choice,
  'craft:combobox': combobox,
  'craft:color-select': colorSelect,
  'craft:color': color,
  'craft:element-select': elementSelect,
  'craft:link': link,
  'craft:money': money,
  'craft:table': table,
};
