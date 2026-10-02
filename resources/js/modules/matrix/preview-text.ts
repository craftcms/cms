import {getInputPostVal} from '@craftcms/garnish';

/**
 * A folded-up block's summary of its own field values, one entry per field.
 *
 * Craft 5 built this off the inputs rather than the values, so it reads what
 * the user last typed rather than what was last saved — and it needs no
 * per-field preview logic on the server. Both stacks put their controls in the
 * light DOM, so one pass over the block's fields serves the Twig markup, the
 * Form Control's HTML, and the Vue control alike.
 *
 * Used only when the block has no UI label; that's the better summary when
 * there is one.
 */
const FIELD_SELECTOR = 'craft-field, .field';
const INPUT_SELECTOR = 'select, input:not([type="hidden"]), textarea, .label';

export function blockPreviewParts(container: HTMLElement): string[] {
  const parts: string[] = [];

  for (const field of container.querySelectorAll<HTMLElement>(FIELD_SELECTOR)) {
    // The block's own fields only. A nested Matrix or content block brings
    // fields of its own, and those belong to their own block's summary.
    if (field.parentElement?.closest(FIELD_SELECTOR)) {
      continue;
    }

    const values: string[] = [];

    for (const input of field.querySelectorAll<HTMLElement>(INPUT_SELECTOR)) {
      if (input.closest(FIELD_SELECTOR) !== field) {
        continue;
      }

      const text = inputText(input);

      if (text) {
        values.push(text);
      }
    }

    if (values.length) {
      parts.push(values.join(', '));
    }
  }

  return parts;
}

function inputText(input: HTMLElement): string {
  if (input.classList.contains('label')) {
    return switchedOff(input) ? '' : (input.textContent ?? '').trim();
  }

  const value = inputValue(input);

  // No entity decoding on the way out, unlike Craft 5's `Craft.getText()`:
  // every value here is read through a DOM property, which is already text.
  return (Array.isArray(value) ? value.join(', ') : (value ?? '')).trim();
}

/**
 * Whether the label belongs to the side of a switch that isn't on, or to a
 * button that isn't pressed — either way it says nothing about the value.
 */
function switchedOff(label: HTMLElement): boolean {
  const lightswitch = label.closest('.lightswitch');

  if (
    lightswitch &&
    lightswitch.classList.contains('on') !== label.classList.contains('on')
  ) {
    return true;
  }

  return !!label.closest('button[aria-pressed=false]');
}

function inputValue(input: HTMLElement): string | string[] | null {
  if (input instanceof HTMLSelectElement) {
    return [...input.selectedOptions].map((option) => option.text);
  }

  if (
    input instanceof HTMLInputElement &&
    (input.type === 'checkbox' || input.type === 'radio')
  ) {
    if (!input.checked) {
      return null;
    }

    const label = input.id
      ? document.querySelector(`label[for="${CSS.escape(input.id)}"]`)
      : null;

    return label?.textContent ?? null;
  }

  if (!(input instanceof HTMLInputElement)) {
    return input instanceof HTMLTextAreaElement ? input.value : null;
  }

  const value = getInputPostVal(input);

  return Array.isArray(value)
    ? value.map(String)
    : value == null
      ? null
      : String(value);
}
