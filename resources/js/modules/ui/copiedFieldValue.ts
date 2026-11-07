/**
 * The control panel's field value clipboard.
 *
 * A value copied from a field goes on the system clipboard as text, and as
 * HTML where that says more, such as a table, for other apps to paste. The
 * value itself is kept in localStorage, so it outlasts the page and is shared
 * with other tabs, which are told about each change. While it's there, a
 * "copied" card previews it, like the element clipboard's (see
 * `valuePreviews.ts`), and fields that take it offer to paste it.
 *
 * Nothing is read back from the system clipboard. Pasting there only pastes
 * the stored value when the clipboard still holds the text it was copied as,
 * so another page can't put a value in a field.
 */

import {ConfigService} from '@craftcms/ui/services/Config';
import {escapeHtml} from '@craftcms/ui/utilities/escapeHtml';
import {t} from '@craftcms/ui/utilities/translate';
import {shallowRef, type ShallowRef} from 'vue';
import {broadcast, onBroadcast} from '@/common/craft/broadcast';
import {prefixedKey} from '@/common/composables/useStorage';
import {Notification} from '@/modules/messages/notification';
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
  /** The value's items as text, when it's a list, such as selected options' labels. */
  items?: string[] | null;
}

/** A value as other apps take it from the clipboard. */
export interface FieldValueFormats {
  text: string;
  /** Only when it says more than the text does. */
  html: string | null;
  /** The value's items as text, when it's a list of them. */
  items?: string[] | null;
}

const STORAGE_KEY = 'copiedFieldValue';
const BROADCAST_EVENT = 'copyFieldValue';
const PREVIEW_LENGTH = 500;

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
  writeSystemClipboard(value.text, value.html ?? null);
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

/** Shows what's on the clipboard, and keeps up with other tabs. */
export function installFieldValueClipboard(): void {
  if (installed) {
    return;
  }

  installed = true;
  copied.value = read();
  onBroadcast(BROADCAST_EVENT, () => {
    copied.value = read();
    show(copied.value);
  });

  if (copied.value && ConfigService.getInstance().get('userId')) {
    show(copied.value, false);
  }
}

/**
 * Whether text pasted from the system clipboard is what `copied` was copied
 * as, give or take the line endings other apps and systems use.
 */
