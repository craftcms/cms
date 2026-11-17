/**
 * The control panel's field value clipboard.
 *
 * A value copied from a field goes on the system clipboard as text, and as
 * HTML where that says more, such as a table, for other apps to paste (see
 * `system-clipboard.ts`). The value itself is kept in localStorage, so it
 * outlasts the page and is shared with other tabs, which are told about each
 * change. While it's there, a "copied" card previews it (see
 * `valuePreviews.ts`), and fields that take it offer to paste it.
 */

import {ConfigService} from '@craftcms/ui/services/Config';
import {escapeHtml} from '@craftcms/ui/utilities/escapeHtml';
import {t} from '@craftcms/ui/utilities/translate';
import {shallowRef, type ShallowRef} from 'vue';
import {broadcast, onBroadcast} from '@/common/craft/broadcast';
import {
  claimClipboard,
  elementFormats,
  onClipboardClaimed,
  writeSystemClipboard,
  type ClipboardFormats,
} from '@/common/craft/system-clipboard';
import {prefixedKey} from '@/common/composables/useStorage';
import {CopiedNotification} from '@/modules/messages/copied-notification';
import {formatMoney} from './money';
import type {UiControlPayload} from './types';
import type {UiValueSnapshot} from './valueAccess';
import {renderValuePreview} from './valuePreviews';

export interface CopiedFieldValue extends UiValueSnapshot {
  /** The component of the control it was read from, which a paste target must share. */
  component: string;
  /** The value scope of the control it was read from, which a paste target must share. */
  valueScope?: string;
  /** The label of the field it was copied from. */
  label: string | null;
  /** The value as text, as it was put on the system clipboard. */
  text: string;
  /** The value as HTML, as it was put on the system clipboard, if it was. */
  html?: string | null;
  /**
   * The value's items, when it's a list or one of a control's options, such
   * as the selected options' labels, with their icons and colors.
   */
  items?: CopiedValueItem[] | null;
}

/** One of a copied value's items, such as a selected option. */
export interface CopiedValueItem {
  label: string;
  /** The name of the option's icon. */
  icon?: string | null;
  /** The option's CSS color. */
  color?: string | null;
}

/** A value as other apps take it from the clipboard, and its items. */
export interface FieldValueFormats extends ClipboardFormats {
  /** The value's items, when it's a list or one of a control's options. */
  items?: CopiedValueItem[] | null;
}

const STORAGE_KEY = 'copiedFieldValue';
const BROADCAST_EVENT = 'copyFieldValue';

const copied = shallowRef<CopiedFieldValue | null>(null);
let card: CopiedFieldValueNotification | null = null;
let installed = false;

/** The copied value, kept live. */
export function useCopiedFieldValue(): Readonly<
  ShallowRef<CopiedFieldValue | null>
> {
  return copied;
}

/**
 * Puts a value on the clipboard, replacing what was there. Called while the
 * user's click is being handled, which the system clipboard needs.
 */
export function copyFieldValue(value: CopiedFieldValue): void {
  claimClipboard('fieldValue');
  writeSystemClipboard({text: value.text, html: value.html ?? null});
  write(value);
  copied.value = value;
  show(value);
  broadcast({event: BROADCAST_EVENT});
}

export function clearCopiedFieldValue(): void {
  write(null);
  copied.value = null;
  show(null);
  broadcast({event: BROADCAST_EVENT});
}

/**
 * Shows what's on the clipboard, keeps up with other tabs, and clears it when
 * elements are copied.
 */
export function installFieldValueClipboard(): void {
  if (installed) {
    return;
  }

  installed = true;
  copied.value = read();
  onClipboardClaimed((owner) => {
    if (owner !== 'fieldValue' && copied.value) {
      clearCopiedFieldValue();
    }
  });
  onBroadcast(BROADCAST_EVENT, () => {
    copied.value = read();
    show(copied.value);
  });

  if (copied.value && ConfigService.getInstance().get('userId')) {
    show(copied.value, false);
  }
}

