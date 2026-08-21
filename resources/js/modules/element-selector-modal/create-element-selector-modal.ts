import {
  createElementSelectorController,
  type ElementSelectorController,
  type ElementSelectorEvent,
  type ElementSelectorEventMap,
  type ElementSelectorOptions,
} from '@craftcms/ui';
import type {App} from 'vue';

/**
 * Settings the presentation layer consumes, which the core neither needs nor
 * should know about.
 *
 * They are DOM concerns, and openers pass them in the same blob as the business
 * settings, so the factory splits the blob rather than widening the core's
 * options with fields it would only carry around.
 */
export interface ElementSelectorModalPresentationOptions {
  /** Where to put focus when the modal closes. */
  triggerElement?: HTMLElement | (() => HTMLElement | null) | null;
  /** Legacy Garnish setting, accepted and ignored. */
  closeOtherModals?: boolean;
}

export type ElementSelectorModalSettings = Partial<ElementSelectorOptions> &
  ElementSelectorModalPresentationOptions;

export interface ElementSelectorModalHandle {
  readonly controller: ElementSelectorController;
  readonly element: HTMLElement;
  show(): Promise<void>;
  hide(): void;
  destroy(): void;
  on<E extends ElementSelectorEvent>(
    event: E,
    listener: (data: ElementSelectorEventMap[E]) => void
  ): () => void;
  setDisabledElementIds(ids: number[]): void;
  setBusy(busy: boolean): void;
}

const PRESENTATION_KEYS = ['triggerElement', 'closeOtherModals'] as const;

function splitSettings(settings: ElementSelectorModalSettings) {
  const presentation: ElementSelectorModalPresentationOptions = {};
  const core = {...settings} as Record<string, unknown>;

  for (const key of PRESENTATION_KEYS) {
    if (key in core) {
      (presentation as Record<string, unknown>)[key] = core[key];
      delete core[key];
    }
  }

  return {core: core as Partial<ElementSelectorOptions>, presentation};
}

function resolveFocusTarget(
  target: ElementSelectorModalPresentationOptions['triggerElement']
): HTMLElement | null {
  return (typeof target === 'function' ? target() : target) ?? null;
}

/**
 * Opens an element selector modal and returns a handle on it.
 *
 * The controller comes from the registry, so an element type with its own
 * controller (assets, for their transforms) gets it. Presentation is the Vue
 * modal, mounted into a detached host — which is why this is async, and why the
 * Vue and element-index modules are imported here rather than at module scope: a
 * page with a relation field on it shouldn't pay for the index unless a modal is
 * actually opened.
 *
 * @example
 * const modal = await createElementSelectorModal(elementType, {
 *   multiSelect: true,
 *   onSelect: (elements) => …,
 * });
 */
export async function createElementSelectorModal(
  elementType: string,
  settings: ElementSelectorModalSettings = {}
): Promise<ElementSelectorModalHandle> {
  const {core, presentation} = splitSettings(settings);
  const controller = createElementSelectorController({
    ...core,
    elementType,
  });

  const [
    {createApp},
    {default: ElementSelectorModal},
    {createCpComponentRegistry},
  ] = await Promise.all([
    import('vue'),
    import('./ElementSelectorModal.vue'),
    import('@/bootstrap/components'),
  ]);

  const host = document.createElement('div');
  host.className = 'element-selector-modal-host';
  document.body.append(host);

  const app: App = createApp(ElementSelectorModal, {controller});
  createCpComponentRegistry().install(app);
  app.config.compilerOptions.isCustomElement = (tag: string) =>
    tag.includes('-');
  app.mount(host);

  // Focus restoration is the opener's business — the relation field wants focus
  // to land on whatever replaced the chip it just removed, not on the trigger.
  controller.on('close', () => {
    const target = resolveFocusTarget(presentation.triggerElement);
    target?.focus();
  });

  let destroyed = false;

  const handle: ElementSelectorModalHandle = {
    controller,
    element: host,
    show: () => controller.open(),
    hide: () => controller.close(),
    destroy() {
      if (destroyed) {
        return;
      }

      destroyed = true;
      controller.destroy();
      app.unmount();
      host.remove();
    },
    on: (event, listener) => controller.on(event, listener),
    setDisabledElementIds: (ids) => controller.setDisabledElementIds(ids),
    setBusy: (busy) => controller.setBusy(busy),
  };

  await controller.open();

  return handle;
}
