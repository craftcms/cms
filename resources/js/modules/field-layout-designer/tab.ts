import {Base} from '@craftcms/garnish';
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
import {isRtl} from '@/common/craft/direction';
import {type ActionMenuItem} from '@craftcms/ui';
import type {UiValues} from '@/modules/ui/types';
import {createInitialIcon} from '@/common/utils/initialIcon';
import {uuid} from '@/common/craft/strings';
import {t} from '@craftcms/ui/utilities/translate';
import {actionClient} from '@craftcms/ui/utilities/api/actionClient';
import {showError} from '@/modules/messages/messages';

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
  hud: HTMLElementTagNameMap['craft-popover'] | null = null;
  destroyed = false;

  constructor(designer: FieldLayoutDesigner, $container: any) {
    super();
    this.designer = designer;
    this.$container = $container;
    fldTabData.set(this.$container, this);
    this.uid = this.$container.dataset.uid;

    // New tab?
    if (!this.uid) {
      this.uid = uuid();
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
      this.designer.tabGrid?.refreshCols(true);
    });

    this.$addBtn = $tabContent.querySelector('[command="--add-field"]');

    // The library opens in a popover anchored to the button. It sits beside the tabs rather than in this one,
    // so the designer's lookups of the fields in its tabs don't find the library's, and it stays inside the
    // page's main landmark. The library itself is shared, and moves into whichever tab's popover opens.
    const popover = (this.hud = document.createElement('craft-popover'));
    popover.classList.add('fld-library-hud');
    popover.anchor = this.$addBtn;
    popover.placement = isRtl() ? 'left-start' : 'right-start';
    const $popoverContent = document.createElement('div');
    $popoverContent.slot = 'content';
    $popoverContent.className = 'fld-library-hud__content';
    popover.appendChild($popoverContent);
    this.designer.$innerContainer.appendChild(popover);
    // `getActiveHud()` finds the popover holding the library through this.
    hudData.set(popover, {
      $trigger: this.$addBtn,
      hide: () => void popover.hide(),
    });
    popover.addEventListener('craft-show', (ev) => {
      if (ev.target !== popover) {
        return;
      }
      $popoverContent.appendChild(this.designer.$libraryContainer);
      this.designer.libraryPicker?.select(0);
      this.designer.clearSearch();
    });
    popover.addEventListener('craft-after-show', (ev) => {
      if (ev.target !== popover) {
        return;
      }
      this.designer.$fieldLibrary.scrollTop = 0;
      this.designer.$fieldSearch.focus({preventScroll: true});
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
    menu.label = t('Actions');
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
          label: t('Settings'),
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
          label: isRtl() ? t('Move to the right') : t('Move to the left'),
          icon: isRtl() ? 'arrow-right' : 'arrow-left',
          onClick: () => {
            this.moveLeft();
          },
        });
      }

      if (hasNext) {
        items.push({
          label: isRtl() ? t('Move to the left') : t('Move to the right'),
          icon: isRtl() ? 'arrow-left' : 'arrow-right',
          onClick: () => {
            this.moveRight();
          },
        });
      }

      items.push(
        this.isSidebar
          ? {
              label: t('Move to content'),
              icon: 'browser',
              onClick: () => {
                this.setType('content');
              },
            }
          : {
              label: t('Move to the sidebar'),
              icon: 'sidebar-flip',
              onClick: () => {
                this.setType('sidebar');
              },
            },
        {type: 'hr'},
        {
          label: t('Remove'),
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
      const response = await actionClient.post(
        'fields/render-layout-component-settings',
        {
          ...this.settingsRequestData(),
        }
      );
      data = response.data;
    } catch (e: any) {
      showError(e?.response?.data?.message);
      throw e;
    }

    await openLayoutComponentSettings(data, {
      title: this.config?.name || t('Settings'),
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
      const message = t('You must specify a tab name.');

      throw Object.assign(new Error(message), {
        name: 'TabNameRequired',
        response: {data: {errors: {name: message}}},
      });
    }

    const config = Object.assign({}, this.config);
    delete config.elements;

    const response = await actionClient.post(
      'fields/apply-layout-tab-settings',
      {
        ...this.settingsRequestData(),
        config,
        settings,
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
      this.designer.tabGrid?.removeItems(this.$container);
      this.designer.$sidebarTabContainer.appendChild(this.$container);
    } else {
      this.designer.$contentTabContainer.appendChild(this.$container);
      this.designer.tabGrid?.addItems(this.$container);
    }

    this.syncTypeWithColumn();
    this.updatePositionInConfig();
    this.designer.tabGrid?.refreshCols(true);
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
   * remove DOM, except the library popover, which sits outside the tab. Detached-node
   * listeners are released by `super.destroy()` / GC.
   */
  dispose(): void {
    if (this.destroyed) {
      return;
    }

    this.destroyed = true;
    this.hud?.remove();
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

    this.designer.tabGrid?.removeItems(this.$container);
    this.designer.tabDrag!.removeItems(this.$container);
    this.$container.remove();
    this.hud?.remove();
    this.designer.refreshSelectedFields();

    super.destroy();
  }
}