/**
 * The value as other apps take it: element titles one per line for
 * relations (linked to their pages in the HTML), a table's rows with their
 * cells separated by tabs, which spreadsheets split into cells (and as an
 * HTML table), option labels for choices, and otherwise the text it holds.
 */
export function fieldValueFormats(
  control: UiControlPayload<object>,
  snapshot: UiValueSnapshot
): FieldValueFormats {
  const elements = snapshot.props?.elements;

  if (Array.isArray(elements)) {
    return elementFormats(elements);
  }

  const columns = (control.props as {columns?: unknown}).columns;

  if (control.component === 'craft:table' && columns) {
    return tableFormats(snapshot.value, columns as Record<string, TableColumn>);
  }

  const {value} = snapshot;

  switch (control.component) {
    case 'craft:link':
      return linkFormats(value);
    case 'craft:money':
      return {text: moneyText(value, control.props), html: null};
    case 'craft:date-time':
      return {text: dateTimeText(value, control.props), html: null};
  }

  const labels = optionLabels(control.props);
  const options = optionItems(control.props);
  const item = (entry: unknown): CopiedValueItem | null => {
    const option = isScalar(entry) ? options?.get(String(entry)) : undefined;
    const label = option?.label ?? valueText(entry, labels);

    return label ? {...option, label} : null;
  };

  return {
    text: valueText(value, labels),
    html: null,
    items: Array.isArray(value)
      ? value.flatMap((entry) => item(entry) ?? [])
      : isScalar(value) && options?.has(String(value))
        ? [item(value)!]
        : null,
  };
}

type TableColumn = {heading?: unknown; options?: unknown};

function tableFormats(
  value: unknown,
  columns: Record<string, TableColumn>
): FieldValueFormats {
  const rows =
    value && typeof value === 'object'
      ? (Object.values(value) as unknown[])
      : [];
  const ids = Object.keys(columns);
  const headings = ids.map((id) => {
    const heading = columns[id]!.heading;

    return typeof heading === 'string' ? heading : '';
  });
  const cells = rows.map((row) =>
    ids.map((id) =>
      valueText(
        (row as Record<string, unknown> | null)?.[id],
        optionLabels(columns[id]!)
      )
    )
  );
  const htmlRow = (tag: 'th' | 'td', texts: string[]) =>
    `<tr>${texts.map((text) => `<${tag}>${escapeHtml(text)}</${tag}>`).join('')}</tr>`;

  return {
    text: cells.map((row) => row.map(tsvCell).join('\t')).join('\n'),
    html:
      '<table>' +
      (headings.some(Boolean)
        ? `<thead>${htmlRow('th', headings)}</thead>`
        : '') +
      `<tbody>${cells.map((row) => htmlRow('td', row)).join('')}</tbody></table>`,
  };
}

/** URL schemes a copied link is linked with in HTML. */
const LINKABLE_URL = /^(https?|mailto|tel|sms):/i;

/**
 * A link as its URL, or for `mailto:`, `tel:`, and `sms:` links the address
 * or number, and in HTML, linked from its label. Links to elements, whose
 * values are reference tags, are their labels alone.
 */
function linkFormats(value: unknown): FieldValueFormats {
  const {
    value: url,
    label,
    defaultLabel,
    urlSuffix,
  } = (value ?? {}) as Record<string, unknown>;
  const text = (part: unknown) => (typeof part === 'string' ? part.trim() : '');
  const name = text(label) || text(defaultLabel);
  const href = text(url) + text(urlSuffix);

  // Reference tags, which element links hold, mean nothing outside Craft.
  if (text(url).startsWith('{')) {
    return {text: name, html: null};
  }

  // Other URLs, such as `/about` or `#top`, keep their destination, but only
  // these schemes are linked.
  if (!LINKABLE_URL.test(href)) {
    return {text: href, html: null};
  }

  const address = linkAddress(href);

  return {
    text: address,
    html: `<a href="${escapeHtml(href)}">${escapeHtml(name || address)}</a>`,
  };
}

