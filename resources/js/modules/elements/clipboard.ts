/**
 * The control panel's element clipboard.
 *
 * Copied elements are kept in localStorage, so they outlast the page and are
 * shared with other tabs, which are told about each change. The "copied" card
 * ({@link CopiedElementsNotification}) also fills in each element's chip data
 * (where an entry's `entryTypeId` comes from) and drops elements that no
 * longer exist; listeners hear about the elements once it has.
 */

import {actionClient} from '@craftcms/ui/utilities/api/actionClient';
import {isHttpError} from '@craftcms/ui/utilities/api/http';
import {ConfigService} from '@craftcms/ui/services/Config';
import {t} from '@craftcms/ui/utilities/translate';
import {broadcast, onBroadcast} from '@/common/craft/broadcast';
import {elementTypeName} from '@/common/craft/element-types';
import {uppercaseFirst} from '@/common/craft/strings';
import {prefixedKey} from '@/common/composables/useStorage';
import {showError, showSuccess} from '@/modules/messages/messages';
import {CopiedElementsNotification} from './copied-elements-notification';

export interface CopiedElementInfo {
  type: string;
  id: ElementId;
  draftId?: JsonValue;
  revisionId?: JsonValue;
  fieldId?: number | null;
  ownerId?: ElementId;
  siteId?: number | null;
  data?: Record<string, JsonValue | undefined> & {entryTypeId?: number};
}

type ElementId = string | number | null;

type JsonValue =
  | string
  | number
  | boolean
  | null
  | JsonValue[]
  | {[key: string]: JsonValue};

/** Called with the copied elements, and the label for a paste button (`null` when there's nothing to paste). */
export type CopiedElementsListener = (
  elements: CopiedElementInfo[],
  pasteLabel: string | null
) => void;

interface LegacyCraft {
  cp?: {copyElementCallbacks?: CopiedElementsListener[]};
  CP?: {prototype: Record<string, unknown>};
}

const STORAGE_KEY = 'copiedElements';
const BROADCAST_EVENT = 'copyElements';

const listeners = new Set<CopiedElementsListener>();
let card: CopiedElementsNotification | null = null;
let installed = false;

/** Puts elements on the clipboard, replacing what was there. */
export function copyElements(elements: CopiedElementInfo[]): void {
  write(elements);
  show(elements);
  broadcast({event: BROADCAST_EVENT});
}

export function clearCopiedElements(): void {
  copyElements([]);
}

/** The copied elements, once the card has filled them in. */
export function getCopiedElements(): CopiedElementInfo[] {
  return card?.loaded ? card.elementInfo : [];
}

/**
 * Calls `listener` whenever the copied elements change, and straight away if
 * there are some. Returns a function that stops listening.
 */
export function onCopiedElementsChange(
  listener: CopiedElementsListener
): () => void {
  listeners.add(listener);

  if (card?.loaded) {
    listener(card.elementInfo, pasteElementsLabel(card.elementInfo));
  }

  return () => listeners.delete(listener);
}

export function pasteElementsLabel(
  elements: CopiedElementInfo[]
): string | null {
  if (!elements.length) {
    return null;
  }

  const type = elementTypeName(elements[0]!.type, elements.length !== 1);

  return uppercaseFirst(t('Paste {type}', {type: type ?? ''}));
}

/**
 * Duplicates the copied elements with `newAttributes` applied, then clears the
 * clipboard. Returns the new elements, or none if the paste failed.
 */
export async function pasteElements(
  newAttributes: Record<string, unknown> = {}
): Promise<{id: number}[]> {
  const elements = read();

  if (!elements.length) {
    return [];
  }

  let data: {message?: string; newElements: {id: number}[]};

  try {
    ({data} = await actionClient.post('elements/bulk-duplicate', {
      elements,
      newAttributes,
    }));
  } catch (error) {
    showError(
      isHttpError<{message?: string}>(error)
        ? error.response?.data?.message
        : null
    );
    return [];
  }

  clearCopiedElements();
  showSuccess(data.message ?? '');

  return data.newElements;
}

