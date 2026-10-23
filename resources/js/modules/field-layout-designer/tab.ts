import {Base, HUD} from '@craftcms/garnish';
import {FieldLayoutDesigner} from './field-layout-designer';
import {openLayoutComponentSettings} from './settings-slideout';
import {Element as FldElement} from './element';
import {
  firstFocusableInSiblings,
  fldElementData,
  fldTabData,
  hudData,
  isSidebarTab,
} from './support';
import {type ActionMenuItem} from '@craftcms/ui';
import type {UiValues} from '@/modules/ui/types';
import {createInitialIcon} from '@/common/utils/initialIcon';

declare const Craft: any;
declare const $: any;

/**
 * A single tab within the {@link FieldLayoutDesigner}. Native DOM port of the
 * legacy `Craft.FieldLayoutDesigner.Tab`. jQuery survives only at the Craft
 * seams (`Craft.Grid`, `Craft.Slideout`). The action menu uses the
 * `craft-action-menu` web component.
 */
export class Tab extends Base {
  designer: FieldLayoutDesigner;
  uid: any = null;
  $container: any = null;
  $addBtn: any = null;
  $actionBtn: any = null;
  hud: any = null;
  destroyed = false;

  constructor(designer: FieldLayoutDesigner, $container: any) {
    super();
    this.designer = designer;
    this.$container = $container;
    fldTabData.set(this.$container, this);
    this.uid = this.$container.dataset.uid;

    // New tab?
    if (!this.uid) {
      this.uid = Craft.uuid();
      this.config = {
        uid: this.uid,
        name:
          this.$container.querySelector('.tabs .tab .fld-tab__name')
            ?.textContent ?? '',
        type: this.isSidebar ? 'sidebar' : 'content',
        elements: [],
      };
    }

    if (this.designer.settings!.customizableTabs) {
      this.createMenu();
    }

    this.syncTypeUi();

    // initialize the elements
    const $tabContent = this.$container.querySelector(
      ':scope > .fld-tabcontent'
    );

    this.addListener($tabContent, 'resize', () => {
      this.designer.tabGrid.refreshCols(true);
    });

    this.$addBtn = $tabContent.querySelector('[command="--add-field"]');

    const hud = (this.hud = new HUD(this.$addBtn, {
      hudClass: 'hud fld-library-hud',
      listenToMainResize: false,
      showOnInit: false,
      orientations: ['right', 'bottom', 'left'],
    }));
    // The legacy HUD stored itself via jQuery `.data('hud', this)`; mirror that
    // with the FLD WeakMap so `designer.getActiveHud()` can find it.
    if (
      !(hud.$hud instanceof Element) ||
      !(hud.$trigger instanceof HTMLElement)
    ) {
      throw new Error('Field layout HUD requires a trigger and container.');
    }
    hudData.set(hud.$hud, {
      $trigger: hud.$trigger,
      hide: () => hud.hide(),
    });
    hud.on('show', () => {
      hud.$main!.appendChild(this.designer.$libraryContainer);
      this.designer.libraryPicker?.select(0);
      // Garnish emits `show` before it moves the HUD into place, so focusing
      // normally would scroll the page to wherever the HUD was last (the top,
      // for a new tab's).
      this.designer.$fieldSearch.focus({preventScroll: true});
      this.designer.clearSearch();
      this.designer.$fieldLibrary.scrollTop = 0;
    });
    hud.on('hide', () => {
      this.$addBtn.focus();
    });

    this.addListener(this.$addBtn, 'activate', () => {
      hud.show();
    });

    const $elements = Array.from($tabContent.children).filter(
      (element): element is HTMLElement =>
        element instanceof HTMLElement && element !== this.$addBtn
    );

    for (const el of $elements) {
      this.initElement(el);
    }
  }

