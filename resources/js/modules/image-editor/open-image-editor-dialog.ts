import type {App} from 'vue';
import type {RelativeFocalPoint} from './types';
import type {SaveResult} from './useImageEditor';

/** The props `ImageEditorDialog` opens an asset with, as the server sends them. */
export interface ImageEditorSettings {
  assetId: number;
  filename: string;
  focalPoint: RelativeFocalPoint | null;
  imageWidth: number | null;
  imageHeight: number | null;
  imageEditorRatios: Record<string, string | number>;
  allowDegreeFractions: boolean;
  orientation: 'ltr' | 'rtl';
}

/**
 * Opens the image editor dialog away from the asset edit screen, which renders
 * its own — for the “Open in Image Editor” action on asset chips and cards.
 *
 * The dialog and the editor behind it are imported on demand, so the CP bundle
 * doesn't carry them onto pages that never open it.
 */
export async function openImageEditorDialog(
  settings: ImageEditorSettings,
  onSaved?: (result: SaveResult) => void
): Promise<void> {
  const [{createApp, h, ref}, {default: ImageEditorDialog}] = await Promise.all(
    [import('vue'), import('./components/ImageEditorDialog.vue')]
  );

  const host = document.createElement('div');
  document.body.append(host);

  const open = ref(true);

  const app: App = createApp({
    render: () =>
      h(ImageEditorDialog, {
        ...settings,
        open: open.value,
        'onUpdate:open': (value: boolean) => {
          open.value = value;
        },
        onSaved: (result: SaveResult) => onSaved?.(result),
      }),
  });

  // Torn down once the editor's dialog has finished closing, so its exit
  // transition and focus return aren't cut short. Its confirmation and tooltips
  // fire the same event from inside the host while the editor stays open.
  host.addEventListener(
    'craft-after-hide',
    (event) => {
      if (!(event.target as Element).matches('.image-editor-dialog')) {
        return;
      }

      app.unmount();
      host.remove();
    },
    {capture: true}
  );

  app.config.compilerOptions.isCustomElement = (tag: string) =>
    tag.includes('-');
  app.mount(host);
}
