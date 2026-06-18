import {Base, hasAttr} from '@craftcms/garnish';
import {FieldLayoutDesigner} from './FieldLayoutDesigner';
import {
  fldElementData,
  firstFocusableInSiblings,
  htmlToElement,
} from './support';
import type {Tab} from './Tab';

declare const Craft: any;
declare const $: any;

/**
 * A single layout element (field or UI element) within a {@link Tab}. Native DOM
 * port of the legacy `Craft.FieldLayoutDesigner.Element`. jQuery survives only at
 * Craft seams (`Craft.ui.*`, the `.disclosureMenu()` plugin, `Craft.*Slideout`).
 */
export class Element extends Base {
  tab: Tab;
  $container: any = null;
  $actionBtn: any = null;

  uid: any = null;
  isMandatory = false;
  isMultiInstance: any = null;
  isField = false;
  attribute: any = null;
  requirable = false;
  thumbable = false;
  hasCustomWidth = false;
  hasSettings = false;
  settingsNamespace: any = null;
  slideout: any = null;
  defaultHandle: any = null;
  fieldId: any = null;
  fieldsWithErrors: any[] = [];

  constructor(tab: Tab, $container: any) {
    super();
    this.tab = tab;
    this.$container = $container;
    this.uid = $container.dataset.uid;
    this.fieldId = $container.dataset.id;

    this.fieldsWithErrors = [];

    // New element?
    const isNew = !this.uid;
    if (isNew) {
      this.uid = Craft.uuid();
      this.config = Object.assign(JSON.parse($container.dataset.config), {
        uid: this.uid,
      });
    }

    this.initUi();

    if (isNew && this.isField) {
      // Find a unique handle
      let handle = this.defaultHandle;
      let i = 1;
      while (this.tab.designer.hasHandle(handle)) {
        i++;
        handle = this.defaultHandle + i;
      }
      if (handle !== this.defaultHandle) {
        this.config = Object.assign({}, this.config, {handle: handle});
        const $label = $container.querySelector('.fld-attribute-label');
        if ($label) {
          $label.textContent = handle;
        }
      }
      this.tab.designer.refreshSelectedFields();
    }

    // cleanup
    $container.removeAttribute('data-keywords');
  }

