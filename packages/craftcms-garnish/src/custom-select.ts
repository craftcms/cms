import {Base} from './base';
import {bod, globals, win} from './globals';
import {ESC_KEY, FX_DURATION, noop} from './constants';
import {getUiLayerManager} from './managers/registry';
import {getElement} from './utils';
import {prefersReducedMotion} from './utils/animation';
import type {ElementInput, GarnishBaseSettings} from './types';

export interface CustomSelectSettings extends GarnishBaseSettings {
  /** Element the menu positions itself against. */
  anchor: Element | null;
  /** @deprecated Use {@link anchor} instead. */
  attachToElement: Element | null;
  /** Gap (px) to keep between the menu and the window edge. */
  windowSpacing: number;
  /** Called with the selected option element. */
  onOptionSelect: (option: HTMLElement) => void;
}

const DEFAULTS: CustomSelectSettings = {
  anchor: null,
  attachToElement: null,
  windowSpacing: 5,
  onOptionSelect: noop,
};

/**
 * CustomSelect — the jQuery-free TypeScript port of the legacy
 * `Garnish.CustomSelect` (the floating listbox menu; `Garnish.Menu` is a
 * deprecated alias). A menu of `<a>`/`.menu-item`/`.menu-option` options that
 * positions itself relative to an anchor, manages `aria-selected`, and fires
 * `onOptionSelect` / `optionselect`.
 *
 * Following the modern `Modal` convention, the `$`-prefixed fields hold native
 * DOM (an `HTMLElement` / `HTMLElement[]`), not jQuery collections.
 */
export class CustomSelect extends Base<CustomSelectSettings> {
  static defaults = DEFAULTS;

  visible = false;

  $container!: HTMLElement;
  $options: HTMLElement[] = [];
  $ariaOptions: HTMLElement[] = [];
  $anchor: HTMLElement | null = null;

  menuId!: string;

  private _observers = new Map<HTMLElement, MutationObserver>();
  private _optionSearchText = new WeakMap<HTMLElement, string>();
  private _anim: Animation | null = null;

  constructor(
    container?: ElementInput,
    settings?: Partial<CustomSelectSettings>
  ) {
    super();
    if (new.target === CustomSelect) {
      this.init(container, settings);
    }
  }

  init(
    container?: ElementInput,
    settings?: Partial<CustomSelectSettings>
  ): void {
    this.setSettings(settings, CustomSelect.defaults);

    this.$container = getElement(container) as HTMLElement;

    this.$options = [];
    this.$ariaOptions = [];

    // Menu List
    this.menuId = 'menu' + this._namespace;
    this.$container.setAttribute('role', 'listbox');
    this.$container.setAttribute('id', this.menuId);

    this.$container
      .querySelectorAll('ul')
      .forEach((ul) => ul.setAttribute('role', 'group'));
    this.addOptions(
      this.$container.querySelectorAll<HTMLElement>('a,.menu-item,.menu-option')
    );

    // Deprecated
    if (this.settings!.attachToElement) {
      this.settings!.anchor = this.settings!.attachToElement;
      console.warn(
        "The 'attachToElement' setting is deprecated. Use 'anchor' instead."
      );
    }

    if (this.settings!.anchor) {
      this.$anchor = getElement(this.settings!.anchor) as HTMLElement;
    }

    // Prevent clicking on the container from hiding the menu
    this.addListener(this.$container, 'mousedown', (ev) => {
      const e = ev as unknown as MouseEvent;
      e.stopPropagation();

      if ((e.target as HTMLElement).nodeName !== 'INPUT') {
        // Prevent this from causing the menu button to blur
        e.preventDefault();
      }
    });
  }

