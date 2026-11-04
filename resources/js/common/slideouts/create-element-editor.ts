import {Base} from '@craftcms/garnish';
import {elementEditorParams} from '@/modules/slideout/element-editor-params';
import type {UiValues} from '@/modules/ui/types';
import {closeSlideout, openSlideout} from './store';
import type {SlideoutInstance, SlideoutSaveResult} from './types';

declare const $: any;
declare const Craft: any;

/** The legacy element editor settings a Vue slideout can honor. */
export interface CreateElementEditorSettings {
  elementType?: string | null;
  elementId?: number | null;
  draftId?: number | false | null;
  revisionId?: number | false | null;
  fieldId?: number | false | null;
  ownerId?: number | false | null;
  siteId?: number | null;
  /** Ask the server to validate the element as part of the initial load. */
  prevalidate?: boolean;
  /** Extra `elements/edit` params, such as `fresh`. */
  params?: UiValues;
  onLoad?: (() => void) | null;
  onSubmit?: ((event: ElementEditorSubmitEvent) => void) | null;
  onSaveElement?: ((data: UiValues) => void) | null;
  onClose?: (() => void) | null;
}

export interface ElementEditorSubmitEvent {
  response: {data: UiValues};
  /** The saved element's data. */
  data: UiValues;
}

/**
 * Stands in for the legacy slideout's `elementEditor`, which the Vue editor
 * doesn't have, so callers listening for its `update` event still hear about
 * each autosaved draft.
 */
class ElementEditorEvents extends Base {}

/**
 * What {@link createElementEditor} returns: an element editor in a Vue
 * slideout, behind the parts of `Craft.ElementEditorSlideout`'s API that
 * callers use — `submit`, `load` and `close` events, `close()`, and
 * `elementEditor`'s `update` event.
 */
export class ElementEditorPanel extends Base {
  /** The panel, once it has loaded. */
  panel: SlideoutInstance | null = null;

  readonly elementEditor = new ElementEditorEvents();

  /** Resolves once the panel has loaded, or failed to open. */
  readonly opened: Promise<SlideoutInstance | null>;

  constructor(
    element: string | Element | ArrayLike<Element> | null,
    settings: CreateElementEditorSettings
  ) {
    super();

    const $element = $(element);
    const params = {
      ...elementEditorParams($element, {
        elementType: settings.elementType,
        elementId: settings.elementId,
        draftId: settings.draftId,
        revisionId: settings.revisionId,
        fieldId: settings.fieldId,
        ownerId: settings.ownerId,
        siteId: settings.siteId,
        prevalidate:
          settings.prevalidate ?? $element.parents('.prevalidate').length > 0,
      }),
      ...settings.params,
    };

    this.opened = openSlideout(Craft.getActionUrl('elements/edit', params), {
      opener: $element[0] ?? undefined,
      onSaved: (result) => this.saved(result, settings),
      onClosed: () => {
        settings.onClose?.();
        this.trigger('close');
      },
    }).then((panel) => {
      this.panel = panel;

      if (panel) {
        settings.onLoad?.();
        this.trigger('load');
      }

      return panel;
    });
  }

  close(): void {
    if (this.panel) {
      closeSlideout(this.panel.id);
    }
  }

  private saved(
    result: SlideoutSaveResult,
    settings: CreateElementEditorSettings
  ): void {
    if (result.draft) {
      this.elementEditor.trigger('update');
      return;
    }

    const data = (result.data ?? {}) as UiValues;
    const modelName =
      typeof data.modelName === 'string' ? data.modelName : 'element';
    const event: ElementEditorSubmitEvent = {
      response: {data},
      data: (data[modelName] as UiValues | undefined) ?? {},
    };

    settings.onSubmit?.(event);
    settings.onSaveElement?.(event.data);
    this.trigger('submit', event as unknown as Record<string, unknown>);
  }
}

/**
 * `Craft.createElementEditor()` on control panel pages with Vue slideouts:
 * opens the element's editor in one, rather than in the legacy
 * `Craft.ElementEditorSlideout`, whose styles those pages don't load.
 *
 * Takes the same arguments: `(elementType, settings)` or
 * `(elementType, element, settings)`, where `element` is a chip or card whose
 * `data-*` attributes identify the element.
 */
export function createElementEditor(
  elementType: string,
  element?:
    | string
    | Element
    | ArrayLike<Element>
    | CreateElementEditorSettings
    | null,
  settings?: CreateElementEditorSettings
): ElementEditorPanel {
  if (settings === undefined && $.isPlainObject(element)) {
    settings = element as CreateElementEditorSettings;
    element = null;
  }

  return new ElementEditorPanel(
    (element ?? null) as string | Element | ArrayLike<Element> | null,
    {...settings, elementType: settings?.elementType ?? elementType}
  );
}
