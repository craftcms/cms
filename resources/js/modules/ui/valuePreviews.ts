/**
 * Previews of copied field values, shown in the "copied" card in place of
 * the value's text: chips for relations, say. Each is drawn again from the
 * stored copy whenever the card is shown, on any page or tab.
 */

import {renderChipStack} from '@/modules/elements/copied-elements-notification';
import type {CopiedFieldValue} from './copiedFieldValue';

/**
 * Draws a preview of a value copied from a control drawn by the component
 * it's registered for, or returns `null` to show the value's text instead.
 * The preview is only for looking at: it can't take focus, and screen
 * readers read the value's text.
 */
export type UiValuePreview = (
  copied: Readonly<CopiedFieldValue>
) => Node | null | Promise<Node | null>;

/** The most rows a table preview shows. */
const TABLE_PREVIEW_ROWS = 5;

/** Relations, as the chips of the elements they relate. */
const elementChips: UiValuePreview = async ({props}) => {
  const elements = props?.elements;
  const type = props?.elementType;

  if (!Array.isArray(elements) || typeof type !== 'string') {
    return null;
  }

  const stack = await renderChipStack(
    elements.flatMap((element) => {
      const {id, siteId, revisionId} = (element ?? {}) as {
        id?: string | number | null;
        siteId?: number | null;
        revisionId?: unknown;
      };

      return id == null ? [] : [{type, id, siteId, revisionId}];
    })
  );

  return stack?.container ?? null;
};

/** Tables, as the first rows of the HTML table they were copied to the clipboard as. */
const table: UiValuePreview = ({html}) => {
  if (!html) {
    return null;
  }

  const template = document.createElement('template');
  template.innerHTML = html;
  const preview = template.content.querySelector('table');

  if (!preview) {
    return null;
  }

  preview.className = 'copied-field-value-table';
  preview.querySelector('thead')?.remove();
  [...preview.querySelectorAll('tbody > tr')]
    .slice(TABLE_PREVIEW_ROWS)
    .forEach((row) => row.remove());

  return preview;
};

/** Lists, such as several selected options, as a pill for each item. */
const list: UiValuePreview = ({items}) => {
  if (!items?.length) {
    return null;
  }

  const preview = document.createElement('ul');
  preview.className = 'copied-field-value-list';
  preview.append(
    ...items.map((item) => {
      const pill = document.createElement('li');
      pill.textContent = item;

      return pill;
    })
  );

  return preview;
};

const previews = new Map<string, UiValuePreview>([
  ['craft:element-select', elementChips],
  ['craft:table', table],
]);

/**
 * Lets the controls drawn by `component` preview the values copied from
 * them, replacing any preview registered for it before.
 */
export function registerValuePreview(
  component: string,
  preview: UiValuePreview
): void {
  previews.set(component, preview);
}

/**
 * The preview of a copied value, or `null` when it has none, or it couldn't
 * be drawn, and the card shows the value's text instead. Values from
 * components without a preview of their own are previewed as lists, when
 * they're lists.
 */
export async function renderValuePreview(
  copied: CopiedFieldValue
): Promise<Node | null> {
  const preview = previews.get(copied.component) ?? list;

  try {
    return await preview(copied);
  } catch {
    return null;
  }
}
