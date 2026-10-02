import {Base} from './base';
import {doc} from './globals';
import {
  DOWN_KEY,
  END_KEY,
  HOME_KEY,
  PAGE_DOWN_KEY,
  PAGE_UP_KEY,
  RETURN_KEY,
  SPACE_KEY,
  TAB_KEY,
  UP_KEY,
  noop,
} from './constants';
import {CustomSelect} from './custom-select';
import {
  getElement,
  hasAttr,
  isCtrlKeyPressed,
  isPlainObject,
  isPrimaryClick,
  requestAnimationFrame,
  scrollContainerToElement,
} from './utils';
import type {ElementInput, GarnishBaseSettings} from './types';

export interface MenuBtnSettings extends GarnishBaseSettings {
  /** Element the menu anchors to (defaults to the button). */
  menuAnchor: Element | null;
  /** Called with the selected option element. */
  onOptionSelect: (option: HTMLElement) => void;
}

const DEFAULTS: MenuBtnSettings = {
  menuAnchor: null,
  onOptionSelect: noop,
};

/** Registry backing the legacy `$btn.data('menubtn')` double-instantiation guard. */
const menuBtnRegistry = new WeakMap<Element, MenuBtn>();

/**
 * MenuBtn — the jQuery-free TypeScript port of the legacy `Garnish.MenuBtn`. A
 * trigger button (`role="combobox"`) that owns a {@link CustomSelect} menu,
 * with full keyboard navigation, type-ahead search, and disabled-state syncing.
 *
 * Following the modern `Modal` convention, `$btn` holds a native `HTMLElement`.
 */
export class MenuBtn extends Base<MenuBtnSettings> {
  static defaults = DEFAULTS;

  $btn!: HTMLElement;
  menu!: CustomSelect;
  showingMenu = false;
  #disabled = true;
  observer: MutationObserver | null = null;

  /**
   * Whether the button is disabled. Overrides the base accessor: MenuBtn tracks
   * disabled state via the button's `disabled` attribute (see
   * {@link handleStatusChange}), not the base `_disabled` flag.
   */
  override get disabled(): boolean {
    return this.#disabled;
  }
  searchStr = '';
  clearSearchStrTimeout: ReturnType<typeof setTimeout> | null = null;

  constructor(
    btn?: ElementInput,
    menu?: CustomSelect | Partial<MenuBtnSettings> | null,
    settings?: Partial<MenuBtnSettings>
  ) {
    super();
    if (new.target === MenuBtn) {
      this.init(btn, menu, settings);
    }
  }

  init(
    btn?: ElementInput,
    menu?: CustomSelect | Partial<MenuBtnSettings> | null,
    settings?: Partial<MenuBtnSettings>
  ): void {
    // Param mapping
    if (typeof settings === 'undefined' && isPlainObject(menu)) {
      // (btn, settings)
      settings = menu as Partial<MenuBtnSettings>;
      menu = null;
    }

    this.$btn = getElement(btn) as HTMLElement;

    if (!this.$btn) {
      console.warn('Menu button instantiated without a DOM element.');
      return;
    }

    let $menu: HTMLElement | undefined;
    const menuObj = menu as CustomSelect | null;

    // Is this already a menu button?
    const existing = menuBtnRegistry.get(this.$btn);
    if (existing) {
      // Grab the old MenuBtn's menu container
      if (!menuObj) {
        $menu = existing.menu.$container;
      }

      console.warn('Double-instantiating a menu button on an element');
      existing.destroy();
    } else if (!menuObj) {
      const next = this.$btn.nextElementSibling;
      if (next && next.classList.contains('menu')) {
        $menu = next as HTMLElement;
        next.remove();
      }
    }

    menuBtnRegistry.set(this.$btn, this);

    this.setSettings(settings, MenuBtn.defaults);

    this.menu = menuObj || new CustomSelect($menu);
    this.menu.$anchor = getElement(
      this.settings!.menuAnchor || this.$btn
    ) as HTMLElement;
    this.menu.on('optionselect', (ev) => {
      this.onOptionSelect(
        (ev as unknown as {selectedOption: HTMLElement}).selectedOption
      );
    });
    this.menu.on('hide', () => {
      this.clearSearchStr();
    });
    this.menu.on('show', () => {
      this.clearSearchStr();
    });

    this.$btn.setAttribute('role', 'combobox');
    this.$btn.setAttribute('aria-controls', this.menu.menuId);
    this.$btn.setAttribute('aria-haspopup', 'listbox');
    this.$btn.setAttribute('aria-expanded', 'false');

    // If no label is set on the listbox, set one based on the combobox label
    const comboboxLabel = this.$btn.getAttribute('aria-labelledby');

    if (
      !this.menu.$container.getAttribute('aria-labelledby') &&
      comboboxLabel
    ) {
      this.menu.$container.setAttribute('aria-labelledby', comboboxLabel);
    }

    this.menu.on('hide', () => this.onMenuHide());
    this.addListener(this.$btn, 'mousedown', 'onMouseDown');
    this.addListener(this.$btn, 'keydown', 'onKeyDown');
    this.addListener(this.$btn, 'blur', 'onBlur');

    this.observer = new MutationObserver((mutations) => {
      for (const mutation of mutations) {
        if (
          mutation.type === 'attributes' &&
          mutation.attributeName === 'disabled'
        ) {
          this.handleStatusChange();
          break;
        }
      }
    });

    this.observer.observe(this.$btn, {attributes: true});

    this.handleStatusChange();
  }

