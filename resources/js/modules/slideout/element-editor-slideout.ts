/**
 * ElementEditorSlideout — a {@link CpScreenSlideout} for the `elements/edit`
 * screen, pairing the slideout chrome with a `Craft.ElementEditor` instance
 * that takes over drafts, tabs, and submission once the screen loads. See the
 * module README for usage.
 *
 * `Craft.ElementEditor` stays a page-global seam: it's constructed on `load`
 * with this slideout's chrome handed in (`$contentContainer`, `$sidebar`,
 * tab-manager accessors, submit-response handlers), and it drives
 * {@link handleSubmit} from then on.
 */

import {
  elementEditorParams,
  type ElementEditorParams,
} from './element-editor-params';
import {
  CpScreenSlideout,
  type CpScreenSlideoutSettings,
} from './cp-screen-slideout';
import {refreshPreviews} from '@/modules/elements/live-preview/previews';
import type {UiValues} from '@/modules/ui/types';

declare const Craft: any;
declare const $: any;

/**
 * Settings accepted by {@link ElementEditorSlideout}, layered on top of
 * {@link CpScreenSlideoutSettings}. The element identifiers all fall back to
 * the corresponding `data-*` attributes on the element passed to the
 * constructor; `false` values are sent as explicit `null` params.
 */
export interface ElementEditorSlideoutSettings extends CpScreenSlideoutSettings {
  elementId: number | null;
  draftId: number | false | null;
  revisionId: number | false | null;
  fieldId: number | false | null;
  ownerId: number | false | null;
  elementType: string | null;
  siteId: number | null;
  /** Ask the server to validate the element as part of the initial load. */
  prevalidate: boolean;
  /** Extra params injected as hidden inputs just before submitting. */
  saveParams: UiValues | null;
  /** Callback receiving the saved element's data (alongside the `submit` event). */
  onSaveElement: ((data: UiValues) => void) | null;
  validators: unknown[];
  expandData: unknown[];
  isStatic: boolean;
  onBeforeSubmit: () => Promise<void>;
}

/**
 * An element editor slideout: loads the `elements/edit` screen for an
 * element (or a new one) and hands control of drafts/submission to
 * `Craft.ElementEditor`.
 *
 * @example
 * ```ts
 * import {ElementEditorSlideout} from '@/modules/slideout';
 *
 * const slideout = new ElementEditorSlideout($chip, {siteId: 2});
 * slideout.on('submit', ({data}) => console.log('saved', data.id));
 * ```
 */
export class ElementEditorSlideout extends CpScreenSlideout {
  static override defaults: ElementEditorSlideoutSettings = {
    ...CpScreenSlideout.defaults,
    elementId: null,
    draftId: null,
    revisionId: null,
    fieldId: null,
    ownerId: null,
    elementType: null,
    siteId: null,
    prevalidate: false,
    saveParams: null,
    onSaveElement: null,
    validators: [],
    expandData: [],
    isStatic: false,
    onBeforeSubmit: async () => {},
  };

  override settings: ElementEditorSlideoutSettings | null = null;

  /** The element (chip/card) this editor was opened for, if any. */
  $element: any = null;

  /** The `Craft.ElementEditor` instance, once the screen has loaded. */
  elementEditor: any = null;

  /**
   * @param element - The element chip/card the editor is being opened for
   *   (its `data-*` attributes provide fallback params), or `null`.
   * @param settings - Optional settings overrides (see
   *   {@link ElementEditorSlideoutSettings}).
   */
  constructor(
    element?: string | Element | ArrayLike<Element> | null,
    settings?: Partial<ElementEditorSlideoutSettings>
  ) {
    super();
    if (new.target === ElementEditorSlideout) {
      this.init(element, settings);
    }
  }

