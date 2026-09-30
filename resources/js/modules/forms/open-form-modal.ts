import type {App} from 'vue';
import type {FormValues} from './types';

export interface FormModalOptions {
  modalUrl: string;
  actionUrl: string;
  params?: FormValues;
  onSubmitted?: (data: Record<string, unknown>) => void;
}

/**
 * Opens a {@link FormModal} away from any component that could render one —
 * for action menu items, which only have a behavior to dispatch.
 *
 * It's mounted into a detached host with the CP's component registry
 * installed, so the Form's controls — plugins' included — resolve there the
 * same as on the page. The modal and its renderer are imported on demand.
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
