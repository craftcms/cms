/**
 * The control panel's field value clipboard.
 *
 * A value copied from a field is kept in localStorage, so it outlasts the
 * page and is shared with other tabs, which are told about each change. While
 * it's there, a "copied" card previews it, like the element clipboard's, and
 * fields of the same type offer to paste it.
 */

import {ConfigService} from '@craftcms/ui/services/Config';
import {t} from '@craftcms/ui/utilities/translate';
import {shallowRef, type ShallowRef} from 'vue';
import {broadcast, onBroadcast} from '@/common/craft/broadcast';
import {prefixedKey} from '@/common/composables/useStorage';
import {Notification} from '@/modules/messages/notification';
import type {UiControlPayload} from './types';
import type {UiValueSnapshot} from './valueAccess';

export interface CopiedFieldValue extends UiValueSnapshot {
  /** The component of the control it was read from, which a paste target must share. */
  component: string;
  /** The value scope of the control it was read from, which a paste target must share. */
  valueScope?: string;
  /** The label of the field it was copied from. */
  label: string | null;
  /** The value as text, for the card. */
  preview: string;
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

/** Puts a value on the clipboard, replacing what was there. */
export function copyFieldValue(value: CopiedFieldValue): void {
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
 * The value as text, for the card: element titles for relations, option
 * labels for choices, and otherwise the text it holds.
 */
export function fieldValuePreview(
  control: UiControlPayload<object>,
  snapshot: UiValueSnapshot
): string {
  const elements = snapshot.props?.elements;
  const text = Array.isArray(elements)
    ? labels(elements).join(', ')
    : previewText(snapshot.value, optionLabels(control.props));

  return text.length > PREVIEW_LENGTH
    ? `${text.slice(0, PREVIEW_LENGTH)}…`
    : text;
}

function previewText(
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

  const entries = Array.isArray(value)
    ? value
    : 'label' in value && typeof value.label === 'string' && value.label
      ? [value.label]
      : 'value' in value
        ? [value.value]
        : Object.values(value);

  return entries
    .map((entry) => previewText(entry, options))
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

function labels(elements: unknown[]): string[] {
  return elements.flatMap((element) => {
    const label = (element as {label?: unknown} | null)?.label;

    return typeof label === 'string' && label ? [label] : [];
  });
}

/** The "copied" card, with the value's preview and a Cancel button that clears it. */
class CopiedFieldValueNotification extends Notification {
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

    if (value.preview) {
      const preview = document.createElement('div');
      preview.className = 'copied-field-value';
      preview.textContent = value.preview;
      this.settings.details = preview;
    }
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