/**
 * Shows what's on the clipboard, keeps up with other tabs, and points
 * `Craft.cp`'s clipboard methods here.
 */
export function installClipboard(): void {
  if (installed) {
    return;
  }

  installed = true;
  installLegacyShim();
  onBroadcast(BROADCAST_EVENT, () => show(read()));

  if (ConfigService.getInstance().get('userId')) {
    const elements = read();

    if (elements.length) {
      show(elements, {animate: false});
    }
  }
}

function show(
  elements: CopiedElementInfo[],
  settings: Record<string, unknown> = {}
): void {
  close();

  if (!elements.length) {
    notify([]);
    return;
  }

  const next = new CopiedElementsNotification(
    elements,
    clearCopiedElements,
    settings
  );
  card = next;
  void next.show();
  next.on('show', () => {
    // The card clears the clipboard when none of the elements still exist
    if (card === next) {
      notify(next.elementInfo);
    }
  });
}

function close(): void {
  const current = card;
  card = null;
  current?.close();
}

function notify(elements: CopiedElementInfo[]): void {
  const label = pasteElementsLabel(elements);
  listeners.forEach((listener) => listener(elements, label));
}

function read(): CopiedElementInfo[] {
  try {
    const value = localStorage.getItem(prefixedKey(STORAGE_KEY));
    return value ? (JSON.parse(value) as CopiedElementInfo[]) : [];
  } catch {
    return [];
  }
}

function write(elements: CopiedElementInfo[]): void {
  try {
    if (elements.length) {
      localStorage.setItem(prefixedKey(STORAGE_KEY), JSON.stringify(elements));
    } else {
      localStorage.removeItem(prefixedKey(STORAGE_KEY));
    }
  } catch {
    // Storage can be full, or unavailable in a private window
  }
}

/** Element info from the data attributes of legacy element markup. */
function fromJQuery(
  $elements: ArrayLike<HTMLElement> & {
    each(fn: (i: number, el: HTMLElement) => void): void;
  }
): CopiedElementInfo[] {
  const $ = (window as {jQuery?: (el: HTMLElement) => any}).jQuery!;
  const elements: CopiedElementInfo[] = [];

  $elements.each((_i, el) => {
    const $el = $(el);
    elements.push({
      type: $el.data('type'),
      id: $el.data('id'),
      draftId: $el.data('draftId'),
      revisionId: $el.data('revisionId'),
      fieldId: $el.data('fieldId'),
      ownerId: $el.data('ownerId'),
      siteId: $el.data('siteId'),
    });
  });

  return elements;
}

function installLegacyShim(): void {
  const Craft = window.Craft as LegacyCraft | undefined;
  const proto = Craft?.CP?.prototype;

  if (!proto) {
    return;
  }

  Object.assign(proto, {
    copyElements(elements: CopiedElementInfo[] | {jquery: string}) {
      copyElements(
        'jquery' in elements
          ? fromJQuery(elements as unknown as Parameters<typeof fromJQuery>[0])
          : elements
      );
    },
    clearCopiedElements,
    getCopiedElements,
    pasteElements,
    onCopyElements(listener: CopiedElementsListener) {
      onCopiedElementsChange(listener);
    },
    showElementCopyNotification(
      elements: CopiedElementInfo[] = [],
      settings: Record<string, unknown> = {}
    ) {
      show(elements, settings);
    },
    closeElementCopyNotification(notifyListeners = true) {
      const hadCard = card !== null;
      close();

      if (hadCard && notifyListeners) {
        notify([]);
      }
    },
  });

  // Listeners registered before this ran
  const cp = Craft.cp;
  cp?.copyElementCallbacks?.forEach((listener) => listeners.add(listener));
  cp?.copyElementCallbacks?.splice(0);
}
