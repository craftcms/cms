import type CraftOption from '../components/option/option.js';

/** The options in a listbox, in order, leaving out hidden ones. */
export function listboxOptions(listbox: Element | undefined): CraftOption[] {
  if (!listbox) {
    return [];
  }

  return [...listbox.querySelectorAll<CraftOption>('craft-option')].filter(
    (option) => !option.hidden
  );
}

/** The text an option is known by: its label, without its hint. */
export function optionLabel(option: CraftOption): string {
  return option.textContent?.trim() ?? '';
}

/**
 * Makes `option` the active descendant of `owner`, the element that keeps
 * focus, and scrolls it into view within the listbox.
 */
export function activateOption(
  options: CraftOption[],
  option: CraftOption | undefined,
  owner: HTMLElement | undefined,
  {scroll = true} = {}
): void {
  for (const candidate of options) {
    candidate.active = candidate === option;
  }

  if (option) {
    owner?.setAttribute('aria-activedescendant', option.id);
    if (scroll) {
      option.scrollIntoView?.({block: 'nearest'});
    }
  } else {
    owner?.removeAttribute('aria-activedescendant');
  }
}

/**
 * The option `step` places after `from`. With nothing active, stepping
 * forward lands on the first option and back on the last.
 */
export function stepOption(
  options: CraftOption[],
  from: CraftOption | undefined,
  step: 1 | -1,
  {wrap = true, skipDisabled = false} = {}
): CraftOption | undefined {
  const candidates = skipDisabled
    ? options.filter((option) => !option.disabled || option === from)
    : options;

  if (!candidates.length) {
    return undefined;
  }

  const index = from ? candidates.indexOf(from) : -1;

  if (index === -1) {
    return step === 1 ? candidates[0] : candidates.at(-1);
  }

  const next = index + step;

  if (wrap) {
    return candidates[(next + candidates.length) % candidates.length];
  }

  return candidates[Math.min(Math.max(next, 0), candidates.length - 1)];
}

const TYPEAHEAD_RESET = 1000;

/**
 * Type-to-select for a listbox that owns focus: printable keys build up a
 * query, and the first option whose label starts with it is the match.
 * Repeating one character cycles through the options starting with it.
 */
export class Typeahead {
  #query = '';

  #timeout: ReturnType<typeof setTimeout> | null = null;

  /** The option the key leads to, if the key is a type-ahead key at all. */
  match(
    event: KeyboardEvent,
    options: CraftOption[],
    current: CraftOption | undefined
  ): CraftOption | undefined {
    if (
      event.key.length !== 1 ||
      event.metaKey ||
      event.ctrlKey ||
      event.altKey ||
      (event.key === ' ' && this.#query === '')
    ) {
      return undefined;
    }

    this.#query += event.key.toLowerCase();
    if (this.#timeout !== null) {
      clearTimeout(this.#timeout);
    }
    this.#timeout = setTimeout(() => this.reset(), TYPEAHEAD_RESET);

    const repeated = [...this.#query].every((char) => char === this.#query[0]);
    const query = repeated ? this.#query[0]! : this.#query;
    const start = current ? options.indexOf(current) : -1;
    const ordered = [
      ...options.slice(start + (repeated ? 1 : 0)),
      ...options.slice(0, start + (repeated ? 1 : 0)),
    ];

    return ordered.find(
      (option) =>
        !option.disabled && optionLabel(option).toLowerCase().startsWith(query)
    );
  }

  /** Whether a query is being typed, during which Space is part of it. */
  get typing(): boolean {
    return this.#query !== '';
  }

  reset(): void {
    this.#query = '';
    if (this.#timeout !== null) {
      clearTimeout(this.#timeout);
      this.#timeout = null;
    }
  }
}