  addOptions(options: ArrayLike<HTMLElement>): void {
    const added = Array.from(options);
    this.$options = this.$options.concat(added);

    added.forEach((option, i) => {
      const index = this.$options.length - added.length + i;
      const li = option.parentElement;
      const ariaOption = li && li.tagName === 'LI' ? li : null;

      option.setAttribute('tabindex', '-1');
      if (!option.getAttribute('id')) {
        option.setAttribute('id', `${this.menuId}-option-${index + 1}`);
      }

      if (ariaOption) {
        ariaOption.setAttribute('role', 'option');
        ariaOption.setAttribute(
          'aria-selected',
          option.classList.contains('sel') ? 'true' : 'false'
        );
        if (!ariaOption.getAttribute('id')) {
          ariaOption.setAttribute(
            'id',
            `${this.menuId}-aria-option-${index + 1}`
          );
        }
        this.$ariaOptions.push(ariaOption);

        // keep aria-selected in-line with .sel
        const observer = new MutationObserver((mutations) => {
          for (const mutation of mutations) {
            if (
              mutation.type === 'attributes' &&
              mutation.attributeName === 'class'
            ) {
              const optionHasHover = this.$options.some((o) =>
                o.classList.contains('hover')
              );
              ariaOption.setAttribute(
                'aria-selected',
                (!optionHasHover && option.classList.contains('sel')) ||
                  option.classList.contains('hover')
                  ? 'true'
                  : 'false'
              );
              break;
            }
          }
        });
        observer.observe(option, {attributes: true});
        this._observers.set(option, observer);
      }
    });

    this.removeAllListeners(added);
    this.addListener(added, 'click', (ev) => {
      this.selectOption((ev as unknown as Event).currentTarget as HTMLElement);
    });
  }

  setPositionRelativeToAnchor(): void {
    if (!this.$anchor) {
      return;
    }

    const windowWidth = win.innerWidth;
    const windowHeight = win.innerHeight;
    const windowScrollLeft = win.scrollX;
    const windowScrollTop = win.scrollY;

    const anchorRect = this.$anchor.getBoundingClientRect();
    const anchorOffset = {
      left: anchorRect.left + windowScrollLeft,
      top: anchorRect.top + windowScrollTop,
    };
    const anchorWidth = anchorRect.width;
    const anchorHeight = anchorRect.height;
    // NB: legacy computes these from the height (a long-standing quirk kept for
    // parity).
    const anchorOffsetBottom = anchorOffset.top + anchorHeight;

    const container = this.$container;
    container.style.minWidth = '0';

    // outerWidth − width: the container's horizontal padding + border.
    const cs = getComputedStyle(container);
    const horizontalChrome =
      parseFloat(cs.paddingLeft) +
      parseFloat(cs.paddingRight) +
      parseFloat(cs.borderLeftWidth) +
      parseFloat(cs.borderRightWidth);
    container.style.minWidth = `${anchorWidth - horizontalChrome}px`;

    const menuWidth = container.getBoundingClientRect().width;
    const menuHeight = container.getBoundingClientRect().height;

    // Is there room for the menu below the anchor?
    const topClearance = anchorOffset.top - windowScrollTop;
    const bottomClearance = windowHeight + windowScrollTop - anchorOffsetBottom;

    if (
      bottomClearance >= menuHeight ||
      (topClearance < menuHeight && bottomClearance >= topClearance)
    ) {
      container.style.top = `${anchorOffsetBottom}px`;
      container.style.maxHeight = `${
        bottomClearance - this.settings!.windowSpacing
      }px`;
    } else {
      container.style.top = `${
        anchorOffset.top -
        Math.min(menuHeight, topClearance - this.settings!.windowSpacing)
      }px`;
      container.style.maxHeight = `${
        topClearance - this.settings!.windowSpacing
      }px`;
    }

    // Figure out how we're aligning it
    let align = container.dataset.align;

    if (align !== 'left' && align !== 'center' && align !== 'right') {
      align = 'left';
    }

    if (align === 'center') {
      this._alignCenter(anchorOffset.left, anchorWidth, menuWidth);
    } else {
      // Figure out which alignments are actually possible
      const rightClearance =
        windowWidth + windowScrollLeft - (anchorOffset.left + menuWidth);
      const leftClearance = anchorOffset.left + anchorHeight - menuWidth;

      if (
        ((align === 'right' && leftClearance >= 0) || rightClearance < 0) &&
        menuWidth < anchorOffset.left + anchorWidth
      ) {
        this._alignRight(
          anchorOffset.left,
          anchorWidth,
          menuWidth,
          windowWidth
        );
      } else {
        this._alignLeft(anchorOffset.left, menuWidth, windowWidth);
      }
    }
  }

