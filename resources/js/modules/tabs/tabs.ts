import {Base, type GarnishBaseSettings} from '@craftcms/garnish';
import {resolveElement, type ElementArg} from '@/common/utils/dom';
import {jq} from '@/common/utils/jquery';

declare const Craft: any;

interface TabsSettings extends GarnishBaseSettings {
  handleCtrlClicks: boolean;
}

interface TabsEvent {
  currentTarget: EventTarget | null;
  key?: string;
  originalEvent?: {metaKey?: boolean; ctrlKey?: boolean};
  preventDefault(): void;
}

type TabsModifierEvent = NonNullable<TabsEvent['originalEvent']>;

/** Cross-platform ctrl/⌘ check (Garnish.isCtrlKeyPressed equivalent). */
function isCtrlKeyPressed(ev: TabsModifierEvent | null | undefined): boolean {
  return !!(ev && (ev.metaKey || ev.ctrlKey));
}

/** Nearest preceding/following sibling matching `selector`, or null. */
function siblingMatch(
  el: Element,
  selector: string,
  dir: 'prev' | 'next'
): HTMLElement | null {
  let sib = dir === 'prev' ? el.previousElementSibling : el.nextElementSibling;
  while (sib) {
    if (sib instanceof HTMLElement && sib.matches(selector)) {
      return sib;
    }
    sib = dir === 'prev' ? sib.previousElementSibling : sib.nextElementSibling;
  }
  return null;
}

/** A tab argument: an index, a DOM element, a jQuery object, or a `data-id`. */
type TabArg = number | string | HTMLElement | {0?: HTMLElement} | null;

/**
 * Tabs — a port of `Craft.Tabs` onto `@craftcms/garnish` `Base`. Manages an
 * ARIA tablist: click/keyboard tab selection, roving-tabindex focus, horizontal
 * scroll-into-view, and an overflow "more" menu.
 *
 * jQuery-free logic, with two documented seams: the overflow menu button is the
 * legacy `.disclosureMenu()` plugin (read via `.data('trigger')`), and the
 * `selectTab`/`deselectTab` events carry a jQuery-wrapped `$tab` because every
 * consumer (CP, Preview, matrix-entry, cp-screen-slideout) reads
 * `ev.$tab.attr('href')`. Booted as `new Craft.Tabs(...)`, so exposed on
 * `window.Craft`.
 */
export class Tabs extends Base<TabsSettings> {
  container: HTMLElement | null = null;

  #tablist: HTMLElement | null = null;
  #tabs: HTMLElement[] = [];
  #selectedTab: HTMLElement | null = null;
  #focusableTab: HTMLElement | null = null;

  #menuBtnEl: HTMLElement | null = null;
  // The legacy Garnish.DisclosureMenu instance + its content element (jQuery seam).
  #trigger: any = null;
  #menuEl: HTMLElement | null = null;

  constructor(container?: ElementArg, settings?: Partial<TabsSettings>) {
    super();
    if (new.target === Tabs) {
      this.init(container ?? null, settings);
    }
  }

  init(container: ElementArg, settings?: Partial<TabsSettings>): void {
    this.container = resolveElement(container);
    if (!this.container) {
      return;
    }
    this.setSettings(settings, {handleCtrlClicks: false});

    this.#tablist = this.container.querySelector<HTMLElement>(
      ':scope > [role="tablist"]'
    );
    this.#tabs = this.#tablist
      ? Array.from(
          this.#tablist.querySelectorAll<HTMLElement>(':scope > [role="tab"]')
        )
      : [];
    this.#selectedTab =
      this.#tabs.find((t) => t.classList.contains('sel')) ?? null;
    this.#focusableTab =
      this.#tabs.find((t) => t.getAttribute('tabindex') === '0') ?? null;

    // Overflow menu button — the legacy disclosure-menu jQuery plugin.
    const $ = jq();
    this.#menuBtnEl =
      this.container.querySelector<HTMLElement>(':scope > .menubtn');
    if ($ && this.#menuBtnEl) {
      // SAFETY: CP pages install the disclosureMenu jQuery plugin before Tabs boots.
      const $menuBtn = $(this.#menuBtnEl) as JQuery<HTMLElement> & {
        disclosureMenu(): JQuery<HTMLElement>;
      };
      $menuBtn.disclosureMenu();
      this.#trigger = $menuBtn.data('trigger');
      this.#menuEl = this.#trigger?.$container?.[0] ?? null;
    }

