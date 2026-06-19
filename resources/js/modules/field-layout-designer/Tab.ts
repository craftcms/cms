import {Base, HUD} from '@craftcms/garnish';
import {FieldLayoutDesigner} from './FieldLayoutDesigner';
import {Element as FldElement} from './Element';
import {
  fldTabData,
  fldElementData,
  hudData,
  firstFocusableInSiblings,
} from './support';

declare const Craft: any;
declare const $: any;

/**
 * A single tab within the {@link FieldLayoutDesigner}. Native DOM port of the
 * legacy `Craft.FieldLayoutDesigner.Tab`. jQuery survives only at the Craft
 * seams (`Craft.Grid`, the `.disclosureMenu()` plugin, `Craft.Slideout`).
 */
export class Tab extends Base {
  designer: FieldLayoutDesigner;
  uid: any = null;
  $container: any = null;
  $addBtn: any = null;
  $actionBtn: any = null;
  slideout: any = null;
  settingsNamespace: any = null;
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

    this.$addBtn = $tabContent.querySelector(':scope > .fld-add-btn');

    const hud = new HUD(this.$addBtn, {
      hudClass: 'hud fld-library-hud',
      listenToMainResize: false,
      showOnInit: false,
      orientations: ['right', 'bottom', 'left'],
    });
    // The legacy HUD stored itself via jQuery `.data('hud', this)`; mirror that
    // with the FLD WeakMap so `designer.getActiveHud()` can find it.
    hudData.set(hud.$hud!, hud);
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

    const $elements = (
      Array.from($tabContent.children) as HTMLElement[]
    ).filter((el) => el !== this.$addBtn);

    for (const el of $elements) {
      this.initElement(el);
    }
  }

  createMenu(): void {
    const $tab = this.$container.querySelector('.tabs .tab');
    const menuId = `actionmenu${Math.floor(Math.random() * 1000000)}`;
    const $btn = document.createElement('button');
    $btn.setAttribute('type', 'button');
    $btn.className = 'btn action-btn';
    $btn.setAttribute('data-disclosure-trigger', 'true');
    $btn.setAttribute('aria-controls', menuId);
    $btn.setAttribute('aria-haspopup', 'true');
    $btn.setAttribute('aria-label', Craft.t('app', 'Actions'));
    $btn.setAttribute('title', Craft.t('app', 'Actions'));
    if (this.designer.settings!.readOnly) {
      $btn.disabled = true;
    }
    $tab.appendChild($btn);

    const $menu = document.createElement('div');
    $menu.id = menuId;
    $menu.className = 'menu menu--disclosure';
    $menu.setAttribute('data-disclosure-menu', 'true');
    $tab.appendChild($menu);

    // The `.disclosureMenu()` jQuery plugin is a Craft seam.
    const disclosureMenu = $($btn).disclosureMenu().data('disclosureMenu');

    disclosureMenu.addItem(
      {
        label: Craft.t('app', 'Settings'),
        icon: async () => await Craft.ui.icon('gear'),
        onActivate: () => {
          this.createSettings();
        },
      },
      disclosureMenu.addGroup()
    );

    const moveUl = disclosureMenu.addGroup();
    const moveLeftBtn = disclosureMenu.addItem(
      {
        label:
          Craft.orientation === 'ltr'
            ? Craft.t('app', 'Move to the left')
            : Craft.t('app', 'Move to the right'),
        icon: async () =>
          await Craft.ui.icon(
            Craft.orientation === 'ltr' ? 'arrow-left' : 'arrow-right'
          ),
        onActivate: () => {
          this.moveLeft();
        },
      },
      moveUl
    );

    const moveRightBtn = disclosureMenu.addItem(
      {
        label:
          Craft.orientation === 'ltr'
            ? Craft.t('app', 'Move to the right')
            : Craft.t('app', 'Move to the left'),
        icon: async () =>
          await Craft.ui.icon(
            Craft.orientation === 'ltr' ? 'arrow-right' : 'arrow-left'
          ),
        onActivate: () => {
          this.moveRight();
        },
      },
      moveUl
    );

    disclosureMenu.addItem(
      {
        label: Craft.t('app', 'Remove'),
        icon: async () => await Craft.ui.icon('xmark'),
        destructive: true,
        onActivate: () => {
          this.destroy();
        },
      },
      disclosureMenu.addGroup()
    );

    disclosureMenu.on('show', () => {
      const prev = this.$container.previousElementSibling;
      const next = this.$container.nextElementSibling;
      disclosureMenu.toggleItem(
        moveLeftBtn,
        !!(prev && prev.matches('.fld-tab'))
      );
      disclosureMenu.toggleItem(
        moveRightBtn,
        !!(next && next.matches('.fld-tab'))
      );
    });
  }

  async createSettings(): Promise<void> {
    let data;
    try {
      const response = await Craft.sendActionRequest(
        'POST',
        'fields/render-layout-component-settings',
        {
          data: {
            uid: this.uid,
            layoutConfig: this.designer.config,
            elementType: this.designer.settings!.elementType,
          },
        }
      );
      data = response.data;
    } catch (e: any) {
      Craft.cp.displayError(e?.response?.data?.message);
      throw e;
    }

    this.settingsNamespace = data.namespace;
    this.slideout = await FieldLayoutDesigner.createSlideout(data, null, {
      triggerElement: this.$actionBtn,
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
    const $nameInput = $container.querySelector('[name$="[name]"]');
    if (!$nameInput?.value) {
      Craft.cp.displayError(Craft.t('app', 'You must specify a tab name.'));
      return;
    }

    // update the UI
    const $submitBtn = $container.querySelector('button[type=submit]');
    $submitBtn?.classList.add('loading');

    const config = Object.assign({}, this.config);
    delete config.elements;

    Craft.sendActionRequest('POST', 'fields/apply-layout-tab-settings', {
      data: {
        uid: this.uid,
        layoutConfig: this.designer.config,
        elementType: this.designer.settings!.elementType,
        config,
        settingsNamespace: this.settingsNamespace,
        // .serialize() is a jQuery-only form helper — keep it at the seam.
        settings: this.slideout.$container.serialize(),
      },
    })
      .then((response: any) => {
        this.updateConfig((config) =>
          Object.assign(response.data.config, {elements: config.elements})
        );
        const $label = this.$container.querySelector('.tabs .tab');
        const $actionBtn = $label.querySelector(':scope > button');
        $actionBtn?.remove();
        $label.innerHTML = response.data.labelHtml;
        if ($actionBtn) {
          $label.appendChild($actionBtn);
        }
        this.slideout.close();
      })
      .catch((e: any) => {
        Craft.cp.displayError();
        console.error(e);
      })
      .finally(() => {
        $submitBtn?.classList.remove('loading');
        this.slideout.close();
      });
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