  createMenu(): void {
    const $tab = this.$container.querySelector('.tabs .tab');

    const menu = document.createElement('craft-action-menu');
    menu.label = Craft.t('app', 'Actions');
    // Disable the generated (default ellipsis) invoker when read-only.
    menu.disabled = !!this.designer.settings!.readOnly;
    // Anchor the settings slideout (focus return) to the menu element, since the
    // default invoker lives inside the web component's light DOM.
    this.$actionBtn = menu;

    // Provider function — re-evaluated each time the menu opens, so move items
    // reflect the tab's current sibling state (replacing the legacy
    // `on('show')` + `toggleItem` logic).
    menu.actions = (): ActionMenuItem[] => {
      const items: ActionMenuItem[] = [
        {
          label: Craft.t('app', 'Settings'),
          icon: 'gear',
          onClick: () => {
            this.createSettings();
          },
        },
        {type: 'hr'},
      ];

      const hasPrev = !!this.prevSibling;
      const hasNext = !!this.nextSibling;

      if (hasPrev) {
        items.push({
          label:
            Craft.orientation === 'ltr'
              ? Craft.t('app', 'Move to the left')
              : Craft.t('app', 'Move to the right'),
          icon: Craft.orientation === 'ltr' ? 'arrow-left' : 'arrow-right',
          onClick: () => {
            this.moveLeft();
          },
        });
      }

      if (hasNext) {
        items.push({
          label:
            Craft.orientation === 'ltr'
              ? Craft.t('app', 'Move to the right')
              : Craft.t('app', 'Move to the left'),
          icon: Craft.orientation === 'ltr' ? 'arrow-right' : 'arrow-left',
          onClick: () => {
            this.moveRight();
          },
        });
      }

      items.push(
        this.isSidebar
          ? {
              label: Craft.t('app', 'Move to content'),
              icon: 'browser',
              onClick: () => {
                this.setType('content');
              },
            }
          : {
              label: Craft.t('app', 'Move to the sidebar'),
              icon: 'sidebar-flip',
              onClick: () => {
                this.setType('sidebar');
              },
            },
        {type: 'hr'},
        {
          label: Craft.t('app', 'Remove'),
          icon: 'xmark-large',
          variant: 'danger',
          onClick: () => {
            this.destroy();
          },
        }
      );

      return items;
    };

    $tab.appendChild(menu);

    // Line the generated invoker's icon up with the tab's inline-end padding.
    void menu.updateComplete.then(() => {
      menu
        .querySelector(':scope > craft-button[slot="invoker"]')
        ?.setAttribute('flush', 'inline-end');
    });
  }

  private settingsRequestData() {
    return {
      uid: this.uid,
      layoutConfig: this.designer.config,
      elementType: this.designer.settings!.elementType,
    };
  }

  async createSettings(): Promise<void> {
    let data;
    try {
      const response = await Craft.sendActionRequest(
        'POST',
        'fields/render-layout-component-settings',
        {
          data: {
            ...this.settingsRequestData(),
          },
        }
      );
      data = response.data;
    } catch (e: any) {
      Craft.cp.displayError(e?.response?.data?.message);
      throw e;
    }

    await openLayoutComponentSettings(data, {
      title: this.config?.name || Craft.t('app', 'Settings'),
      triggerElement: this.$actionBtn,
      requestData: () => this.settingsRequestData(),
      apply: (settings) => this.applyTabSettings(settings),
    });
  }

  /**
   * Persists the tab's settings and re-renders its label.
   *
   * Rejects on failure so the Vue settings panel can surface the errors
   * against the fields they belong to.
   */
  async applyTabSettings(settings: UiValues): Promise<void> {
    if (!settings.name) {
      const message = Craft.t('app', 'You must specify a tab name.');

      throw Object.assign(new Error(message), {
        name: 'TabNameRequired',
        response: {data: {errors: {name: message}}},
      });
    }

    const config = Object.assign({}, this.config);
    delete config.elements;

    const response = await Craft.sendActionRequest(
      'POST',
      'fields/apply-layout-tab-settings',
      {
        data: {
          ...this.settingsRequestData(),
          config,
          settings,
        },
      }
    );

    this.updateConfig((config) =>
      Object.assign(response.data.config, {elements: config.elements})
    );

    // Preserve the action menu across the label re-render.
    const $label = this.$container.querySelector('.tabs .tab');
    const $menu = $label.querySelector(':scope > craft-action-menu');
    $menu?.remove();
    $label.innerHTML = response.data.labelHtml;
    if ($menu) {
      $label.appendChild($menu);
    }
    this.syncTypeUi();
  }