  show(): void {
    if (this.visible) {
      return;
    }

    // Move the menu to the end of the DOM
    bod.appendChild(this.$container);

    if (this.$anchor) {
      this.setPositionRelativeToAnchor();
    }

    this._anim?.cancel();
    this.$container.classList.add('visible');
    this.$container.style.opacity = '1';

    const manager = getUiLayerManager();
    manager?.addLayer(this.$container);
    manager?.registerShortcut(ESC_KEY, () => this.hide());

    this.addListener(
      globals.scrollContainer,
      'scroll',
      'setPositionRelativeToAnchor'
    );
    this.addListener(win, 'resize', 'setPositionRelativeToAnchor');

    this.visible = true;
    this.trigger('show');
  }

  hide(): void {
    if (!this.visible) {
      return;
    }

    this.$options.forEach((o) => o.classList.remove('hover'));
    this.$options
      .filter((o) => o.classList.contains('sel'))
      .forEach((o) => {
        const li = o.parentElement;
        if (li && li.tagName === 'LI') {
          li.setAttribute('aria-selected', 'true');
        }
      });

    const finalize = (): void => {
      this.$container.classList.remove('visible');
      this.$container.style.display = '';
      this.$container.style.opacity = '';
      this.$container.remove();
      this._anim = null;
    };

    this._anim?.cancel();
    if (
      prefersReducedMotion() ||
      typeof this.$container.animate !== 'function'
    ) {
      finalize();
    } else {
      const anim = this.$container.animate([{opacity: 1}, {opacity: 0}], {
        duration: FX_DURATION,
        fill: 'forwards',
      });
      this._anim = anim;
      anim.onfinish = finalize;
      anim.oncancel = (): void => {
        this._anim = null;
      };
    }

    getUiLayerManager()?.removeLayer(this.$container);
    this.removeListener(globals.scrollContainer, 'scroll');
    this.removeListener(win, 'resize');
    this.visible = false;
    this.trigger('hide');
  }

  selectOption(option: HTMLElement): void {
    this.settings!.onOptionSelect(option);
    this.trigger('optionselect', {selectedOption: option});
    this.hide();
  }

  /** Search text (lowercased, SVG-stripped) for type-ahead; lazily cached. */
  getOptionSearchText(option: HTMLElement): string {
    let text = this._optionSearchText.get(option);
    if (text === undefined) {
      const clone = option.cloneNode(true) as HTMLElement;
      clone.querySelectorAll('svg').forEach((svg) => svg.remove());
      text = (clone.textContent ?? '').toLowerCase().trimStart();
      this._optionSearchText.set(option, text);
    }
    return text;
  }

  private _alignLeft(
    anchorLeft: number,
    menuWidth: number,
    windowWidth: number
  ): void {
    this.$container.style.left = `${anchorLeft}px`;
    this.$container.style.right = 'auto';

    // if menuWidth is larger than the screen estate we have
    // - set max-width with a slight margin (10)
    if (menuWidth > windowWidth - anchorLeft) {
      this.$container.style.maxWidth = `${windowWidth - anchorLeft - 10}px`;
    }
  }

  private _alignRight(
    anchorLeft: number,
    anchorWidth: number,
    menuWidth: number,
    windowWidth: number
  ): void {
    this.$container.style.right = `${
      windowWidth - (anchorLeft + anchorWidth)
    }px`;
    this.$container.style.left = 'auto';

    // if menuWidth is larger than the screen estate we have
    // - set max-width with a slight margin (10)
    if (menuWidth > anchorLeft + anchorWidth) {
      this.$container.style.maxWidth = `${anchorLeft + anchorWidth - 10}px`;
    }
  }

  private _alignCenter(
    anchorLeft: number,
    anchorWidth: number,
    menuWidth: number
  ): void {
    let left = Math.round(anchorLeft + anchorWidth / 2 - menuWidth / 2);

    if (left < 0) {
      left = 0;
    }

    this.$container.style.left = `${left}px`;
  }

  override destroy(): void {
    this._observers.forEach((observer) => observer.disconnect());
    this._observers.clear();
    super.destroy();
  }
}
