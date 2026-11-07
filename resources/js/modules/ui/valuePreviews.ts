/**
 * Previews of copied field values, shown in the "copied" card in place of
 * the value's text: chips for relations, say. Each is drawn again from the
 * stored copy whenever the card is shown, on any page or tab.
 */

import '@craftcms/ui/components/badge/badge';
import '@craftcms/ui/components/icon/icon';
import '@craftcms/ui/components/indicator/indicator';
import {t} from '@craftcms/ui/utilities/translate';
import {iconSvg} from '@/common/craft/icon-svg';
import {highlightCodeBlocks} from '@/common/utils/highlight-code';
import {
  chipRow,
  renderChips,
} from '@/modules/elements/copied-elements-notification';
import type {CopiedFieldValue, CopiedValueItem} from './copiedFieldValue';

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

/** The most lines a code preview highlights, well past what the card shows. */
const CODE_PREVIEW_LINES = 40;

/**
 * Relations, as the chips of the elements they relate, as copied elements
 * show them.
 */
const elementChips: UiValuePreview = async ({props}) => {
  const elements = props?.elements;
  const type = props?.elementType;

  if (!Array.isArray(elements) || typeof type !== 'string') {
    return null;
  }

  const chips = await renderChips(
    elements.flatMap((element) => {
      const {id, siteId, revisionId} = (element ?? {}) as {
        id?: string | number | null;
        siteId?: number | null;
        revisionId?: unknown;
      };

      return id == null ? [] : [{type, id, siteId, revisionId}];
    })
  );

  return chips ? chipRow(chips.values()) : null;
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

/**
 * A color's swatch: a CSS color, or the name of one of the palette's
 * (`red`, say).
 */
function swatch(color: string): HTMLElement {
  const indicator = document.createElement('craft-indicator');
  indicator.setAttribute('fill', color);

  return indicator;
}

/** Badges, each with its label after a swatch or an icon, if it has one. */
function badges(
  entries: {label: string; marker: Element | null}[]
): HTMLElement {
  const preview = document.createElement('div');
  preview.className = 'copied-field-value-badges';
  preview.append(
    ...entries.map(({label, marker}) => {
      const badge = document.createElement('craft-badge');
      badge.setAttribute('fill', 'gray');

      if (marker) {
        marker.slot = 'prefix';
        badge.append(marker);
      } else {
        badge.toggleAttribute('no-prefix', true);
      }

      badge.append(label);

      return badge;
    })
  );

  return preview;
}

/** Colors, as a badge with their swatch and value. */
const color: UiValuePreview = ({value}) =>
  typeof value === 'string' && value
    ? badges([{label: value, marker: swatch(value)}])
    : null;

/** Palette colors, as a badge with their swatch and name, as the palette shows them. */
const paletteColor: UiValuePreview = ({value}) =>
  typeof value === 'string' && value && value !== 'transparent'
    ? badges([
        {
          label: t(value.charAt(0).toUpperCase() + value.slice(1)),
          marker: swatch(value),
        },
      ])
    : null;

/** Icons, as a badge with the icon and its name. */
const icon: UiValuePreview = async ({value}) => {
  const svg = typeof value === 'string' && value ? await iconSvg(value) : null;

  if (!svg) {
    return null;
  }

  // `<craft-icon>` sizes the SVG it's given, and makes it decorative.
  const element = document.createElement('craft-icon');
  element.append(svg);

  return badges([{label: value as string, marker: element}]);
};

/**
 * An item's icon, tinted with its color, or else its color as a dot, as
 * choice controls show them beside their options.
 */
function itemMarker({icon, color}: CopiedValueItem): HTMLElement | null {
  if (icon) {
    const element = document.createElement('craft-icon');
    element.setAttribute('name', icon);

    if (color) {
      element.style.color = color;
    }

    return element;
  }

  return color ? swatch(color) : null;
}

/**
 * Lists, such as several selected options, and options, as a badge for each
 * item, with its icon or color.
 */
const list: UiValuePreview = ({items}) =>
  items?.length
    ? badges(
        items.map((item) => ({label: item.label, marker: itemMarker(item)}))
      )
    : null;

/**
 * Links, as their label above their URL, or address, from the HTML link and
 * the text they were copied to the clipboard as. Links to elements, which
 * go on the clipboard as their labels alone, show their text.
 */
const link: UiValuePreview = ({html, text}) => {
  const template = document.createElement('template');
  template.innerHTML = html ?? '';
  const label = template.content.querySelector('a')?.textContent?.trim();

  if (!label || !text) {
    return null;
  }

  const preview = document.createElement('div');
  preview.className = 'copied-field-value-link';
  const labelLine = document.createElement('div');
  labelLine.textContent = label;
  preview.append(labelLine);

  // A link without a label of its own is labeled with its URL.
  if (label !== text) {
    const urlLine = document.createElement('div');
    urlLine.className = 'copied-field-value-link-url';
    urlLine.textContent = text;
    preview.append(urlLine);
  }

  return preview;
};

/** Code, such as JSON or Markdown, color-coded as code blocks are. */
function code(language: string): UiValuePreview {
  return ({text}) => {
    if (!text.trim()) {
      return null;
    }

    const preview = document.createElement('pre');
    preview.className = 'th-code copied-field-value-code';
    const element = document.createElement('code');
    element.dataset.language = language;
    element.textContent = text
      .split('\n')
      .slice(0, CODE_PREVIEW_LINES)
      .join('\n');
    preview.append(element);
    highlightCodeBlocks(preview);

    return preview;
  };
}

const json = code('json');

const previews = new Map<string, UiValuePreview>([
  ['craft:element-select', elementChips],
  ['craft:table', table],
  ['craft:color', color],
  ['craft:color-select', paletteColor],
  ['craft:icon-picker', icon],
  ['craft:link', link],
  ['craft:markdown', code('markdown')],
  // JSON fields' textareas, which only take values copied from JSON fields
  [
    'craft:textarea',
    (copied) => (copied.valueScope === 'json' ? json(copied) : null),
  ],
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
