import type {App} from 'vue';
import type {ConflictResolution} from '@/modules/assets/assetMover';
import type {UploadDuplicate} from './upload-duplicates-notification.ce';

export interface UploadDuplicatesChoice {
  choice: ConflictResolution;
  /** The image that was shown, which Replace replaces. */
  assetId: number;
}

/**
 * Compares an upload with the images it looks like a copy of, in a detached Vue
 * app that's removed once the modal has closed. Resolves with the choice made in
 * it, or `undefined` if it was dismissed, after focus has gone back to where it
 * was before it opened.
 */
export async function openUploadDuplicatesModal(props: {
  file: File;
  location: string;
  duplicates: UploadDuplicate[];
}): Promise<UploadDuplicatesChoice | undefined> {
  const [{createApp, h, shallowRef}, {default: UploadDuplicatesModal}] =
    await Promise.all([import('vue'), import('./UploadDuplicatesModal.vue')]);

  const host = document.createElement('div');
  document.body.append(host);
  // Opened once it's mounted, so it transitions in
  const active = shallowRef(false);
  let result: UploadDuplicatesChoice | undefined;

  return new Promise((resolve) => {
    const app: App = createApp({
      render: () =>
        h(UploadDuplicatesModal, {
          ...props,
          isActive: active.value,
          onClose: () => (active.value = false),
          onChoose: (choice: ConflictResolution, assetId: number) => {
            result ??= {choice, assetId};
            active.value = false;
          },
          onClosed: () => {
            app.unmount();
            host.remove();
            resolve(result);
          },
        }),
    });
    app.config.compilerOptions.isCustomElement = (tag: string) =>
      tag.includes('-');
    app.mount(host);
    active.value = true;
  });
}
