import type {App} from 'vue';
import type {FormValues} from './types';

export interface FormModalOptions {
  modalUrl: string;
  actionUrl: string;
  params?: FormValues;
  title?: string;
  submitLabel?: string;
  width?: string;
  onSubmitted?: (data: Record<string, unknown>) => void;
}

/**
 * Opens a server-built form in a detached Vue app with registered CP controls.
 * The app, host, and registry installation are removed on close or submission.
 */
export async function openFormModal({
  onSubmitted,
  ...props
}: FormModalOptions): Promise<void> {
  const [{createApp, h}, {default: FormModal}, {cpComponentRegistry}] =
    await Promise.all([
      import('vue'),
      import('./FormModal.vue'),
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
      h(FormModal, {
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