  onBlur(): void {
    if (this.showingMenu) {
      requestAnimationFrame(() => {
        if (!this.menu.$container.contains(document.activeElement)) {
          this.hideMenu();
        }
      });
    }
  }

  onKeyDown(ev: KeyboardEvent): void {
    if (isCtrlKeyPressed(ev)) {
      return;
    }

    // Searching for an option?
    if (
      ev.key &&
      (ev.key.match(/^[^ ]$/) || (this.searchStr.length && ev.key === ' '))
    ) {
      // show the menu and set visual focus to the first matching option
      let option: HTMLElement | undefined;

      if (!this.showingMenu) {
        this.showMenu();
        // go with the selected option by default
        option =
          this.menu.$options.find((o) => o.classList.contains('sel')) ??
          this.menu.$options[0];
      }

      // see if there's a matching option
      this.searchStr += ev.key.toLowerCase();
      for (let i = 0; i < this.menu.$options.length; i++) {
        const o = this.menu.$options[i]!;
        if (this.menu.getOptionSearchText(o).startsWith(this.searchStr)) {
          option = o;
          break;
        }
      }

      if (option) {
        this.focusOption(option);
      }

      // update the timeout
      if (this.clearSearchStrTimeout) {
        clearTimeout(this.clearSearchStrTimeout);
      }
      this.clearSearchStrTimeout = setTimeout(() => {
        this.clearSearchStr();
      }, 1000);

      return;
    }

    if (this.showingMenu) {
      switch (ev.keyCode) {
        case RETURN_KEY:
        case SPACE_KEY:
        case TAB_KEY: {
          // select the visually-focused option and close the menu
          if (ev.keyCode !== TAB_KEY) {
            ev.preventDefault();
          }
          const currentOption = this.menu.$options.find((o) =>
            o.classList.contains('hover')
          );
          if (currentOption) {
            currentOption.click();
          } else {
            this.hideMenu();
          }
          break;
        }

        case UP_KEY:
        case PAGE_UP_KEY: {
          // move visual focus up
          ev.preventDefault();
          const dist = ev.keyCode === UP_KEY ? 1 : 10;
          this.moveFocusUp(dist);
          break;
        }

        case DOWN_KEY:
        case PAGE_DOWN_KEY: {
          // move visual focus down
          ev.preventDefault();
          const dist = ev.keyCode === DOWN_KEY ? 1 : 10;
          this.moveFocusDown(dist);
          break;
        }

        case HOME_KEY: {
          // move visual focus to the first option
          ev.preventDefault();
          this.focusFirstOption();
          break;
        }

        case END_KEY: {
          // move visual focus to the last option
          ev.preventDefault();
          this.focusLastOption();
          break;
        }
      }
    } else {
      switch (ev.keyCode) {
        case RETURN_KEY:
        case SPACE_KEY:
        case DOWN_KEY: {
          // show the menu and set visual focus to the selected option
          ev.preventDefault();
          this.showMenu();
          this.focusSelectedOption();
          break;
        }

        case UP_KEY:
        case HOME_KEY: {
          // show the menu and set visual focus to the first option
          ev.preventDefault();
          this.showMenu();
          this.focusFirstOption();
          break;
        }

        case END_KEY: {
          // show the menu and set visual focus to the last option
          ev.preventDefault();
          this.showMenu();
          this.focusLastOption();
          break;
        }
      }
    }
  }

