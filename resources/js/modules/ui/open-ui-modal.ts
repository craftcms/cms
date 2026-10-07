import type {App} from 'vue';
import type {UiValues} from './types';

export interface UiModalOptions {
  modalUrl: string;
  actionUrl: string;
  params?: UiValues;
  title?: string;
  submitLabel?: string;
  width?: string;
  onSubmitted?: (data: Record<string, unknown>) => void;
}

/**
 * Opens a server-built UI in a detached Vue app with registered CP controls.
 * The app, host, and registry installation are removed on close or submission.
 */
export async function openUiModal({
  onSubmitted,
  ...props
}: UiModalOptions): Promise<void> {
  const [{createApp, h}, {default: UiModal}, {cpComponentRegistry}] =
    await Promise.all([
      import('vue'),
      import('./UiModal.vue'),
      import('@/bootstrap/components'),
    ]);

  const host = document.createElement('div');
  document.body.append(host);

  let closed = false;
  const close = () => {
    if (closed) {
      return;
    }

    closed = true;
    app.unmount();
    host.remove();
  };

  const app: App = createApp({
    render: () =>
      h(UiModal, {
        ...props,
        onClose: close,
        onSubmitted: (data: Record<string, unknown>) => {
          close();
          onSubmitted?.(data);
        },
      }),
  });

  cpComponentRegistry.install(app);
  app.onUnmount(() => cpComponentRegistry.uninstall(app));
  app.config.compilerOptions.isCustomElement = (tag: string) =>
    tag.includes('-');
  app.mount(host);
}