    // Double-instantiation guard + self-storage (kept on jQuery `.data` for BC).
    const existing = $?.(this.container).data('tabs');
    if (existing) {
      console.warn('Double-instantiating a tab manager on an element');
      existing.destroy();
    }
    $?.(this.container).data('tabs', this);

    for (const tab of this.#tabs) {
      const href = tab.getAttribute('href');
      if (href && href.charAt(0) === '#') {
        this.addListener(tab, 'keydown', (ev: any) => {
          if (ev.key === ' ' || ev.key === 'Enter') {
            ev.preventDefault();
            this.selectTab(ev.currentTarget, true);
          }
        });
        this.addListener(tab, 'click', (ev: any) => {
          const h = tab.getAttribute('href');
          if (
            this.settings!.handleCtrlClicks &&
            h?.charAt(0) === '#' &&
            isCtrlKeyPressed(ev)
          ) {
            return;
          }
          ev.preventDefault();
          this.selectTab(ev.currentTarget, true);
        });
      }

      this.addListener(tab, 'keydown', (ev: any) => this.#handleNavKeydown(ev));
    }

    this.updateMenuBtn();
    this.addListener(window, 'resize', () => {
      this.updateMenuBtn();
    });

    for (const option of this.getMenuOptions()) {
      this.addListener(option, 'activate', (ev) => {
        if (!(ev.currentTarget instanceof HTMLElement)) {
          return;
        }
        const el = ev.currentTarget;
        const href = el.getAttribute('href');
        if (this.settings!.handleCtrlClicks && href?.charAt(0) === '#') {
          const originalEvent =
            'originalEvent' in ev && ev.originalEvent instanceof Object
              ? {
                  metaKey:
                    'metaKey' in ev.originalEvent &&
                    ev.originalEvent.metaKey === true,
                  ctrlKey:
                    'ctrlKey' in ev.originalEvent &&
                    ev.originalEvent.ctrlKey === true,
                }
              : undefined;
          if (isCtrlKeyPressed(originalEvent)) {
            return;
          }
          if ('preventDefault' in ev && ev.preventDefault instanceof Function) {
            ev.preventDefault();
          }
        }

        this.selectTab(el.dataset.id ?? null);
        this.#trigger?.hide();
      });
    }
  }

  #handleNavKeydown(ev: TabsEvent): void {
    if (!(ev.currentTarget instanceof HTMLElement)) {
      return;
    }
    const current = ev.currentTarget;
    let tab: HTMLElement | null = null;

    if (
      (ev.key === 'ArrowLeft' || ev.key === 'ArrowRight') &&
      this.#tablist?.contains(current)
    ) {
      const goPrev =
        ev.key === (Craft.orientation === 'ltr' ? 'ArrowLeft' : 'ArrowRight');
      if (goPrev) {
        tab =
          siblingMatch(current, '[role="tab"]:not(.hidden)', 'prev') ??
          this.#lastTab();
      } else {
        tab =
          siblingMatch(current, '[role="tab"]:not(.hidden)', 'next') ??
          this.#firstTab();
      }
    } else if (ev.key === 'Home' || ev.key === 'End') {
      tab = ev.key === 'Home' ? this.#firstTab() : this.#lastTab();
    }