  initUi(): void {
    fldElementData.set(this.$container, this);

    this.isMandatory = hasAttr(this.$container, 'data-mandatory');
    this.isField = this.$container.classList.contains('fld-field');
    this.isMultiInstance = hasAttr(this.$container, 'data-is-multi-instance');

    if (this.isField) {
      this.requirable = hasAttr(this.$container, 'data-requirable');
      this.thumbable = hasAttr(this.$container, 'data-thumbable');
      this.attribute = this.$container.dataset.attribute;
      this.defaultHandle = this.$container.dataset.defaultHandle;
    }

    this.hasCustomWidth =
      this.tab.designer.settings!.customizableUi &&
      hasAttr(this.$container, 'data-has-custom-width');

    if (this.hasCustomWidth) {
      const widthSlider = new Craft.SlidePicker(this.config.width || 100, {
        min: 25,
        max: 100,
        step: 25,
        valueLabel: (width: number) => {
          return Craft.t('app', '{pct} width', {pct: `${width}%`});
        },
        onChange: (width: number) => {
          this.updateConfig((config: any) => {
            config.width = width;
            return config;
          });
        },
        readOnly: this.tab.designer.settings!.readOnly,
      });
      // Craft.SlidePicker exposes a jQuery $container — unwrap to native.
      this.$container.appendChild(widthSlider.$container[0]);
    }

    // create the action menu
    const menuId = `actionmenu${Math.floor(Math.random() * 1000000)}`;
    this.$actionBtn = document.createElement('button');
    this.$actionBtn.setAttribute('type', 'button');
    this.$actionBtn.className = 'btn action-btn';
    this.$actionBtn.setAttribute('data-disclosure-trigger', 'true');
    this.$actionBtn.setAttribute('aria-controls', menuId);
    this.$actionBtn.setAttribute('aria-haspopup', 'true');
    this.$actionBtn.setAttribute('aria-label', Craft.t('app', 'Actions'));
    this.$actionBtn.setAttribute('title', Craft.t('app', 'Actions'));
    if (this.tab.designer.settings!.readOnly) {
      this.$actionBtn.disabled = true;
    }
    this.$container.appendChild(this.$actionBtn);

    const $menu = document.createElement('div');
    $menu.id = menuId;
    $menu.className = 'menu menu--disclosure';
    $menu.setAttribute('data-disclosure-menu', 'true');
    this.$container.appendChild($menu);

    // The `.disclosureMenu()` jQuery plugin is a Craft seam.
    const disclosureMenu = $(this.$actionBtn)
      .disclosureMenu()
      .data('disclosureMenu');

    let makeRequiredBtn: any, dropRequiredBtn: any;

    this.hasSettings = hasAttr(this.$container, 'data-has-settings');

    if (this.hasSettings && !this.tab.designer.settings!.readOnly) {
      disclosureMenu.addItem({
        label: Craft.t('app', 'Settings'),
        icon: async () => await Craft.ui.icon('gear'),
        onActivate: () => {
          this.createSettings();
        },
      });

      this.addListener(this.$container, 'dblclick', () => {
        this.createSettings();
      });
    }

    if (
      this.requirable ||
      (!this.tab.designer.settings!.withCardViewDesigner && this.thumbable)
    ) {
      const actionUl = disclosureMenu.addGroup();

      if (this.requirable) {
        makeRequiredBtn = disclosureMenu.addItem(
          {
            label: Craft.t('app', 'Make required'),
            icon: async () => await Craft.ui.icon('asterisk'),
            iconColor: 'rose',
            onActivate: () => {
              this.makeRequired();
            },
          },
          actionUl
        );

        dropRequiredBtn = disclosureMenu.addItem(
          {
            label: Craft.t('app', 'Make optional'),
            icon: async () => await Craft.ui.icon('asterisk-slash'),
            iconColor: 'gray',
            onActivate: () => {
              this.dropRequired();
            },
          },
          actionUl
        );
      }
    }

    const moveGroup = disclosureMenu.addGroup();
    const moveUpBtn = disclosureMenu.addItem(
      {
        label: Craft.t('app', 'Move up'),
        icon: async () => await Craft.ui.icon('arrow-up'),
        onActivate: () => {
          this.moveUp();
        },
      },
      moveGroup
    );
    const moveDownBtn = disclosureMenu.addItem(
      {
        label: Craft.t('app', 'Move down'),
        icon: async () => await Craft.ui.icon('arrow-down'),
        onActivate: () => {
          this.moveDown();
        },
      },
      moveGroup
    );

    if (!this.isMandatory) {
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
    }

    disclosureMenu.on('show', () => {
      if (this.requirable) {
        disclosureMenu.toggleItem(makeRequiredBtn, !this.config.required);
        disclosureMenu.toggleItem(dropRequiredBtn, this.config.required);
      }

      const prev = this.$container.previousElementSibling;
      const next = this.$container.nextElementSibling;
      disclosureMenu.toggleItem(
        moveUpBtn,
        !!(prev && prev.matches('.fld-element'))
      );
      disclosureMenu.toggleItem(
        moveDownBtn,
        !!(next && next.matches('.fld-element'))
      );
    });
  }

  onSelect(): void {
    this.$container.setAttribute('data-uid', this.uid);

    const previewOptions = this.$container.dataset.previewOptions
      ? JSON.parse(this.$container.dataset.previewOptions)
      : null;
    if (previewOptions) {
      const cvd = this.tab.designer.cvd;
      if (cvd) {
        previewOptions.forEach((option: any) => {
          cvd.addCheckbox({
            value: option.value.replace(/\{uid}/g, this.uid),
            label: option.label,
          });
        });
        cvd.updateThumbnailsDropdown(this, 'add');
      }
    }
  }

  getLabel(): string {
    const label =
      this.$container.querySelector('.fld-element-label')?.textContent ?? '';
    return label !== '' ? label : this.$container.dataset.attribute;
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
            layoutConfig: this.tab.designer.config,
            elementType: this.tab.designer.settings!.elementType,
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

    const $fieldsContainer =
      this.slideout.$container[0].querySelector('.fields');

    if (this.isField) {
      const $handleInput = $fieldsContainer?.querySelector(
        'input[name$="[handle]"]'
      );
      if ($handleInput) {
        $handleInput.value = this.config.handle || '';
      }
    }

    this.trigger('createSettings');
  }

