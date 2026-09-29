import type {App} from 'vue';

export interface CustomizeSourcesModalOptions {
  /** The element type whose sources to customize. */
  elementType: string;
  /** The index's current page, so the matching one starts selected. */
  page?: string | null;
  /** The index's current source, so the matching one starts selected. */
  sourceKey?: string | null;
  /**
   * Called once the settings are saved, with the source the index should show
   * next (or null to stay put). Defaults to reloading the page.
   */
  onSaved?: (sourceKey: string | null) => void;
}

/**
 * Opens the Customize Sources modal outside the Vue element index — for the
 * legacy Twig index that categories and plugin element types still use.
 *
 * The modal and everything it renders are imported on demand, so the CP bundle
 * doesn't carry them onto pages that never open it.
 */
export async function openCustomizeSourcesModal(
  options: CustomizeSourcesModalOptions
): Promise<void> {
  const [
    {createApp, h},
    {default: CustomizeSourcesModal},
    {cpComponentRegistry},
  ] = await Promise.all([
    import('vue'),
    import('@/modules/elements/components/customize-sources/CustomizeSourcesModal.vue'),
    import('@/bootstrap/components'),
  ]);

  const host = document.createElement('div');
  document.body.append(host);

  const onSaved = options.onSaved ?? (() => window.location.reload());

  const app: App = createApp({
    render: () =>
      h(CustomizeSourcesModal, {
        isActive: true,
        elementType: options.elementType,
        page: options.page ?? null,
        sourceKey: options.sourceKey ?? null,
        onClose: () => {
          app.unmount();
          host.remove();
        },
        onSaved: ({sourceKey}: {sourceKey: string | null}) =>
          onSaved(sourceKey),
      }),
  });

  // Source settings can include server-rendered controls, which compile their
  // HTML at runtime against the components registered on this app.
  cpComponentRegistry.install(app);
  app.onUnmount(() => cpComponentRegistry.uninstall(app));
  app.config.compilerOptions.isCustomElement = (tag: string) =>
    tag.includes('-');
  app.mount(host);
}
