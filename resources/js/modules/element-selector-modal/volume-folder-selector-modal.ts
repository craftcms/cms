import {
  VolumeFolderSelectorController,
  type VolumeFolderSelectorOptions,
} from '@craftcms/ui';
import type {App} from 'vue';

export interface VolumeFolderSelectorModalSettings extends VolumeFolderSelectorOptions {
  /** Legacy Garnish setting, accepted and ignored. */
  closeOtherModals?: boolean;
}

/**
 * The folder picker used by asset moves.
 *
 * The Vue element selector modal, browsing asset volumes folders-only, with
 * folder rows selectable rather than just openable. Choosing with nothing
 * highlighted picks the folder that's open, which the controller works out
 * from the index's breadcrumb trail.
 *
 * The Vue modal and index are imported when a picker opens, so pages that
 * never open one don't pay for them. The picker removes itself once closed.
 *
 * @example
 * new Craft.VolumeFolderSelectorModal({
 *   sources: ['volume:…'],
 *   disabledFolderIds: [12],
 *   onSelect: ([folder]) => move(folder.folderId),
 * });
 */
export class VolumeFolderSelectorModal {
  readonly controller: VolumeFolderSelectorController;
  readonly element: HTMLElement;

  #app: App | null = null;
  #destroyed = false;

  constructor(settings: VolumeFolderSelectorModalSettings = {}) {
    const {closeOtherModals: _ignored, ...options} = settings;

    this.controller = new VolumeFolderSelectorController(options);

    this.element = document.createElement('div');
    this.element.className = 'element-selector-modal-host';
    document.body.append(this.element);

    // Torn down once the dialog has finished closing, so its exit transition
    // and focus return aren't cut short. Menus inside it hide too, so only the
    // modal's own event counts.
    this.element.addEventListener(
      'craft-after-hide',
      (event) => {
        if (
          event.target instanceof Element &&
          event.target.localName === 'craft-element-selector-modal'
        ) {
          this.destroy();
        }
      },
      {capture: true}
    );

    void this.#mount();
  }

  show(): Promise<void> {
    return this.controller.open();
  }

  hide(): void {
    this.controller.close();
  }

  destroy(): void {
    if (this.#destroyed) {
      return;
    }

    this.#destroyed = true;
    this.controller.destroy();
    this.#app?.unmount();
    this.#app = null;
    this.element.remove();
  }

  async #mount(): Promise<void> {
    const [
      {createApp},
      {default: FolderSelectorModal},
      {cpComponentRegistry},
      {default: CpLink},
    ] = await Promise.all([
      import('vue'),
      import('./FolderSelectorModal.vue'),
      import('@/bootstrap/components'),
      import('@/common/components/CpLink.vue'),
    ]);

    if (this.#destroyed) {
      return;
    }

    const app = createApp(FolderSelectorModal, {controller: this.controller});

    // The index renders server HTML that names `CpLink` and the registry's
    // components, as in `createElementSelectorModal()`.
    app.component('CpLink', CpLink);
    cpComponentRegistry.install(app);
    app.onUnmount(() => cpComponentRegistry.uninstall(app));

    app.config.compilerOptions.isCustomElement = (tag: string) =>
      tag.includes('-');
    app.mount(this.element);
    this.#app = app;

    await this.controller.open();
  }
}