  async applySettings(): Promise<void> {
    // update the UI
    const $submitBtn = this.slideout.$container[0].querySelector(
      'button[type=submit]'
    );
    $submitBtn?.classList.add('loading');

    try {
      await this.applyConfig(() => this.config, true);
    } finally {
      $submitBtn?.classList.remove('loading');
    }
  }

  async showFieldEditor(): Promise<void> {
    const slideout = new Craft.CpScreenSlideout('fields/edit-field', {
      params: {
        fieldId: this.fieldId,
        multiInstanceTypesOnly: this.isMultiInstance ? 1 : 0,
      },
    });

    slideout.on('submit', async ({response}: any) => {
      const designer = this.tab.designer;

      // refresh the library selector
      const $oldSelector = designer.$fieldLibrary.querySelector(
        `.fld-field[data-id=${this.fieldId}]`
      );
      const $newSelector = htmlToElement(response.data.selectorHtml);
      $oldSelector?.replaceWith($newSelector);
      designer.refreshLibraryFields();
      designer.elementDrag!.removeItems($oldSelector);
      designer.elementDrag!.addItems($newSelector);

      // refresh all instances of this field
      designer.$tabContainer
        .querySelectorAll(`.fld-field[data-id=${this.fieldId}]`)
        .forEach((el: HTMLElement) => fldElementData.get(el)?.refresh());
    });
  }

  async makeRequired(): Promise<void> {
    await this.applyConfig((config: any) => {
      config.required = true;
      return config;
    });
  }

  async dropRequired(): Promise<void> {
    await this.applyConfig((config: any) => {
      config.required = false;
      return config;
    });
  }

  moveUp(): void {
    const $prev = this.$container.previousElementSibling;
    if ($prev && $prev.matches('.fld-element')) {
      $prev.before(this.$container);
      this.updatePositionInConfig();
    }
  }

  moveDown(): void {
    const $next = this.$container.nextElementSibling;
    if ($next && $next.matches('.fld-element')) {
      $next.after(this.$container);
      this.updatePositionInConfig();
    }
  }

  async applyConfig(
    callback: (config: any) => any,
    withSettings = false
  ): Promise<void> {
    const config = callback(this.config);
    if (config === false) {
      return;
    }

    // Craft.ui error helpers require jQuery fields — keep them at the seam.
    this.fieldsWithErrors.forEach(($field: any) => {
      Craft.ui.clearErrorsFromField($field);
    });

    let data;

    try {
      const response = await Craft.sendActionRequest(
        'POST',
        'fields/apply-layout-element-settings',
        {
          data: {
            uid: this.uid,
            layoutConfig: this.tab.designer.config,
            elementType: this.tab.designer.settings!.elementType,
            config,
            settingsNamespace: this.settingsNamespace,
            settings: withSettings
              ? this.slideout.$container.serialize()
              : null,
          },
        }
      );
      data = response.data;
    } catch (e: any) {
      if (withSettings) {
        const errors = e?.response?.data?.errors;
        if (errors) {
          Object.entries(errors).forEach(([name, fieldErrors]) => {
            // Craft.ui.addErrorsToField needs a jQuery field — seam.
            const $field = this.slideout.$container.find(
              `[data-error-key="${name}"]`
            );
            if ($field.length) {
              Craft.ui.addErrorsToField($field, fieldErrors);
              this.fieldsWithErrors.push($field);
            }
          });
        }
      }

      Craft.cp.displayError(e?.response?.data?.message);
      throw e;
    }

    this.config = data.config;
    const $oldContainer = this.$container;
    const $newContainer = htmlToElement(data.selectorHtml);
    this.$container.replaceWith($newContainer);
    this.$container = $newContainer;
    this.initUi();

    if (this.tab.designer.settings!.withCardViewDesigner) {
      const cvd = this.tab.designer.cvd;
      if (cvd) {
        const previewOptions = $newContainer.dataset.previewOptions
          ? JSON.parse($newContainer.dataset.previewOptions)
          : null;
        if (previewOptions) {
          // update labels in cvd checkboxes
          previewOptions.forEach((option: any) => {
            cvd.updateCheckboxLabel(
              option.value.replace(/\{uid}/g, this.uid),
              option.label
            );
          });
        }

        // update label in the element thumbnails dropdown
        cvd.updateThumbnailsDropdownOptionLabel(this.$container);
      }
    }

    const designer = this.tab.designer;
    designer.refreshSelectedFields();
    designer.elementDrag!.removeItems($oldContainer);
    designer.elementDrag!.addItems($newContainer);

    if (this.slideout) {
      this.slideout.close();
      this.slideout.destroy();
      this.slideout = null;
    }

    if (this.config.providesThumbs) {
      // make sure this is the only one
      this.tab.designer.$tabContainer
        .querySelectorAll('.fld-field')
        .forEach(($field: HTMLElement) => {
          const element = fldElementData.get($field);
          if (element && element !== this && element.config.providesThumbs) {
            element.applyConfig((config: any) => {
              config.providesThumbs = false;
              return config;
            });
          }
        });
    }
  }

