import {Base} from '@craftcms/garnish';
import {fieldToggleData} from './support';

// jQuery survives ONLY at the legacy `.data('fieldtoggle')`/`.data('selectize')`
// seam that still-legacy readers (LinkField, craft-switch's guard) depend on —
// everything else is plain DOM. Mirrors the sortable-checkbox-select precedent.
// `Garnish` (the `activate` custom event) stays a page global via addListener.

/** The jQuery global, if present (the CP always loads it). */
function jq(): any {
  return (window as any).jQuery ?? null;
}

/** A toggle argument: a selector, an element, or an array-like (incl. jQuery). */
type ToggleInput = string | Element | ArrayLike<Element> | null | undefined;

function resolveElement(input: ToggleInput): HTMLElement | null {
  if (input == null) {
    return null;
  }
  if (typeof input === 'string') {
    return document.querySelector<HTMLElement>(input);
  }
  if (input instanceof Element) {
    return input as HTMLElement;
  }
  if (typeof (input as ArrayLike<Element>).length === 'number') {
    return ((input as ArrayLike<Element>)[0] as HTMLElement) ?? null;
  }
  return null;
}

function queryAll(selector: string | null | undefined): HTMLElement[] {
  return selector
    ? Array.from(document.querySelectorAll<HTMLElement>(selector))
    : [];
}

const ANIMATION_MS = 200;

/**
 * FieldToggle — a port of `Craft.FieldToggle` onto `@craftcms/garnish` `Base`.
 * A toggle control (checkbox/radio/switch, select, boolean menu, button, or
 * fieldset) shows/hides target fields (`data-target` / `data-reverse-target` /
 * `data-target-prefix`) based on its value, with a height reveal animation.
 *
 * jQuery-free apart from the documented `.data()` seam; the velocity height
 * animation is reimplemented with the Web Animations API.
 */
export class FieldToggle extends Base {
  toggle: HTMLElement | null = null;
  type: string | null = null;
  targetPrefix: string | null = null;
  targetSelector: string | null = null;
  reverseTargetSelector: string | null = null;

  #target: HTMLElement[] = [];
  #reverseTarget: HTMLElement[] = [];

  constructor(toggle?: ToggleInput) {
    super();
    if (new.target === FieldToggle) {
      this.init(toggle);
    }
  }

  init(toggle: ToggleInput): void {
    this.toggle = resolveElement(toggle);
    if (!this.toggle) {
      return;
    }

    // Is this already a field toggle?
    const existing = fieldToggleData.get(this.toggle);
    if (existing) {
      console.warn('Double-instantiating a field toggle on an element');
      existing.destroy();
    }

    fieldToggleData.set(this.toggle, this);
    // Legacy back-reference (still read by LinkField / craft-switch via jQuery).
    jq()?.(this.toggle).data('fieldtoggle', this);

    this.type = this.getType();

    if (
      this.type === 'select' ||
      this.type === 'fieldset' ||
      this.toggle.hasAttribute('data-target-prefix')
    ) {
      this.targetPrefix = this.toggle.getAttribute('data-target-prefix') || '';
    } else {
      this.targetSelector = this.normalizeTargetSelector(
        this.toggle.getAttribute('data-target')
      );
      this.reverseTargetSelector = this.normalizeTargetSelector(
        this.toggle.getAttribute('data-reverse-target')
      );
    }

    this.findTargets();

    switch (this.type) {
      case 'button': {
        if (this.#isButtonToggle()) {
          const target = this.#target[0];
          if (target) {
            if (!target.id) {
              target.id = `toggle-target-${Math.floor(Math.random() * 1000000)}`;
            }
            this.toggle.setAttribute('aria-controls', target.id);
            this.#updateButtonExpanded();
          }
        }
        this.addListener(this.toggle, 'activate', () => this.onToggleChange());
        break;
      }
      case 'fieldset':
        this.addListener(this.toggle.querySelectorAll('input'), 'change', () =>
          this.onToggleChange()
        );
        break;
      default:
        this.addListener(this.toggle, 'change', () => {
          this.onToggleChange();

          // For radio buttons, refresh the other toggles sharing the name.
          if (this.toggle!.getAttribute('type') === 'radio') {
            const name = this.toggle!.getAttribute('name');
            document
              .querySelectorAll<HTMLElement>(
                `input[type="radio"][name="${name}"]`
              )
              .forEach((radio) => {
                if (radio !== this.toggle) {
                  fieldToggleData.get(radio)?.onToggleChange();
                }
              });
          }
        });
        this.onToggleChange();
    }
  }

  normalizeTargetSelector(selector: string | null): string | null {
    if (selector && !/^[#.]/.test(selector)) {
      return `#${selector}`;
    }
    return selector;
  }

  getType(): string {
    const nodeName = this.toggle!.nodeName;
    const type = this.toggle!.getAttribute('type');
    const role = this.toggle!.getAttribute('role');

    if (
      (nodeName === 'INPUT' && (type === 'checkbox' || type === 'radio')) ||
      role === 'checkbox' ||
      role === 'switch'
    ) {
      return 'checkbox';
    }

    switch (nodeName) {
      case 'SELECT':
        return this.toggle!.hasAttribute('data-boolean-menu')
          ? 'booleanMenu'
          : 'select';
      case 'BUTTON':
      case 'A':
        return 'button';
      default:
        return 'fieldset';
    }
  }

  findTargets(): void {
    if (this.targetPrefix !== null) {
      this.#target = queryAll(
        this.normalizeTargetSelector(
          this.targetPrefix + (this.getToggleVal() || '')
        )
      );
    } else {
      this.#target = queryAll(this.targetSelector);
      this.#reverseTarget = queryAll(this.reverseTargetSelector);
    }
  }

