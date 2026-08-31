import {Base, type GarnishBaseSettings} from '@craftcms/garnish';

export interface BaseInputGeneratorSettings extends GarnishBaseSettings {
  /** Regenerate the target even while it's hidden. */
  updateWhenHidden: boolean;
  /** Prepended to every generated value. */
  prefix: string;
  /** Appended to every generated value. */
  suffix: string;
  /** Allow the generated value to start with a non-alpha character. */
  allowNonAlphaStart?: boolean;
  /** Character map passed to `asciiString` (slug generation). */
  charMap?: Record<string, string> | null;
}

/** A form control this generator can read from / write to. */
type TextControl = HTMLInputElement | HTMLTextAreaElement;

/**
 * A source/target argument: a CSS selector, an element, or an array-like of
 * elements (a NodeList, an array, or — from legacy callers — a jQuery object).
 */
export type GeneratorInput =
  | string
  | Element
  | ArrayLike<Element>
  | null
  | undefined;

/**
 * Resolve a selector / element / array-like into text controls, without
 * depending on jQuery. jQuery objects are array-like (numeric `length` +
 * indices), so legacy `new Craft.HandleGenerator($name, $handle)` callers keep
 * working through the `ArrayLike` branch.
 */
function resolveControls(input: GeneratorInput): TextControl[] {
  if (input == null) {
    return [];
  }

  let candidates: Element[];
  if (input instanceof Element) {
    candidates = [input];
  } else if (input instanceof Object) {
    candidates = Array.from(input);
  } else {
    candidates = Array.from(document.querySelectorAll(String(input)));
  }

  return candidates.filter(
    (el): el is TextControl =>
      el instanceof HTMLInputElement || el instanceof HTMLTextAreaElement
  );
}

/** jQuery `:visible` equivalent — does the element take up space in layout. */
function isVisible(el: HTMLElement): boolean {
  return !!(el.offsetWidth || el.offsetHeight || el.getClientRects().length);
}

/** Select the whole value (the jQuery-free port of `Craft.selectFullValue`). */
function selectFullValue(el: TextControl): void {
  const val = el.value;
  // `* 2` mirrors the legacy helper; over-selecting is clamped by the browser.
  el.setSelectionRange(0, val.length * 2);
}

/**
 * Input generator — a port of `Craft.BaseInputGenerator` onto `@craftcms/garnish`
 * `Base`. Watches a source input and writes a generated value into a target
 * input as the user types, until the target is edited directly (or on form
 * submit). Subclasses override {@link generateTargetValue}.
 *
 * This is the **DOM** orchestrator (no jQuery), for the legacy `{% js %}`
 * `new Craft.HandleGenerator(...)` boots on not-yet-migrated Twig pages. Vue
 * pages use the `useInputGenerator` composable
 * (`resources/js/common/composables`) instead — the reactive counterpart, which
 * can't drive raw DOM inputs. Both share the same transform functions from
 * `@craftcms/ui` (`toHandle` / `toUriFormat` / …), so the two paths stay in sync.
 *
 * Setup lives in {@link init}, invoked from the constructor only for the leaf
 * class (`new.target` guard) — the shared port contract, and what lets
 * {@link DynamicGenerator} assign its callback before init runs.
 */
export class BaseInputGenerator extends Base<BaseInputGeneratorSettings> {
  static defaults: BaseInputGeneratorSettings = {
    updateWhenHidden: false,
    prefix: '',
    suffix: '',
  };

  sourceControls: TextControl[] = [];
  targetControls: TextControl[] = [];
  form: HTMLFormElement | null = null;
  listening = false;
  sourceVal: string | undefined = undefined;

  constructor(
    source?: GeneratorInput,
    target?: GeneratorInput,
    settings?: Partial<BaseInputGeneratorSettings>
  ) {
    super();
    if (new.target === BaseInputGenerator) {
      this.init(source, target, settings);
    }
  }

  init(
    source: GeneratorInput,
    target: GeneratorInput,
    settings?: Partial<BaseInputGeneratorSettings>
  ): void {
    this.sourceControls = resolveControls(source);
    this.targetControls = resolveControls(target);
    this.form = this.sourceControls[0]?.closest('form') ?? null;

    this.setSettings(settings, BaseInputGenerator.defaults);

    this.startListening();
  }

  setNewSource(source: GeneratorInput): void {
    const listening = this.listening;
    this.stopListening();

    this.sourceControls = resolveControls(source);

    if (listening) {
      this.startListening();
    }
  }

  startListening(): void {
    if (this.listening) {
      return;
    }

    this.listening = true;
    this.sourceVal = this.sourceControls[0]?.value;

    this.addListener(this.sourceControls, 'input', () =>
      this.onSourceTextChange()
    );
    this.addListener(this.targetControls, 'input', () =>
      this.onTargetTextChange()
    );
    this.addListener(this.form, 'submit', () => this.updateTarget());
  }

  stopListening(): void {
    if (!this.listening) {
      return;
    }

    this.listening = false;

    this.removeAllListeners(this.sourceControls);
    this.removeAllListeners(this.targetControls);
    this.removeAllListeners(this.form);
  }

  onSourceTextChange(): void {
    const val = this.sourceControls[0]?.value;
    if (this.sourceVal !== (this.sourceVal = val)) {
      this.updateTarget();
    }
  }

  onTargetTextChange(): void {
    if (this.targetControls[0] === document.activeElement) {
      this.stopListening();
    }
  }

  updateTarget(): void {
    if (
      !this.targetControls.some(isVisible) &&
      this.settings!.updateWhenHidden === false
    ) {
      return;
    }

    const sourceVal = this.sourceControls[0]?.value;

    if (sourceVal === undefined) {
      // The source input may not exist anymore
      return;
    }

    let targetVal = this.generateTargetValue(sourceVal);
    if (targetVal) {
      targetVal = `${this.settings!.prefix}${targetVal}${this.settings!.suffix}`;
    }

    for (const el of this.targetControls) {
      el.value = targetVal;
    }

    for (const el of this.targetControls) {
      el.dispatchEvent(
        new InputEvent('input', {
          inputType: 'insertText',
        })
      );
      el.dispatchEvent(new Event('input'));
    }

    // If the target already has focus, select its whole value to mimic the
    // behavior if the value had already been generated and they just tabbed in.
    const focused = this.targetControls.find(
      (el) => el === document.activeElement
    );
    if (focused) {
      selectFullValue(focused);
    }
  }

  generateTargetValue(sourceVal: string): string {
    return sourceVal;
  }

  override destroy(): void {
    this.stopListening();
    super.destroy();
  }
}
