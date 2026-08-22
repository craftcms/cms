import {Base, HUD} from '@craftcms/garnish';
import {FieldLayoutDesigner} from './field-layout-designer';
import {
  canUseVueSlideout,
  openLayoutComponentSettings,
} from './settings-slideout';
import {Element as FldElement} from './element';
import {
  firstFocusableInSiblings,
  fldElementData,
  fldTabData,
  hudData,
} from './support';
import {type ActionMenuItem} from '@craftcms/ui';
import type {FormValues} from '@/modules/forms/types';

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
  slideout: any = null;
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
        elements: [],
      };
    }

    if (this.designer.settings!.customizableTabs) {
      this.createMenu();
    }

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
      this.designer.$fieldSearch.focus();
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

      const prev = this.$container.previousElementSibling;
      const next = this.$container.nextElementSibling;
      const hasPrev = !!(prev && prev.matches('.fld-tab'));
      const hasNext = !!(next && next.matches('.fld-tab'));

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
        {type: 'hr'},
        {
          label: Craft.t('app', 'Remove'),
          icon: 'xmark',
          variant: 'danger',
          onClick: () => {
            this.destroy();
          },
        }
      );

      return items;
    };

    $tab.appendChild(menu);
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

    if (canUseVueSlideout()) {
      await openLayoutComponentSettings(data, {
        title: this.config?.name || Craft.t('app', 'Settings'),
        triggerElement: this.$actionBtn,
        requestData: () => this.settingsRequestData(),
        apply: (settings) => this.applyTabSettings(settings),
      });

      return;
    }

    this.slideout = await FieldLayoutDesigner.createSlideout(data, {
      triggerElement: this.$actionBtn,
      requestData: () => this.settingsRequestData(),
    });

    // slideout.$container is a Craft jQuery object; bind on the native form.
    this.addListener(this.slideout.$container[0], 'submit', (ev: any) => {
      ev.preventDefault();
      this.applySettings();
    });
    this.slideout.on('close', () => {
      this.slideout.destroy();
      this.slideout = null;
    });
  }

  applySettings(): void {
    const $container = this.slideout.$container[0];
    const settingsForm = this.slideout.settingsForm;
    const settings = settingsForm?.currentValues() ?? {};

    // update the UI
    const $submitBtn = $container.querySelector('button[type=submit]');
    $submitBtn?.classList.add('loading');

    this.applyTabSettings(settings)
      .catch((e: any) => {
        Craft.cp.displayError(
          e?.name === 'TabNameRequired' ? e.message : undefined
        );
      })
      .finally(() => {
        $submitBtn?.classList.remove('loading');
        this.slideout?.close();
      });
  }

  /**
   * Persists the tab's settings and re-renders its label.
   *
   * Rejects on failure so the Vue settings panel can surface the errors
   * against the fields they belong to.
   */
  async applyTabSettings(settings: FormValues): Promise<void> {
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
  }

  moveLeft(): void {
    const $prev = this.$container.previousElementSibling;
    if ($prev && $prev.matches('.fld-tab')) {
      $prev.before(this.$container);
      this.updatePositionInConfig();
    }
  }

  moveRight(): void {
    const $next = this.$container.nextElementSibling;
    if ($next && $next.matches('.fld-tab')) {
      $next.after(this.$container);
      this.updatePositionInConfig();
    }
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
    }

    const designerConfig = this.designer.config;
    const index = this.index;
    if (index !== -1) {
      designerConfig.tabs[index] = config;
    } else {
      const siblings = Array.from(
        this.$container.parentElement.querySelectorAll(':scope > .fld-tab')
      );
      const newIndex = siblings.indexOf(this.$container);
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
      const siblings = Array.from(
        this.$container.parentElement.querySelectorAll(':scope > .fld-tab')
      );
      const newIndex = siblings.indexOf(this.$container);
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
   * (lives in `<body>`) and any open settings slideout. Detached-node listeners are
   * released by `super.destroy()` / GC.
   */
  dispose(): void {
    if (this.destroyed) {
      return;
    }

    this.destroyed = true;
    this.hud?.destroy();
    this.slideout?.destroy?.();
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