  clearSearchStr(): void {
    this.searchStr = '';
    if (this.clearSearchStrTimeout) {
      clearTimeout(this.clearSearchStrTimeout);
      this.clearSearchStrTimeout = null;
    }
  }

  focusOption(option: HTMLElement): void {
    if (option.classList.contains('hover')) {
      return;
    }

    this.menu.$options.forEach((o) => o.classList.remove('hover'));
    this.menu.$ariaOptions.forEach((o) =>
      o.setAttribute('aria-selected', 'false')
    );

    option.classList.add('hover');
    const li = option.parentElement;
    if (li && li.tagName === 'LI' && li.getAttribute('id')) {
      this.$btn.setAttribute('aria-activedescendant', li.getAttribute('id')!);
    }

    scrollContainerToElement(this.menu.$container, option);
  }

  focusSelectedOption(): void {
    const option = this.menu.$options.find((o) => o.classList.contains('sel'));
    if (option) {
      this.focusOption(option);
    } else {
      this.focusFirstOption();
    }
  }

  focusFirstOption(): void {
    const option = this.menu.$options[0];
    if (option) {
      this.focusOption(option);
    }
  }

  focusLastOption(): void {
    const option = this.menu.$options[this.menu.$options.length - 1];
    if (option) {
      this.focusOption(option);
    }
  }

  moveFocusUp(dist = 1): void {
    const options = this.menu.$options;
    const focused = options.find((o) => o.classList.contains('hover'));
    if (focused) {
      const index = options.indexOf(focused);
      let option = options[Math.max(index - dist, 0)]!;
      while (option.classList.contains('disabled') && index - dist >= 0) {
        dist++;
        option = options[Math.max(index - dist, 0)]!;
      }
      this.focusOption(option);
    } else {
      this.focusFirstOption();
    }
  }

  moveFocusDown(dist = 1): void {
    const options = this.menu.$options;
    const focused = options.find((o) => o.classList.contains('hover'));
    if (focused) {
      const index = options.indexOf(focused);
      let option = options[Math.min(index + dist, options.length - 1)]!;
      while (
        option.classList.contains('disabled') &&
        index + dist <= options.length - 1
      ) {
        dist++;
        option = options[Math.min(index + dist, options.length - 1)]!;
      }
      this.focusOption(option);
    } else {
      this.focusFirstOption();
    }
  }

  onMouseDown(ev: MouseEvent): void {
    if (
      !isPrimaryClick(ev) ||
      (ev.target as HTMLElement).nodeName === 'INPUT'
    ) {
      return;
    }

    ev.preventDefault();

    if (this.showingMenu) {
      this.hideMenu();
    } else {
      this.showMenu();
    }
  }

  showMenu(): void {
    if (this.disabled) {
      return;
    }

    this.menu.show();
    this.$btn.classList.add('active');
    this.$btn.focus();
    this.$btn.setAttribute('aria-expanded', 'true');

    this.showingMenu = true;

    setTimeout(() => {
      this.addListener(doc, 'mousedown', 'onMouseDown');
    }, 1);
  }

  hideMenu(): void {
    this.menu.hide();
  }

  onMenuHide(): void {
    this.$btn.classList.remove('active');
    this.$btn.setAttribute('aria-expanded', 'false');
    this.$btn.removeAttribute('aria-activedescendant');
    this.showingMenu = false;

    this.removeListener(doc, 'mousedown');
  }

  onOptionSelect(option: HTMLElement): void {
    this.settings!.onOptionSelect(option);
    this.trigger('optionSelect', {option});
  }

  override enable(): void {
    if (!this.$btn) {
      return;
    }

    this.$btn.removeAttribute('disabled');
  }

  override disable(): void {
    if (!this.$btn) {
      return;
    }

    this.$btn.setAttribute('disabled', 'disabled');
  }

  handleStatusChange(): void {
    if (!this.$btn) {
      return;
    }

    if (
      hasAttr(this.$btn, 'disabled') ||
      this.$btn.getAttribute('aria-disabled') === 'true'
    ) {
      this.#disabled = true;
      this.$btn.classList.add('disabled');
    } else {
      this.#disabled = false;
      this.$btn.classList.remove('disabled');
    }
  }

  override destroy(): void {
    this.menu.destroy();
    menuBtnRegistry.delete(this.$btn);
    this.observer?.disconnect();
    this.observer = null;
    super.destroy();
  }
}