  async refresh(): Promise<void> {
    await this.applyConfig((config: any) => config);
  }

  get index(): number {
    const tabConfig = this.tab.config;
    if (typeof tabConfig === 'undefined') {
      return -1;
    }
    return tabConfig.elements.findIndex((c: any) => c.uid === this.uid);
  }

  get config(): any {
    if (!this.uid) {
      throw 'Tab is missing its UID';
    }
    let config = this.tab.config.elements.find((c: any) => c.uid === this.uid);
    if (!config) {
      config = {
        uid: this.uid,
      };
      this.config = config;
    }
    return config;
  }

  set config(config: any) {
    const tabConfig = this.tab.config;
    const index = this.index;
    if (index !== -1) {
      tabConfig.elements[index] = config;
    } else {
      const siblings = Array.from(
        this.$container.parentElement.querySelectorAll(':scope > .fld-element')
      );
      const newIndex = siblings.indexOf(this.$container);
      tabConfig.elements.splice(newIndex, 0, config);
    }
    this.tab.config = tabConfig;
  }

  updateConfig(callback: (config: any) => any): void {
    const config = callback(this.config);
    if (config !== false) {
      this.config = config;
    }
  }

  updatePositionInConfig(): void {
    this.tab.updateConfig((config: any) => {
      const elementConfig = this.config;
      const oldIndex = this.index;
      const siblings = Array.from(
        this.$container.parentElement.querySelectorAll(':scope > .fld-element')
      );
      const newIndex = siblings.indexOf(this.$container);
      if (oldIndex !== -1) {
        config.elements.splice(oldIndex, 1);
      }
      config.elements.splice(newIndex, 0, elementConfig);
      return config;
    });
  }

  override destroy(): void {
    if (this.tab.designer.settings!.withCardViewDesigner) {
      const cvd = this.tab.designer.cvd;
      if (cvd) {
        // this needs to be called before removeCheckbox()
        cvd.updateThumbnailsDropdown(this, 'remove');

        const previewOptions = this.$container.dataset.previewOptions
          ? JSON.parse(this.$container.dataset.previewOptions)
          : null;
        if (previewOptions?.length) {
          previewOptions.forEach((option: any) => {
            cvd.removeCheckbox(option.value.replace(/\{uid}/g, this.uid));
          });
        }
      }
    }

    this.tab.updateConfig((config: any) => {
      const index = this.index;
      if (index === -1) {
        return false;
      }
      config.elements.splice(index, 1);
      return config;
    });

    // Set focus to the closest element's first focusable element
    const $focusElement = firstFocusableInSiblings(this.$container);
    if ($focusElement) {
      $focusElement.focus();
    } else {
      this.tab.$addBtn.focus();
    }

    this.tab.designer.elementDrag!.removeItems(this.$container);
    this.$container.remove();

    if (this.isField) {
      this.tab.designer.refreshSelectedFields();

      if (!this.isMultiInstance) {
        this.tab.designer.removeFieldByHandle(
          this.defaultHandle || this.attribute
        );
      }
    } else {
      // if it's not a field (so a ui element) and it's not multi instance, make it visible for selection again
      if (!this.isMultiInstance) {
        const uiLibraryElement = this.tab.designer.$uiLibraryElements.find(
          (el: HTMLElement) =>
            el.matches(`[data-type="${this.$container.dataset.type}"]`)
        );
        if (uiLibraryElement) {
          uiLibraryElement.classList.remove('hidden');
        }
      }
    }

    super.destroy();
  }
}