  getToggleVal(): boolean | string | null {
    const toggle = this.toggle as HTMLInputElement & HTMLSelectElement;

    if (this.type === 'checkbox' && this.targetPrefix === null) {
      if (typeof toggle.checked !== 'undefined') {
        return toggle.checked;
      }
      return this.toggle!.getAttribute('aria-checked') === 'true';
    }

    if (this.type === 'booleanMenu') {
      const attr = this.toggle!.getAttribute('data-boolean');
      if (attr !== null) {
        return attr === 'true' ? true : attr === 'false' ? false : !!attr;
      }
      const val = toggle.value;
      return !!val && val !== '0';
    }

    if (this.type === 'fieldset') {
      const checked =
        this.toggle!.querySelector<HTMLInputElement>('input:checked');
      return this.normalizeToggleVal(checked?.value);
    }

    return this.normalizeToggleVal(toggle.value);
  }

  normalizeToggleVal(val: string | null | undefined): string | null {
    if (!val) {
      return null;
    }
    return val.replace(/[^\w]+/g, '-');
  }

  async onToggleChange(): Promise<void> {
    // Is this a selectize input that looks like it was just opened?
    const selectize = jq()?.(this.toggle).data('selectize');
    if (selectize && (this.toggle as HTMLInputElement).value === '') {
      await new Promise((resolve) => setTimeout(resolve, 1));
      if (selectize.isOpen) {
        return;
      }
    }

    if (this.type === 'select' || this.type === 'fieldset') {
      this.hideTarget(this.#target);
      this.findTargets();
      this.showTarget(this.#target);
    } else {
      this.findTargets();

      let show: boolean;
      if (this.type === 'button') {
        show = this.#buttonIsCollapsed();
      } else if (this.type === 'checkbox' && this.targetPrefix !== null) {
        show = (this.toggle as HTMLInputElement).checked;
      } else {
        show = !!this.getToggleVal();
      }

      if (show) {
        this.showTarget(this.#target);
        this.hideTarget(this.#reverseTarget);
      } else {
        this.hideTarget(this.#target);
        this.showTarget(this.#reverseTarget);
      }
    }

    this.trigger('toggleChange');
  }

  showTarget(targets: HTMLElement[]): void {
    if (!targets.length) {
      return;
    }

    // Capture heights before unhiding (a hidden target measures 0).
    const fromHeights = targets.map((target) => target.offsetHeight);

    for (const target of targets) {
      target.classList.remove('hidden');
    }

    if (this.type !== 'select' && this.type !== 'fieldset') {
      if (this.type === 'button') {
        this.toggle!.classList.remove('collapsed');
        this.toggle!.classList.add('expanded');
        if (this.#isButtonToggle()) {
          this.#updateButtonExpanded();
        }
      }

      targets.forEach((target, i) => {
        if (target.nodeName !== 'SPAN') {
          this.#animateOpen(target, fromHeights[i] ?? 0);
        }
      });
    }

    // Let grids etc. inside the revealed container lay out.
    window.dispatchEvent(new Event('resize'));
  }

  hideTarget(targets: HTMLElement[]): void {
    if (!targets.length) {
      return;
    }

    if (this.type === 'select' || this.type === 'fieldset') {
      for (const target of targets) {
        target.classList.add('hidden');
      }
      return;
    }

    if (this.type === 'button') {
      this.toggle!.classList.remove('expanded');
      this.toggle!.classList.add('collapsed');
      if (this.#isButtonToggle()) {
        this.#updateButtonExpanded();
      }
    }

    for (const target of targets) {
      if (target.classList.contains('hidden')) {
        continue;
      }
      if (target.nodeName === 'SPAN') {
        target.classList.add('hidden');
      } else {
        this.#animateClose(target);
      }
    }
  }

  #animateOpen(target: HTMLElement, from: number): void {
    target.style.height = 'auto';
    const to = target.offsetHeight;
    target.style.height = `${from}px`;
    target.style.overflow = 'hidden';

    const animation = target.animate(
      [{height: `${from}px`}, {height: `${to}px`}],
      {duration: ANIMATION_MS, easing: 'ease'}
    );
    animation.onfinish = () => {
      target.style.height = '';
      target.style.overflow = '';
    };
  }

  #animateClose(target: HTMLElement): void {
    const from = target.offsetHeight;
    target.style.overflow = 'hidden';

    const animation = target.animate([{height: `${from}px`}, {height: '0px'}], {
      duration: ANIMATION_MS,
      easing: 'ease',
    });
    animation.onfinish = () => {
      target.classList.add('hidden');
      target.style.height = '';
      target.style.overflow = '';
    };
  }

  #isButtonToggle(): boolean {
    return this.toggle!.nodeName === 'BUTTON';
  }

  #buttonIsCollapsed(): boolean {
    return (
      this.toggle!.classList.contains('collapsed') ||
      !this.toggle!.classList.contains('expanded')
    );
  }

  #updateButtonExpanded(): void {
    this.toggle!.setAttribute(
      'aria-expanded',
      this.#buttonIsCollapsed() ? 'false' : 'true'
    );
  }

  override destroy(): void {
    if (this.toggle) {
      fieldToggleData.delete(this.toggle);
      jq()?.(this.toggle).removeData('fieldtoggle');
    }
    super.destroy();
  }
}