  get isSidebar(): boolean {
    return isSidebarTab(this.$container);
  }

  /** The previous tab, if it's the same type as this one. */
  get prevSibling(): HTMLElement | null {
    const $prev = this.$container.previousElementSibling;
    return $prev?.matches('.fld-tab') && isSidebarTab($prev) === this.isSidebar
      ? $prev
      : null;
  }

  /** The next tab, if it's the same type as this one. */
  get nextSibling(): HTMLElement | null {
    const $next = this.$container.nextElementSibling;
    return $next?.matches('.fld-tab') && isSidebarTab($next) === this.isSidebar
      ? $next
      : null;
  }

  moveLeft(): void {
    const $prev = this.prevSibling;
    if ($prev) {
      $prev.before(this.$container);
      this.updatePositionInConfig();
    }
  }

  moveRight(): void {
    const $next = this.nextSibling;
    if ($next) {
      $next.after(this.$container);
      this.updatePositionInConfig();
    }
  }

  /**
   * Switches the tab between a content tab and a sidebar tab, moving it to
   * the end of the content tabs or the sidebar tabs.
   */
  setType(type: 'content' | 'sidebar'): void {
    if ((type === 'sidebar') === this.isSidebar) {
      return;
    }

    // The grid only lays out the content column.
    if (type === 'sidebar') {
      this.designer.tabGrid.removeItems($(this.$container));
      this.designer.$sidebarTabContainer.appendChild(this.$container);
    } else {
      this.designer.$contentTabContainer.appendChild(this.$container);
      this.designer.tabGrid.addItems($(this.$container));
    }

    this.syncTypeWithColumn();
    this.updatePositionInConfig();
    this.designer.tabGrid.refreshCols(true);
    this.$actionBtn?.querySelector(':scope > [slot="invoker"]')?.focus();
  }

  /**
   * Makes the tab's type match the column it's in, after it's been moved (or
   * dragged) from one to the other.
   */
  syncTypeWithColumn(): void {
    const type =
      this.$container.parentElement === this.designer.$sidebarTabContainer
        ? 'sidebar'
        : 'content';

    if ((type === 'sidebar') === this.isSidebar) {
      return;
    }

    this.updateConfig((config) => ({...config, type}));
    this.$container.classList.toggle('fld-tab--sidebar', type === 'sidebar');
    // The grid's row height only applies in the content column.
    this.$container.style.removeProperty('height');
    this.syncTypeUi();
  }

  /**
   * Sidebar tabs only show an icon, with the name in a tooltip. A tab without
   * its own icon gets its name's first letter.
   */
  syncTypeUi(): void {
    const $label = this.$container.querySelector('.tabs .tab');
    if (!$label) {
      return;
    }

    $label.querySelector(':scope > .fld-tab__default-icon')?.remove();
    $label.querySelector(':scope > craft-tooltip')?.remove();

    if (!this.isSidebar) {
      return;
    }

    let $icon = $label.querySelector(':scope > .fld-tab__icon');
    if (!$icon) {
      $icon = document.createElement('span');
      $icon.className = 'fld-tab__icon fld-tab__default-icon';
      $icon.append(createInitialIcon(this.config.name ?? ''));
      $label.prepend($icon);
    }

    // On the icon rather than the whole tab, so it stays out of the way of
    // the action menu below it.
    $icon.id ||= `fld-tab-icon-${this.uid}`;
    const $tooltip = document.createElement('craft-tooltip');
    $tooltip.setAttribute('for', $icon.id);
    $tooltip.textContent = this.config.name ?? '';
    $label.appendChild($tooltip);
  }