  override init(
    element?: string | Element | ArrayLike<Element> | null,
    settings?: Partial<ElementEditorSlideoutSettings>
  ): void {
    this.$element = $(element);

    const mergedSettings = Object.assign(
      {},
      ElementEditorSlideout.defaults,
      settings,
      {
        showHeader: true,
        prevalidate: this.$element.parents('.prevalidate').length > 0,
      }
    );
    super.init('elements/edit', mergedSettings);

    this.on('load', () => {
      this.elementEditor = new Craft.ElementEditor(
        this.$container,
        Object.assign(
          {
            namespace: this.namespace,
            $contentContainer: this.$content,
            $sidebar: this.$sidebar,
            $actionBtn: this.$actionBtn,
            $spinnerContainer: this.$toolbar,
            updateTabs: (tabs: any) => {
              this.updateTabs(tabs);
            },
            getTabManager: () => this.tabManager,
            handleSubmitResponse: (response: any) => {
              this.handleSubmitResponse(response);
            },
            handleSubmitError: (error: any) => {
              this.handleSubmitError(error);
            },
          },
          this.$container.data('elementEditorSettings')
        )
      );

      // If it's supposed to be static — e.g. it's a revision — don't show
      // the save button, and change the wording on the cancel one.
      if (this.elementEditor.settings.isStatic) {
        this.$saveBtn.remove();
        this.$cancelBtn[0].innerText = Craft.t('app', 'Close');
      }

      this.elementEditor.on('beforeSubmit', () => {
        if (this.settings!.saveParams) {
          Object.keys(this.settings!.saveParams!).forEach((name) => {
            $('<input/>', {
              class: 'hidden',
              name: this.elementEditor.namespaceInputName(name),
              value: this.settings!.saveParams![name],
            }).appendTo(this.$container);
          });
        }
        this.showSubmitSpinner();
      });
      this.elementEditor.on('afterSubmit', () => {
        this.hideSubmitSpinner();
      });
    });

    this.on('submit', (ev: any) => {
      if (Craft.broadcaster) {
        Craft.broadcaster.postMessage({
          event: 'saveElement',
          id: ev.response.data.element.id,
        });
      }

      // Pass the response data off to onSaveElement() for backwards
      // compatibility.
      if (this.settings!.onSaveElement) {
        const data = Object.assign(
          {},
          ev.response.data,
          ev.response.data.element
        );
        delete data.element;
        delete data.modelName;
        delete data.message;
        this.settings!.onSaveElement!(data);
      }

      refreshPreviews();
    });
  }

  override getParams(): ElementEditorParams {
    return elementEditorParams(this.$element, this.settings!);
  }

  override reload(): void {
    this.elementEditor?.destroy();
    this.elementEditor = null;
    super.reload();
  }

  /**
   * Reloads the slideout for the element in a different site.
   */
  async switchSite(siteId: number): Promise<void> {
    if (siteId == this.settings!.siteId) {
      return;
    }

    if (this.elementEditor) {
      if (
        this.elementEditor.settings.canCreateDrafts &&
        this.elementEditor.enableAutosave
      ) {
        // Make sure any pending changes have been saved to the draft
        await this.elementEditor.checkForm();
      } else if (
        this.isDirty() &&
        !confirm(
          Craft.t(
            'app',
            'Are you sure you want to switch sites? Any changes will be lost.'
          )
        )
      ) {
        return;
      }
    }

    // Keep the sidebar open, so the site change is visible
    if (this.showingSidebar) {
      this.once('load', () => {
        this.showSidebar(false);
      });
    }

    this.settings!.siteId = siteId;
    this.reload();
  }

  override async handleSubmit(ev: any): Promise<void> {
    if (ev.type !== 'submit' && this.elementEditor.settings.canCreateDrafts) {
      // Save the draft before fully saving; otherwise the tab error
      // indicators get out of sync.
      await this.elementEditor.saveDraft();
    }

    ev.preventDefault();
    ev.stopPropagation();
    ev.stopImmediatePropagation();

    this.showSubmitSpinner();
    await this.settings!.onBeforeSubmit();
    this.elementEditor.handleSubmit(ev);
    this.hideSubmitSpinner();
  }

  override handleSubmitError(e: any): void {
    super.handleSubmitError(e);

    const ids = e?.response?.data?.invalidNestedElementIds;
    if (Array.isArray(ids)) {
      this.$content[0]?.dispatchEvent(
        new CustomEvent('craft:nested-validation', {
          bubbles: true,
          detail: {ids},
        })
      );

      const $cardsAndChips = this.$content
        .find('.element.card, craft-chip')
        .removeClass('error');
      $cardsAndChips.find('span[data-icon="triangle-exclamation"]').remove();
      if (ids.length) {
        const $errorCardsAndChips = $cardsAndChips
          .filter(ids.map((id: number) => `[data-id=${id}]`).join(','))
          .addClass('error');
        for (let i = 0; i < $errorCardsAndChips.length; i++) {
          const $item = $errorCardsAndChips.eq(i);
          const $label = $item.find('craft-truncate');
          $('<span/>', {
            'data-icon': 'triangle-exclamation',
            'aria-label': Craft.t('app', 'Error'),
            role: 'img',
          }).appendTo($label.length ? $label : $item);
        }
      }
    }
  }

  override destroy(): void {
    this.elementEditor.destroy();
    this.elementEditor = null;
    super.destroy();
  }
}

export default ElementEditorSlideout;
