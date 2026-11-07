/**
 * Which values the built-in controls take from other controls of their kind,
 * decided from the value itself and the control's own settings: a value a
 * control couldn't have been given by hand, such as multi-line text for a
 * single-line input or an option it doesn't have, isn't pasted into it.
 *
 * Anything that depends on more than the value, such as the elements a
 * relation field can select, is settled by the controls' value scopes.
 */

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
 * numbers, and as strings when they're strings, like dates and times are.
 */
function withinBounds(value: Scalar, min: unknown, max: unknown): boolean {
  return [min, max].every((bound, index) => {
    const order =
      typeof bound === 'number'
        ? Number(value) - bound
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

/** Whether a choice of `values` fits the options a control offers. */
function fitsOptions(
  value: UiValue,
  options: unknown,
  {multiple, limit}: {multiple?: boolean; limit?: unknown}
): boolean {
  const values = Array.isArray(value) ? value : [value];
  const offered = optionValues(options);

  return (
    (multiple || values.length <= 1) &&
    (typeof limit !== 'number' || values.length <= limit) &&
    values.every((item) => isScalar(item) && offered.has(String(item)))
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
  if (allowCustom) {
    const values = Array.isArray(value) ? value : [value];

    return (multiple || values.length === 1) && values.every(isScalar);
  }

  return fitsOptions(value, options, {multiple: Boolean(multiple)});
};

const combobox: UiValueAcceptor = (control, {value}) => {
  const {options, multiple, limit, requireOptionMatch} = propsOf(control);

  if (isBlank(value)) {
    return true;
  }

  if (!requireOptionMatch) {
    const values = Array.isArray(value) ? value : [value];

    return (
      (Boolean(multiple) || values.length === 1) &&
      (typeof limit !== 'number' || values.length <= limit) &&
      values.every(isScalar)
    );
  }

  return fitsOptions(value, options, {multiple: Boolean(multiple), limit});
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

  const {min, max} = propsOf(control);

  return isNumeric(amount) && withinBounds(amount as Scalar, min, max);
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
    case 'money':
      return isNumeric(cell);
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
    columns?: Record<string, {type?: unknown; options?: unknown}>;
    keyed?: boolean;
    maxRows?: number;
    includeRowId?: boolean | string;
  };
  const rowIdKey = typeof includeRowId === 'string' ? includeRowId : 'rowId';

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
            : Boolean(includeRowId) && key === rowIdKey
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