    if (tab) {
      ev.preventDefault();
      this.makeTabFocusable(tab);
      tab.focus();
      this.scrollToTab(tab);
    }
  }

  getMenuOptions(): HTMLElement[] {
    return this.#menuEl
      ? Array.from(this.#menuEl.querySelectorAll<HTMLElement>('a'))
      : [];
  }

  getSelectedTabIndex(): number {
    return this.#selectedTab ? this.#tabs.indexOf(this.#selectedTab) : -1;
  }

  selectTab(tab: TabArg, focusTab = true): void {
    const tabEl = this.#getTab(tab);
    if (!tabEl || tabEl === this.#selectedTab) {
      return;
    }

    this.deselectTab();
    this.#selectedTab = tabEl;
    tabEl.classList.add('sel');
    tabEl.setAttribute('aria-selected', 'true');
    this.makeTabFocusable(tabEl);

    if (focusTab) {
      tabEl.focus();
    }

    this.scrollToTab(tabEl);

    const id = tabEl.dataset.id;
    for (const option of this.getMenuOptions()) {
      option.classList.remove('sel');
      option.removeAttribute('aria-current');
      if (option.dataset.id === id) {
        option.classList.add('sel');
        option.setAttribute('aria-current', 'true');
      }
    }

    this.trigger('selectTab', {$tab: jq()?.(tabEl)});

    document.getElementById('content')?.dispatchEvent(new Event('scroll'));

    const slideoutContainer = tabEl.closest('.slideout-container');
    slideoutContainer
      ?.querySelector('.so-content')
      ?.dispatchEvent(new Event('scroll'));
  }

  deselectTab(): void {
    const tabEl = this.#selectedTab;
    if (tabEl) {
      tabEl.classList.remove('sel');
      tabEl.setAttribute('aria-selected', 'false');
    }
    this.#selectedTab = null;

    const $ = jq();
    this.trigger('deselectTab', {$tab: $ && tabEl ? $(tabEl) : undefined});
  }

  makeTabFocusable(tab: TabArg): void {
    const tabEl = this.#getTab(tab);
    if (!tabEl || tabEl === this.#focusableTab) {
      return;
    }

    this.#focusableTab?.setAttribute('tabindex', '-1');
    this.#focusableTab = tabEl;
    tabEl.setAttribute('tabindex', '0');
  }

  scrollToTab(tab: TabArg): void {
    const tabEl = this.#getTab(tab);
    if (!tabEl || !this.#tablist) {
      return;
    }

    const scrollLeft = this.#tablist.scrollLeft;
    const elemScrollOffset =
      tabEl.getBoundingClientRect().left -
      this.#tablist.getBoundingClientRect().left;
    let targetScrollLeft: number | false = false;

    // Hidden on the left?
    if (elemScrollOffset < 0) {
      targetScrollLeft = scrollLeft + elemScrollOffset - 24;
    } else {
      const tabWidth = tabEl.offsetWidth;
      const ulWidth = this.#tablist.clientWidth;

      // Hidden on the right?
      if (elemScrollOffset + tabWidth > ulWidth) {
        targetScrollLeft =
          scrollLeft + (elemScrollOffset - (ulWidth - tabWidth)) + 24;
      }
    }

    if (targetScrollLeft !== false) {
      this.#tablist.scrollLeft = targetScrollLeft;
    }
  }

  updateMenuBtn(): void {
    if (!this.#tablist || !this.container) {
      return;
    }

    if (
      Math.floor(this.#tablist.scrollWidth - 48) > this.container.clientWidth
    ) {
      this.#tablist.classList.add('scrollable');
      this.#menuBtnEl?.classList.remove('hidden');
    } else {
      this.#tablist.classList.remove('scrollable');
      this.#menuBtnEl?.classList.add('hidden');
    }
  }

  #firstTab(): HTMLElement | null {
    return this.#tabs[0] ?? null;
  }

  #lastTab(): HTMLElement | null {
    return this.#tabs[this.#tabs.length - 1] ?? null;
  }

  #getTab(tab: TabArg): HTMLElement | null {
    if (Number.isInteger(tab)) {
      return this.#tabs[Number(tab)] ?? null;
    }

    if (tab instanceof HTMLElement) {
      return tab;
    }

    // jQuery object / array-like
    if (tab instanceof Object && tab[0] instanceof HTMLElement) {
      return tab[0];
    }

    if (Object(tab).constructor !== String) {
      throw 'Invalid tab ID';
    }

    const tabId = String(tab);
    const found = this.#tabs.find((t) => t.dataset.id === tabId);
    if (!found) {
      throw `Invalid tab ID: ${tabId}`;
    }
    return found;
  }

  override destroy(): void {
    const $ = jq();
    if ($ && this.container) {
      $(this.container).removeData('tabs');
    }
    super.destroy();
  }
}
