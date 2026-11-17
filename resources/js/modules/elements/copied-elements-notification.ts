import {actionClient} from '@craftcms/ui/utilities/api/actionClient';
import {appendBodyHtml, appendHeadHtml} from '@craftcms/ui/utilities/dom';
import {t} from '@craftcms/ui/utilities/translate';
import {elementTypeName} from '@/common/craft/element-types';
import {uppercaseFirst} from '@/common/craft/strings';
import {
  elementFormats,
  type ClipboardFormats,
} from '@/common/craft/system-clipboard';
import {CopiedNotification} from '@/modules/messages/copied-notification';
import type {NotificationSettings} from '@/modules/messages/notification';
import type {CopiedElementInfo} from './clipboard';

interface RenderedElements {
  elements?: Record<string, string[]> | unknown[];
  headHtml?: string;
  bodyHtml?: string;
}

/** A chip's data attributes, typed the way jQuery's `.data()` reads them. */
function chipData(chip: HTMLElement): NonNullable<CopiedElementInfo['data']> {
  return Object.fromEntries(
    Object.entries(chip.dataset).map(([key, value]) => {
      try {
        return [key, JSON.parse(value ?? '')];
      } catch {
        return [key, value];
      }
    })
  );
}

/**
 * The "copied" card: the copied elements' chips. Loading them also fills in
 * each element's chip data and drops elements that no longer exist, so
 * `elementInfo` is only final once it's shown.
 */
export class CopiedElementsNotification extends CopiedNotification {
  elementInfo: CopiedElementInfo[];
  loaded = false;
  /** The elements as they're put on the system clipboard, once they're loaded. */
  clipboardFormats: ClipboardFormats | null = null;
  /** Resolves to `clipboardFormats` once they're loaded, or `null` if they never are. */
  readonly loadedFormats: Promise<ClipboardFormats | null>;
  #resolveFormats!: (formats: ClipboardFormats | null) => void;
  #onCancel: () => void;

  constructor(
    elementInfo: CopiedElementInfo[],
    onCancel: () => void,
    settings: NotificationSettings = {}
  ) {
    if (!elementInfo.length) {
      throw new Error('No elements are copied.');
    }

    super('', settings);

    this.elementInfo = elementInfo;
    this.#onCancel = onCancel;
    this.message = this.#message();
    this.loadedFormats = new Promise((resolve) => {
      this.#resolveFormats = resolve;
    });
    this.on('close', () => this.#resolveFormats(null));
  }

  #message(): string {
    const type = this.elementInfo[0]!.type;

    return this.elementInfo.length === 1
      ? t('{type} copied.', {type: uppercaseFirst(elementTypeName(type) ?? '')})
      : uppercaseFirst(
          t('{total, number} {type} copied.', {
            total: this.elementInfo.length,
            type: elementTypeName(type, true),
          })
        );
  }

  override async getDetails(): Promise<HTMLElement | null> {
    let chips: Map<CopiedElementInfo, HTMLElement> | null;

    try {
      chips = await renderChips(this.elementInfo);
    } catch {
      // Offline, say. The elements stay copied, to paste within the control
      // panel, but there are no titles for the system clipboard.
      this.loaded = true;
      this.#resolveFormats(null);
      return null;
    }

    // None of them exist anymore
    if (!chips) {
      this.#resolveFormats(null);
      this.#onCancel();
      return null;
    }

    const remaining = this.elementInfo.filter((element) => chips.has(element));

    for (const element of remaining) {
      element.data = chipData(chips.get(element)!);
    }

    if (remaining.length !== this.elementInfo.length) {
      this.elementInfo = remaining;
      this.updateMessage(this.#message());
    }

    this.clipboardFormats = elementFormats(
      [...chips.values()].map(({dataset}) => ({
        label: dataset.label,
        url: dataset.url,
      }))
    );
    this.loaded = true;
    this.#resolveFormats(this.clipboardFormats);

    return this.copiedDetails(
      chipRow(chips.values()),
      this.clipboardFormats.text
    );
  }

  override handleCloseButtonClick(): void {
    this.#onCancel();
  }
}

/** An element to draw a chip for. */
export interface ChipElement {
  type: string;
  id: string | number | null;
  siteId?: number | null;
  revisionId?: unknown;
}

/**
 * Draws elements' chips, in order, leaving out elements that no longer
 * exist. Resolves to `null` when none of them do.
 */
export async function renderChips<T extends ChipElement>(
  elements: T[]
): Promise<Map<T, HTMLElement> | null> {
  const {data} = await actionClient.post<RenderedElements>(
    'app/render-elements',
    {
      elements: elements.map((element) => ({
        type: element.type,
        id: element.id,
        siteId: element.siteId,
        revisionId: element.revisionId ?? null,
        instances: [{ui: 'chip', hyperlink: false}],
      })),
    }
  );

  // An empty list rather than a map: none of them exist anymore
  if (!data.elements || Array.isArray(data.elements)) {
    return null;
  }

  setTimeout(async () => {
    await appendHeadHtml(data.headHtml ?? '');
    await appendBodyHtml(data.bodyHtml ?? '');
  }, 1);

  const rendered = data.elements;
  const template = document.createElement('template');
  const chips = new Map<T, HTMLElement>();

  for (const element of elements) {
    template.innerHTML = (rendered[String(element.id)] ?? []).join('');
    const chip = [...template.content.children].find(
      (candidate): candidate is HTMLElement =>
        candidate instanceof HTMLElement &&
        candidate.dataset.id === String(element.id) &&
        candidate.dataset.siteId === String(element.siteId)
    );

    if (chip) {
      chips.set(element, chip);
    }
  }

  return chips;
}

/**
 * Lays chips out side by side, as small rounded chips that sit among badges,
 * as "copied" cards preview them.
 */
export function chipRow(chips: Iterable<HTMLElement>): HTMLElement {
  const row = document.createElement('div');
  row.className = 'copied-chips';

  for (const chip of chips) {
    chip.setAttribute('size', 'xsmall');
    chip.toggleAttribute('rounded', true);
    row.append(chip);
  }

  return row;
}
