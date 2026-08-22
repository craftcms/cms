/**
 * Listbox — modern, jQuery-free TypeScript port of Craft's `Craft.Listbox`.
 *
 * A Listbox turns a container of `button` / `[type=button]` / `craft-button`
 * options into a single-select toggle group: exactly one option is "pressed" at
 * a time (`aria-pressed="true"` + the `selectedClass`), clicking an option
 * selects it, and selection changes notify via the `onChange` setting and a
 * `change` event.
 *
 * This is the jQuery-free port of the legacy `Listbox.js`. It replaces every
 * jQuery call with native DOM:
 *
 * - `$(container).find(...)` → `container.querySelectorAll(...)`
 * - `.filter('[aria-pressed=true]')` → an `Array.find`
 * - `.addClass`/`.removeClass`/`.attr` → `classList` / `setAttribute`
 * - `$container.data('listbox', this)` → a module-level `WeakMap`
 *
 * Because this targets Craft 6, the legacy Craft-5 shims are dropped: there is
 * no `[role=option]` / `[aria-selected=true]` normalization — selection is
 * standardized on `button` / `[type=button]` / `craft-button` + `aria-pressed`.
 * (A `craft-button` renders its native `<button>` in shadow DOM, so it's matched
 * as the host element; its clicks still bubble/compose out to the listener.)
 *
 * This class was moved out of the `@craftcms/garnish` package into the app at
 * `resources/js/modules/listbox/`, following the controller-class +
 * self-booting custom element pattern. The `Base` primitive is still imported
 * from `@craftcms/garnish`.
 */

import {Base, type GarnishBaseSettings} from '@craftcms/garnish';
import {containerListboxes} from './support';
import {toHtmlElement} from '@craftcms/garnish/compat';

/** No-op default for the `onChange` setting (native replacement for `$.noop`). */
const noop = (): void => {};

/**
 * Callback invoked when the selection changes, with the newly-selected RAW
 * option element and its index.
 */
type ListboxOnChange = (option: HTMLElement, index: number) => void;

/**
 * Settings accepted by {@link Listbox}. Pass a `Partial<ListboxSettings>` to the
 * constructor; unset keys fall back to {@link Listbox.defaults}.
 */
export interface ListboxSettings extends GarnishBaseSettings {
  /** CSS class applied to the selected option. Default `'active'`. */
  selectedClass: string;
  /** CSS class used for the focused option. Default `'focus'`. */
  focusClass: string;
  /**
   * Callback invoked when the selection changes, with the newly-selected option
   * element and its index. Default no-op.
   */
  onChange: ListboxOnChange;
  /**
   * When `true`, the listbox is non-interactive: options aren't click-bound and
   * are removed from the tab order. Default `false`. (Named `readOnly` for
   * legacy API parity.)
   */
  readOnly: boolean;
}

/**
 * A single-select toggle group — the jQuery-free TypeScript port of Craft's
 * `Listbox`. Discovers its options from a container, tracks the pressed option,
 * and emits a `change` event (and calls the `onChange` setting) on selection.
 *
 * The constructor accepts a container element and/or a settings object, with a
 * legacy-compatible param shift: `new Listbox(settings)` is treated as
 * `new Listbox(null, settings)` when the first argument is a plain object. It
 * also tolerates a jQuery-collection container (legacy callers pass one),
 * unwrapping it to its first native element.
 *
 * @example Modern usage
 * ```ts
 * import {Listbox} from '@/modules/listbox';
 *
 * const el = document.querySelector('.btngroup')!;
 * const listbox = new Listbox(el, {
 *   onChange: (option, index) => console.log('selected', index, option),
 * });
 *
 * listbox.on('change', (ev) => console.log(ev.selectedOptionIndex));
 * // listbox.select(2);  listbox.destroy();
 * ```
 */
export class Listbox extends Base<ListboxSettings> {
  /** Default {@link ListboxSettings}, merged with the per-instance overrides. */
  static readonly defaults: ListboxSettings = {
    selectedClass: 'active',
    focusClass: 'focus',
    onChange: noop,
    readOnly: false,
  };

  // --- Instance state (named with the legacy `$`-prefix for consumer parity) ---

  /** The listbox container element, or `null` until set. */
  $container: HTMLElement | null = null;
  /** The discovered option elements. */
  $options: HTMLElement[] = [];
  /** The currently-selected option, or `null` if none. */
  $selectedOption: HTMLElement | null = null;
  /** The index of the selected option in {@link $options}, or `null`. */
  selectedOptionIndex: number | null = null;