/** A URL, or a `mailto:`, `tel:`, or `sms:` link's address or number. */
function linkAddress(href: string): string {
  return /^https?:/i.test(href)
    ? href
    : href.replace(/^[a-z]+:/i, '').replace(/[?#].*$/, '');
}

/** An amount with its currency, such as `$1,999.00`. */
function moneyText(value: unknown, props: object): string {
  const {currency, locale} = props as {currency?: string; locale?: string};
  const {value: amount, locale: amountLocale} = (
    value && typeof value === 'object' ? value : {value}
  ) as {value?: unknown; locale?: unknown};

  if (typeof amount !== 'string' && typeof amount !== 'number') {
    return '';
  }

  return formatMoney(
    String(amount),
    currency,
    typeof amountLocale === 'string' ? amountLocale : locale
  );
}

/**
 * A date and time as the user's locale writes them, such as
 * `Nov 13, 2026, 2:30 PM`, followed by its time zone where the control
 * shows one.
 */
function dateTimeText(value: unknown, props: object): string {
  const {date, time, timezone} = (value ?? {}) as Record<string, unknown>;
  const {locale, showDate, showTime, showTimeZone} = props as {
    locale?: string;
    showDate?: boolean;
    showTime?: boolean;
    showTimeZone?: boolean;
  };
  // Values hold a date and a time even when the field hides one of them.
  const dateParts =
    showDate !== false && typeof date === 'string'
      ? date.match(/^(\d{4})-(\d{2})-(\d{2})$/)
      : null;
  const timeParts =
    showTime !== false && typeof time === 'string'
      ? time.match(/^(\d{2}):(\d{2})/)
      : null;

  // The parts the field shows, as they're held, so a cleared field has
  // nothing to copy.
  const shownText = [showDate !== false && date, showTime !== false && time]
    .filter((part) => typeof part === 'string' && part)
    .join(' ');

  if (!dateParts && !timeParts) {
    return shownText;
  }

  // The wall-clock date and time, formatted as they are, not converted. The
  // year is set on its own, as `Date.UTC()` reads years 0–99 as 1900–1999.
  const wallClock = new Date(0);
  wallClock.setUTCFullYear(
    dateParts ? Number(dateParts[1]) : 2000,
    dateParts ? Number(dateParts[2]) - 1 : 0,
    dateParts ? Number(dateParts[3]) : 1
  );
  wallClock.setUTCHours(
    timeParts ? Number(timeParts[1]) : 0,
    timeParts ? Number(timeParts[2]) : 0
  );
  let text: string;

  try {
    text = new Intl.DateTimeFormat(locale?.replace(/_/g, '-'), {
      timeZone: 'UTC',
      dateStyle: dateParts ? 'medium' : undefined,
      timeStyle: timeParts ? 'short' : undefined,
    }).format(wallClock);
  } catch {
    return shownText;
  }

  return showTimeZone && typeof timezone === 'string' && timezone
    ? `${text} (${timezone})`
    : text;
}

/** A cell for tab-separated text, quoted the way spreadsheets read it. */
function tsvCell(text: string): string {
  return /[\t\n"]/.test(text) ? `"${text.replace(/"/g, '""')}"` : text;
}

function valueText(
  value: unknown,
  options: Map<string, string> | null
): string {
  if (value === null || value === undefined) {
    return '';
  }

  // An option's own label, if it's one, as its item is labeled.
  if (typeof value === 'boolean') {
    return options?.get(String(value)) ?? (value ? t('Yes') : t('No'));
  }

  if (typeof value === 'string' || typeof value === 'number') {
    return options?.get(String(value)) ?? String(value);
  }

  if (typeof value !== 'object') {
    return '';
  }

  // A date and time, as the date controls hold them
  if (!Array.isArray(value) && 'date' in value) {
    const {date, time} = value as {date?: unknown; time?: unknown};

    return [date, time]
      .filter((part) => typeof part === 'string' && part)
      .join(' ');
  }

  const entries = Array.isArray(value)
    ? value
    : 'label' in value && typeof value.label === 'string' && value.label
      ? [value.label]
      : 'value' in value
        ? [value.value]
        : Object.values(value);

  return entries
    .map((entry) => valueText(entry, options))
    .filter(Boolean)
    .join(', ');
}

function isScalar(value: unknown): value is string | number | boolean {
  return ['string', 'number', 'boolean'].includes(typeof value);
}

/**
 * A choice control's options, with their icons and colors, by value,
 * including those within option groups.
 */
function optionItems(props: object): Map<string, CopiedValueItem> | null {
  const options = (props as {options?: unknown}).options;

  if (!Array.isArray(options)) {
    return null;
  }

  const items = new Map<string, CopiedValueItem>();
  const collect = (list: unknown[]): void => {
    for (const option of list as {
      value?: unknown;
      label?: unknown;
      icon?: unknown;
      color?: unknown;
      options?: unknown;
    }[]) {
      if (!option || typeof option !== 'object') {
        continue;
      }

      if (option.options && typeof option.options === 'object') {
        collect(Object.values(option.options));
      } else if (typeof option.label === 'string' && isScalar(option.value)) {
        items.set(String(option.value), {
          label: option.label,
          icon: typeof option.icon === 'string' ? option.icon : null,
          color: typeof option.color === 'string' ? option.color : null,
        });
      }
    }
  };
  collect(options);

  return items;
}

/** A choice control's option labels, by value. */
function optionLabels(props: object): Map<string, string> | null {
  const options = optionItems(props);

  return options
    ? new Map([...options].map(([value, {label}]) => [value, label]))
    : null;
}

/** The "copied" card, with the value's preview. */
class CopiedFieldValueNotification extends CopiedNotification {
  readonly #value: CopiedFieldValue;

  constructor(value: CopiedFieldValue, animate: boolean) {
    super(
      value.label
        ? t('“{name}” value copied.', {name: value.label})
        : t('Value copied.'),
      {animate}
    );

    this.#value = value;
  }

  override async getDetails(): Promise<HTMLElement | null> {
    return this.copiedDetails(
      await renderValuePreview(this.#value),
      this.#value.text
    );
  }

  override handleCloseButtonClick(): void {
    clearCopiedFieldValue();
  }
}

function show(value: CopiedFieldValue | null, animate = true): void {
  const current = card;
  card = null;
  void current?.close();

  if (value) {
    card = new CopiedFieldValueNotification(value, animate);
    void card.show();
  }
}

/**
 * The copied value, if the signed-in user copied it. It outlasts their
 * session, so it's dropped once someone else, or no one, is signed in.
 */
function read(): CopiedFieldValue | null {
  try {
    const stored = localStorage.getItem(prefixedKey(STORAGE_KEY));

    if (!stored) {
      return null;
    }

    const {userId, ...value} = JSON.parse(stored) as CopiedFieldValue & {
      userId?: unknown;
    };

    if (userId !== ConfigService.getInstance().get('userId')) {
      localStorage.removeItem(prefixedKey(STORAGE_KEY));
      return null;
    }

    return value;
  } catch {
    return null;
  }
}

function write(value: CopiedFieldValue | null): void {
  try {
    if (value) {
      localStorage.setItem(
        prefixedKey(STORAGE_KEY),
        JSON.stringify({
          ...value,
          userId: ConfigService.getInstance().get('userId'),
        })
      );
    } else {
      localStorage.removeItem(prefixedKey(STORAGE_KEY));
    }
  } catch {
    // Storage can be full, or unavailable in a private window
  }
}
