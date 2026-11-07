import {actionClient} from '@craftcms/ui/utilities/api/actionClient';
import {appendBodyHtml, appendHeadHtml} from '@craftcms/ui/utilities/dom';
import {t} from '@craftcms/ui/utilities/translate';
import {elementTypeName} from '@/common/craft/element-types';
import {uppercaseFirst} from '@/common/craft/strings';
import {
  Notification,
  type NotificationSettings,
} from '@/modules/messages/notification';
import type {CopiedElementInfo} from './clipboard';

const GAP = 4;

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
 * The "copied" card: the copied elements as a stack of chips. Loading them
 * also fills in each element's chip data and drops elements that no longer
 * exist, so `elementInfo` is only final once it's shown.
 */
export class CopiedElementsNotification extends Notification {
  elementInfo: CopiedElementInfo[];
  loaded = false;
  #onCancel: () => void;

  constructor(
    elementInfo: CopiedElementInfo[],
    onCancel: () => void,
    settings: NotificationSettings = {}
  ) {
    if (!elementInfo.length) {
      throw new Error('No elements are copied.');
    }

    super('notice', '', {
      icon: 'clone-dashed',
      persist: true,
      class: 'copied-elements-notification',
      ...settings,
    });

    this.elementInfo = elementInfo;
    this.#onCancel = onCancel;
    this.message = this.#message();
  }

  override createCloseButton(): HTMLElement {
    return Notification.createIconButton(t('Cancel'));
  }

  override handleCloseButtonClick(): void {
    this.#onCancel();
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
    const stack = await renderChipStack(this.elementInfo);

    // None of them exist anymore
    if (!stack) {
      this.#onCancel();
      return null;
    }

    const remaining = this.elementInfo.filter((element) =>
      stack.chips.has(element)
    );

    for (const element of remaining) {
      element.data = chipData(stack.chips.get(element)!);
    }

    if (remaining.length !== this.elementInfo.length) {
      this.elementInfo = remaining;
      this.updateMessage(this.#message());
    }

    this.loaded = true;

    return stack.container;
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
 * Draws elements' chips stacked with each one behind the last, as the
 * "copied elements" card shows them, leaving out elements that no longer
 * exist. Resolves to `null` when none of them do.
 */
async function renderChipStack<T extends ChipElement>(
  elements: T[]
): Promise<{container: HTMLElement; chips: Map<T, HTMLElement>} | null> {
  const chips = await renderChips(elements);

  if (!chips) {
    return null;
  }

  const container = document.createElement('div');
  container.className = 'copied-elements';

  [...chips.values()].forEach((chip, index) => {
    chip.classList.add('copied-element-chip');
    chip.style.zIndex = String(elements.length - index);

    // Overlay opacities: 0, .4, .55, .7, .85
    if (index !== 0) {
      chip.style.insetBlockStart = `${index * GAP}px`;
      chip.style.insetInlineStart = `${index * GAP}px`;
      chip.style.setProperty(
        '--overlay-opacity',
        String(Math.min(0.25 + 0.15 * index, 1))
      );
    }

    container.append(chip);
  });

  return {container, chips};
}
