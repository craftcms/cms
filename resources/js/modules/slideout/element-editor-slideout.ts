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
  CpScreenSlideout,
  type CpScreenSlideoutSettings,
} from './cp-screen-slideout';
import type {FormValues} from '@/modules/forms/types';

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
  saveParams: FormValues | null;
  /** Callback receiving the saved element's data (alongside the `submit` event). */
  onSaveElement: ((data: FormValues) => void) | null;
  validators: unknown[];
  expandData: unknown[];
  isStatic: boolean;
  onBeforeSubmit: () => Promise<void>;
}

interface ElementEditorParams extends FormValues {
  elementType?: string;
  elementId?: number;
  draftId?: number | null;
  revisionId?: number | null;
  fieldId?: number | null;
  ownerId?: number | null;
  siteId?: number;
  prevalidate?: 1;
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

      // Refresh Live Preview
      Craft.Preview.refresh();
    });
  }

  override getParams(): ElementEditorParams {
    const params: ElementEditorParams = {};

    if (this.settings!.elementType) {
      params.elementType = this.settings!.elementType;
    }

    if (this.settings!.elementId) {
      params.elementId = this.settings!.elementId;
    } else if (this.$element?.data('id')) {
      params.elementId = this.$element.data('id');
    }

    if (this.settings!.draftId !== null) {
      params.draftId = this.settings!.draftId || null; // could be false
    } else if (this.$element?.data('draft-id')) {
      params.draftId = this.$element.data('draft-id');
    } else if (this.settings!.revisionId !== null) {
      params.revisionId = this.settings!.revisionId || null; // could be false
    } else if (this.$element?.data('revision-id')) {
      params.revisionId = this.$element.data('revision-id');
    }

    if (this.settings!.fieldId !== null) {
      params.fieldId = this.settings!.fieldId || null; // could be false
    } else if (this.$element?.data('field-id')) {
      params.fieldId = this.$element.data('field-id');
    }

    if (this.settings!.ownerId !== null) {
      params.ownerId = this.settings!.ownerId || null; // could be false
    } else if (this.$element?.data('owner-id')) {
      params.ownerId = this.$element.data('owner-id');
    }

    if (this.settings!.siteId) {
      params.siteId = this.settings!.siteId;
    } else if (this.$element?.data('site-id')) {
      params.siteId = this.$element.data('site-id');
    }

    if (this.settings!.prevalidate) {
      params.prevalidate = 1;
    }

    return params;
  }

  override reload(): void {
    this.elementEditor?.destroy();
    this.elementEditor = null;
    super.reload();
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

    // Update the `error` class on nested cards.
    if (e?.response?.data?.invalidNestedElementIds) {
      const $cards = this.$content.find('.element.card').removeClass('error');
      $cards
        .find('craft-truncate > span[data-icon="triangle-exclamation"]')
        .remove();
      if (e.response.data.invalidNestedElementIds.length) {
        const $errorCards = $cards
          .filter(
            e.response.data.invalidNestedElementIds
              .map((id: number) => `[data-id=${id}]`)
              .join(',')
          )
          .addClass('error');
        for (let i = 0; i < $errorCards.length; i++) {
          const $label = $errorCards.eq(i).find('craft-truncate');
          $('<span/>', {
            'data-icon': 'triangle-exclamation',
            'aria-label': Craft.t('app', 'Error'),
            role: 'img',
          }).appendTo($label);
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