export function isCopiedText(copied: CopiedFieldValue, text: string): boolean {
  const normalize = (value: string) =>
    value.replace(/\r\n?/g, '\n').replace(/\n+$/, '');

  return copied.text !== '' && normalize(text) === normalize(copied.text);
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

  const labels = optionLabels(control.props);

  return {
    text: valueText(snapshot.value, labels),
    html: null,
    items: Array.isArray(snapshot.value)
      ? snapshot.value.map((item) => valueText(item, labels)).filter(Boolean)
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

/** A cell for tab-separated text, quoted the way spreadsheets read it. */
function tsvCell(text: string): string {
  return /[\t\n"]/.test(text) ? `"${text.replace(/"/g, '""')}"` : text;
}

function elementFormats(elements: unknown[]): FieldValueFormats {
  const presentations = elements.flatMap((element) => {
    const {label, url} = (element ?? {}) as {label?: unknown; url?: unknown};

    return typeof label === 'string' && label
      ? [{label, url: typeof url === 'string' ? url : null}]
      : [];
  });

  return {
    text: presentations.map(({label}) => label).join('\n'),
    // Front-end URLs only: an element's edit URL would give away the control
    // panel's address wherever it's pasted.
    html: presentations.some(({url}) => url)
      ? presentations
          .map(({label, url}) =>
            url
              ? `<a href="${escapeHtml(url)}">${escapeHtml(label)}</a>`
              : escapeHtml(label)
          )
          .join('<br>')
      : null,
  };
}

function valueText(
  value: unknown,
  options: Map<string, string> | null
): string {
  if (value === null || value === undefined) {
    return '';
  }

  if (typeof value === 'boolean') {
    return value ? t('Yes') : t('No');
  }

  if (typeof value === 'string' || typeof value === 'number') {
    return options?.get(String(value)) ?? String(value).trim();
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

/** A choice control's option labels, by value. */
function optionLabels(props: object): Map<string, string> | null {
  const options = (props as {options?: unknown}).options;

  if (!Array.isArray(options)) {
    return null;
  }

  return new Map(
    options.flatMap((option: {value?: unknown; label?: unknown}) =>
      option &&
      typeof option.label === 'string' &&
      ['string', 'number', 'boolean'].includes(typeof option.value)
        ? [[String(option.value as string), option.label] as const]
        : []
    )
  );
}

/** The "copied" card, with the value's preview and a Cancel button that clears it. */
class CopiedFieldValueNotification extends Notification {
  readonly #value: CopiedFieldValue;

  constructor(value: CopiedFieldValue, animate: boolean) {
    super(
      'notice',
      value.label
        ? t('“{name}” value copied.', {name: value.label})
        : t('Value copied.'),
      {
        icon: 'clone-dashed',
        persist: true,
        class: 'copied-elements-notification copied-field-value-notification',
        animate,
      }
    );

    this.#value = value;
  }

  /**
   * The value's preview, when its control has one, or else its text. A
   * preview is only for looking at, so screen readers read the text with it.
   * Either one runs off the card's edges when it's too big for it, and fades
   * out as it reaches them.
   */
  override async getDetails(): Promise<HTMLElement | null> {
    const {text} = this.#value;
    const preview = await renderValuePreview(this.#value);

    if (!preview && !text) {
      return null;
    }

    const container = document.createElement('div');
    container.className = 'copied-field-value';
    const content = document.createElement('div');
    const textElement = document.createElement('span');
    textElement.textContent =
      text.length > PREVIEW_LENGTH ? `${text.slice(0, PREVIEW_LENGTH)}…` : text;

    if (preview) {
      content.className = 'copied-field-value-preview';
      // Neither focusable nor read out, so it can't hold up the card either.
      content.inert = true;
      content.append(preview);
      textElement.className = 'sr-only';
      container.append(content, textElement);
    } else {
      content.className = 'copied-field-value-text';
      content.append(textElement);
      container.append(content);
    }

    // Fade out only the edges the value actually runs off. The content's
    // size changes as a preview's chips or images load.
    const observer = new ResizeObserver(() => {
      container.toggleAttribute(
        'data-overflow-block',
        container.scrollHeight > container.clientHeight + 1
      );
      container.toggleAttribute(
        'data-overflow-inline',
        container.scrollWidth > container.clientWidth + 1
      );
    });
    observer.observe(container);
    observer.observe(content);
    this.on('close', () => observer.disconnect());

    return container;
  }

  override createCloseButton(): HTMLElement {
    return Notification.createIconButton(t('Cancel'));
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

function writeSystemClipboard(text: string, html: string | null): void {
  const clipboard = navigator.clipboard as Clipboard | undefined;

  // An empty value would only empty the clipboard.
  if (!clipboard || text === '') {
    return;
  }

  const written =
    html !== null && typeof ClipboardItem !== 'undefined'
      ? clipboard.write([
          new ClipboardItem({
            'text/plain': new Blob([text], {type: 'text/plain'}),
            'text/html': new Blob([html], {type: 'text/html'}),
          }),
        ])
      : clipboard.writeText(text);

  written.catch(() => {
    // The value can still be pasted with the “Paste value” action.
  });
}

function read(): CopiedFieldValue | null {
  try {
    const value = localStorage.getItem(prefixedKey(STORAGE_KEY));

    return value ? (JSON.parse(value) as CopiedFieldValue) : null;
  } catch {
    return null;
  }
}

function write(value: CopiedFieldValue | null): void {
  try {
    if (value) {
      localStorage.setItem(prefixedKey(STORAGE_KEY), JSON.stringify(value));
    } else {
      localStorage.removeItem(prefixedKey(STORAGE_KEY));
    }
  } catch {
    // Storage can be full, or unavailable in a private window
  }
}