  initElement($element: any): FldElement {
    return new FldElement(this, $element);
  }

  get index(): number {
    return this.designer.config.tabs.findIndex((c: any) => c.uid === this.uid);
  }

  get config(): any {
    if (!this.uid) {
      throw 'Tab is missing its UID';
    }
    let config = this.designer.config.tabs.find((c: any) => c.uid === this.uid);
    if (!config) {
      config = {
        uid: this.uid,
        elements: [],
      };
      this.config = config;
    }
    return config;
  }

  set config(config: any) {
    if (this.destroyed) {
      return;
    }

    // Is the name changing?
    if (config.name && config.name !== this.config.name) {
      const $name = this.$container.querySelector('.tabs .tab .fld-tab__name');
      if ($name) {
        $name.textContent = config.name;
      }
      const $tooltip = this.$container.querySelector(
        '.tabs .tab > craft-tooltip'
      );
      if ($tooltip) {
        $tooltip.textContent = config.name;
      }
      const $defaultIcon = this.$container.querySelector(
        '.tabs .tab > .fld-tab__default-icon'
      );
      if ($defaultIcon) {
        $defaultIcon.replaceChildren(createInitialIcon(config.name));
      }
    }

    const designerConfig = this.designer.config;
    const index = this.index;
    if (index !== -1) {
      designerConfig.tabs[index] = config;
    } else {
      const newIndex = this.designer.tabElements().indexOf(this.$container);
      designerConfig.tabs.splice(newIndex, 0, config);
    }
    this.designer.config = designerConfig;
  }

  updateConfig(callback: (config: any) => any): void {
    if (this.destroyed) {
      return;
    }

    const config = callback(this.config);
    if (config !== false) {
      this.config = config;
    }
  }

  updatePositionInConfig(): void {
    if (this.destroyed) {
      return;
    }

    this.designer.updateConfig((config) => {
      const tabConfig = this.config;
      const oldIndex = this.index;
      const newIndex = this.designer.tabElements().indexOf(this.$container);
      if (oldIndex !== -1) {
        config.tabs.splice(oldIndex, 1);
      }
      config.tabs.splice(newIndex, 0, tabConfig);
      return config;
    });
  }

  /**
   * Release this tab's controller resources for a designer reboot (host innerHTML
   * swap). Unlike {@link destroy}, this does NOT mutate the config, move focus, or
   * remove DOM — it only disposes what outlives the detached tab subtree: the HUD
   * (lives in `<body>`). Detached-node listeners are
   * released by `super.destroy()` / GC.
   */
  dispose(): void {
    if (this.destroyed) {
      return;
    }

    this.destroyed = true;
    this.hud?.destroy();
    super.destroy();
  }

  override destroy(): void {
    if (this.destroyed) {
      return;
    }

    this.destroyed = true;

    this.designer.updateConfig((config) => {
      const index = this.index;
      if (index === -1) {
        return false;
      }
      config.tabs.splice(index, 1);
      return config;
    });

    // First destroy the tab's elements
    this.$container
      .querySelectorAll('.fld-element')
      .forEach((el: HTMLElement) => fldElementData.get(el)?.destroy());

    // Set focus to the closest tab's first focusable element
    const $focusElement = firstFocusableInSiblings(this.$container);
    if ($focusElement) {
      $focusElement.focus();
    } else if (this.isSidebar) {
      this.designer.$newSidebarTabBtn?.focus();
    } else {
      this.designer.$newTabBtn.focus();
    }

    this.designer.tabGrid.removeItems($(this.$container));
    this.designer.tabDrag!.removeItems(this.$container);
    this.$container.remove();
    this.designer.refreshSelectedFields();

    super.destroy();
  }
}
