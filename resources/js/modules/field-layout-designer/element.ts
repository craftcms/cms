import {Base, hasAttr} from '@craftcms/garnish';
import {FieldLayoutDesigner} from './field-layout-designer';
import {
    canUseVueSlideout,
    openLayoutComponentSettings,
} from './settings-slideout';
import {
    firstFocusableInSiblings,
    fldElementData,
    htmlToElement,
} from './support';
import type {Tab} from './tab';
import {type ActionMenuItem, t} from '@craftcms/ui';

declare const Craft: any;

/**
 * A single layout element (field or UI element) within a {@link Tab}. Native DOM
 * port of the legacy `Craft.FieldLayoutDesigner.Element`. jQuery survives only at
 * Craft seams (`Craft.ui.*`, `Craft.*Slideout`). The action menu uses the
 * `craft-action-menu` web component.
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
    slideout: any = null;
    defaultHandle: any = null;
    fieldId: any = null;

    constructor(tab: Tab, $container: any) {
        super();
        this.tab = tab;
        this.$container = $container;
        this.uid = $container.dataset.uid;
        this.fieldId = $container.dataset.id;

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
        this.isMultiInstance = hasAttr(
            this.$container,
            'data-is-multi-instance'
        );

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
            const widthSlider = document.createElement('craft-slide-picker');
            widthSlider.setAttribute(
                'label',
                Craft.t('app', 'Number of columns')
            );
            widthSlider.setAttribute('value-unit', '%');
            widthSlider.setAttribute('min', '25');
            widthSlider.setAttribute('max', '100');
            widthSlider.setAttribute('step', '25');
            widthSlider.setAttribute('value', `${this.config.width || 100}`);

            if (this.tab.designer.settings!.readOnly) {
                widthSlider.setAttribute('read-only', '');
            }

            widthSlider.addEventListener('value-change', (event: Event) => {
                const width = (event as CustomEvent<{value: number}>).detail
                    .value;
                this.updateConfig((config: any) => {
                    config.width = width;
                    return config;
                });
            });

            this.$container.appendChild(widthSlider);
        }

        // create the action menu
        const readOnly = !!this.tab.designer.settings!.readOnly;
        this.hasSettings = hasAttr(this.$container, 'data-has-settings');

        const menu = document.createElement('craft-action-menu');
        menu.label = Craft.t('app', 'Actions');
        // Disable the generated (default ellipsis) invoker when read-only.
        menu.disabled = readOnly;
        // Anchor the settings slideout (focus return) to the menu element, since the
        // default invoker lives inside the web component's light DOM.
        this.$actionBtn = menu;

        if (this.hasSettings && !readOnly) {
            this.addListener(this.$container, 'dblclick', () => {
                this.createSettings();
            });
        }

        // Provider function — re-evaluated each time the menu opens, so items
        // reflect the element's current state (required/optional, sibling
        // presence). Replaces the legacy `on('show')` + `toggleItem` logic.
        menu.actions = (): ActionMenuItem[] => {
            const items: ActionMenuItem[] = [];

            if (this.hasSettings && !readOnly) {
                items.push({
                    label: Craft.t('app', 'Settings'),
                    icon: 'gear',
                    onClick: () => {
                        this.createSettings();
                    },
                });
            }

            // Required / optional toggle (provider recomputes on each open, so we
            // simply branch on the current `config.required`).
            if (this.requirable) {
                if (items.length) {
                    items.push({type: 'hr'});
                }
                if (!this.config.required) {
                    items.push({
                        label: Craft.t('app', 'Make required'),
                        icon: 'asterisk',
                        iconColor: 'rose',
                        onClick: () => {
                            this.makeRequired();
                        },
                    });
                } else {
                    items.push({
                        label: Craft.t('app', 'Make optional'),
                        icon: 'custom-icons/asterisk-slash',
                        iconColor: 'gray',
                        onClick: () => {
                            this.dropRequired();
                        },
                    });
                }
            }

            const prev = this.$container.previousElementSibling;
            const next = this.$container.nextElementSibling;
            const hasPrev = !!(prev && prev.matches('.fld-element'));
            const hasNext = !!(next && next.matches('.fld-element'));

            if (hasPrev || hasNext) {
                if (items.length) {
                    items.push({type: 'hr'});
                }
                if (hasPrev) {
                    items.push({
                        label: Craft.t('app', 'Move up'),
                        icon: 'arrow-up',
                        onClick: () => {
                            this.moveUp();
                        },
                    });
                }
                if (hasNext) {
                    items.push({
                        label: Craft.t('app', 'Move down'),
                        icon: 'arrow-down',
                        onClick: () => {
                            this.moveDown();
                        },
                    });
                }
            }

            if (!this.isMandatory) {
                if (items.length) {
                    items.push({type: 'hr'});
                }
                items.push({
                    label: Craft.t('app', 'Remove'),
                    icon: 'xmark',
                    variant: 'danger',
                    onClick: () => {
                        this.destroy();
                    },
                });
            }

            return items;
        };

        this.$container.appendChild(menu);
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
            this.$container.querySelector('.fld-element-label')?.textContent ??
            '';
        return label !== '' ? label : this.$container.dataset.attribute;
    }

    private settingsRequestData(): Record<string, unknown> {
        return {
            uid: this.uid,
            layoutConfig: this.tab.designer.config,
            elementType: this.tab.designer.settings!.elementType,
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
                        config: this.config,
                    },
                }
            );
            data = response.data;
        } catch (e: any) {
            Craft.cp.displayError(e?.response?.data?.message);
            throw e;
        }

        const requestData = () => ({
            ...this.settingsRequestData(),
            config: this.config,
        });

        if (canUseVueSlideout()) {
            await openLayoutComponentSettings(data, {
                title: this.settingsTitle(),
                triggerElement: this.$actionBtn,
                requestData,
                // The panel owns Save/Cancel and reports errors from the rejection.
                apply: (settings) =>
                    this.applyConfig(() => this.config, settings),
            });

            this.trigger('createSettings');

            return;
        }

        this.slideout = await FieldLayoutDesigner.createSlideout(data, {
            triggerElement: this.$actionBtn,
            requestData,
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

        this.addListener($fieldsContainer, 'field-saved', (event) => {
            this.refreshField(
                (event as unknown as CustomEvent).detail.selectorHtml
            );
        });

        this.trigger('createSettings');
    }

    async applySettings(): Promise<void> {
        // update the UI
        const $submitBtn = this.slideout.$container[0].querySelector(
            'button[type=submit]'
        );
        $submitBtn?.classList.add('loading');

        try {
            await this.applyConfig(
                () => this.config,
                this.slideout.settingsForm?.currentValues() ?? {}
            );
        } catch {
            // Errors are already shown in the slideout.
        } finally {
            $submitBtn?.classList.remove('loading');
        }
    }

    /** The label shown in the settings panel's title bar. */
    private settingsTitle(): string {
        return this.getLabel()
            ? t('{label} Settings', {
                  label: this.getLabel(),
              })
            : t('Settings');
    }

    async showFieldEditor(): Promise<void> {
        const slideout = new Craft.CpScreenSlideout(
            Craft.getCpUrl('settings/fields/edit'),
            {
                params: {
                    fieldId: this.fieldId,
                    multiInstanceTypesOnly: this.isMultiInstance ? 1 : 0,
                },
            }
        );

        slideout.on('submit', async ({response}: any) => {
            this.refreshField(response.data.selectorHtml);
        });
    }

    private refreshField(selectorHtml: string): void {
        const designer = this.tab.designer;
        const $oldSelector = designer.$fieldLibrary.querySelector(
            `.fld-field[data-id=${this.fieldId}]`
        );
        const $newSelector = htmlToElement(selectorHtml);
        $oldSelector?.replaceWith($newSelector);
        designer.refreshLibraryFields();
        designer.elementDrag!.removeItems($oldSelector);
        designer.elementDrag!.addItems($newSelector);

        designer.$tabContainer
            .querySelectorAll(`.fld-field[data-id=${this.fieldId}]`)
            .forEach((el: HTMLElement) => fldElementData.get(el)?.refresh());
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
        settings: Record<string, unknown> | null = null,
        closeSlideout = true
    ): Promise<void> {
        const config = callback(this.config);
        if (config === false) {
            return;
        }

        const settingsForm = this.slideout?.settingsForm;

        if (settings && settingsForm) {
            settingsForm.errors = {};
        }

        let data;

        try {
            const response = await Craft.sendActionRequest(
                'POST',
                'fields/apply-layout-element-settings',
                {
                    data: {
                        ...this.settingsRequestData(),
                        config,
                        settings,
                    },
                }
            );
            data = response.data;
        } catch (e: any) {
            const errors = e?.response?.data?.errors;

            // The Vue panel renders its own errors from the rejection.
            if (settings && settingsForm && errors) {
                settingsForm.errors = errors;
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

        if (closeSlideout && this.slideout) {
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
                    if (
                        element &&
                        element !== this &&
                        element.config.providesThumbs
                    ) {
                        element.applyConfig((config: any) => {
                            config.providesThumbs = false;
                            return config;
                        });
                    }
                });
        }
    }

    async refresh(): Promise<void> {
        await this.applyConfig((config: any) => config, null, false);
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
        let config = this.tab.config.elements.find(
            (c: any) => c.uid === this.uid
        );
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
                this.$container.parentElement.querySelectorAll(
                    ':scope > .fld-element'
                )
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
                this.$container.parentElement.querySelectorAll(
                    ':scope > .fld-element'
                )
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
                        cvd.removeCheckbox(
                            option.value.replace(/\{uid}/g, this.uid)
                        );
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
            // Non-field, non-multi-instance UI element — make it selectable again.
            if (!this.isMultiInstance) {
                const uiLibraryElement =
                    this.tab.designer.$uiLibraryElements.find(
                        (el: HTMLElement) =>
                            el.matches(
                                `[data-type="${this.$container.dataset.type}"]`
                            )
                    );
                if (uiLibraryElement) {
                    uiLibraryElement.classList.remove('hidden');
                }
            }
        }

        super.destroy();
    }
}