  /**
   * @param container - The listbox container element (or a jQuery collection
   *   wrapping it), or a `Partial<ListboxSettings>` object (param shift: when
   *   the first arg is a plain object and no second arg is given, it is treated
   *   as the settings).
   * @param settings - Optional settings overrides (see {@link ListboxSettings}).
   */
  constructor(
    container?: Element | Partial<ListboxSettings> | null,
    settings?: Partial<ListboxSettings>
  ) {
    super();

    // Param mapping: `new Listbox(settings)` when the first arg is a plain
    // object. A jQuery collection is NOT a plain object, so it falls through to
    // the container branch below and gets unwrapped there.
    const containerArg =
      settings === undefined && isPlainObject(container)
        ? null
        : (container ?? null);
    if (settings === undefined && isPlainObject(container)) {
      settings = container;
    }

    this.setSettings(settings, Listbox.defaults);

    // Unwrap a jQuery-collection container to its first native element. Legacy
    // callers pass `$container`; this class also accepts a raw Element.
    const containerEl = toHtmlElement(containerArg);

    if (containerEl) {
      this.$container = containerEl;

      // Already a listbox? Tear the old one down (double-instantiation guard).
      const existing = containerListboxes.get(containerEl);
      if (existing) {
        console.warn('Double-instantiating a listbox on an element');
        existing.destroy();
      }

      containerListboxes.set(containerEl, this);

      this.$options = Array.from(
        this.$container.querySelectorAll<HTMLElement>(
          'button, [type="button"], craft-button'
        )
      );

      // Is there already a selected option?
      this.$selectedOption =
        this.$options.find(
          (option) => option.getAttribute('aria-pressed') === 'true'
        ) ?? null;

      if (this.$selectedOption) {
        this.selectedOptionIndex = this.$options.indexOf(this.$selectedOption);
      }

      if (!this.settings!.readOnly) {
        this.addListener(this.$options, 'click', (event) => {
          if (
            !(event instanceof Event) ||
            !(event.currentTarget instanceof HTMLElement)
          ) {
            return;
          }
          this.select(this.$options.indexOf(event.currentTarget));
          event.preventDefault();
        });
      } else {
        for (const option of this.$options) {
          option.setAttribute('tabindex', '-1');
        }
      }
    }
  }

  /**
   * Select the option at `index`: clears the previously-selected option, marks
   * the new one pressed (with `selectedClass` + `aria-pressed="true"`), updates
   * {@link selectedOptionIndex}, calls the `onChange` setting, and emits
   * `change`. No-op when `index` is out of range or already selected.
   *
   * @param index - The index of the option to select.
   */
  select(index: number): void {
    if (
      index < 0 ||
      index >= this.$options.length ||
      index === this.selectedOptionIndex
    ) {
      return;
    }

    if (this.$selectedOption) {
      this.$selectedOption.classList.remove(this.settings!.selectedClass);
      this.$selectedOption.setAttribute('aria-pressed', 'false');
      this.$selectedOption.removeAttribute('active');
    }

    const option = this.$options[index]!;
    option.classList.add(this.settings!.selectedClass);
    option.setAttribute('aria-pressed', 'true');
    option.setAttribute('active', '');

    this.$selectedOption = option;
    this.selectedOptionIndex = index;

    this.settings!.onChange(option, index);
    this.trigger('change', {
      $selectedOption: option,
      selectedOptionIndex: index,
    });
  }

  /**
   * Disable the listbox, setting `aria-disabled` on the container (and gating
   * DOM listeners via {@link Base.disable}).
   */
  override disable(): void {
    super.disable();
    this.$container?.setAttribute('aria-disabled', 'true');
  }

  /**
   * Re-enable the listbox, clearing `aria-disabled` from the container (and
   * restoring DOM-listener dispatch via {@link Base.enable}).
   */
  override enable(): void {
    super.enable();
    this.$container?.removeAttribute('aria-disabled');
  }

  /**
   * Destroy the listbox: drop the container→instance mapping, then run the base
   * teardown (emits `destroy`, removes all listeners). Override and call
   * `super.destroy()` to clean up extra state.
   */
  override destroy(): void {
    if (this.$container) {
      containerListboxes.delete(this.$container);
    }
    super.destroy();
  }
}

function isPlainObject(
  val: Element | Partial<ListboxSettings> | null | undefined
): val is Partial<ListboxSettings> {
  return (
    val instanceof Object &&
    !(val instanceof Element) &&
    (Object.getPrototypeOf(val) === Object.prototype ||
      Object.getPrototypeOf(val) === null)
  );
}

export default Listbox;
