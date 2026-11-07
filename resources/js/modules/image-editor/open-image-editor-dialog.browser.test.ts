import {afterEach, expect, it, vi} from 'vite-plus/test';
import {ref} from 'vue';
import '@craftcms/ui/components/dialog/dialog';
import '@craftcms/ui/components/button/button';
import type CraftDialog from '@craftcms/ui/components/dialog/dialog';
import {openImageEditorDialog} from './open-image-editor-dialog';

vi.mock('./useImageEditor', () => ({
  useImageEditor: () => ({
    state: {
      currentView: ref('rotate'),
      imageStraightenAngle: ref(0),
      focalPoint: ref(null),
    },
    editing: {rectanglePickedUp: ref(false), pickedHandle: ref(null)},
    isDirty: ref(true),
    isReady: ref(true),
    canUndo: ref(true),
    canRedo: ref(false),
    isSaving: ref(false),
    savingAs: ref(null),
    cursor: ref('default'),
    setUiAdapter: () => {},
    start: () => {},
  }),
}));

afterEach(() => {
  document.body.innerHTML = '';
  vi.unstubAllGlobals();
});

function openDialogs(): CraftDialog[] {
  return [...document.querySelectorAll<CraftDialog>('craft-dialog')].filter(
    (dialog) => dialog.opened
  );
}

async function settle(): Promise<void> {
  for (let i = 0; i < 5; i++) {
    await new Promise((resolve) => setTimeout(resolve));
  }
}

function clickButton(label: string): void {
  const dialog = openDialogs().at(-1)!;
  const button = [...dialog.querySelectorAll('craft-button')].find(
    (candidate) => candidate.textContent?.trim() === label
  );

  (button as HTMLElement).click();
}

function requestEditorClose(): void {
  document
    .querySelector<CraftDialog>('craft-dialog[fullscreen]')!
    .shadowRoot!.querySelector<HTMLElement>('[part="close"]')!
    .click();
}

it('confirms closing an edited image in a dialog of its own', async () => {
  const nativeConfirm = vi.fn(() => false);
  vi.stubGlobal('confirm', nativeConfirm);

  await openImageEditorDialog({
    assetId: 1,
    filename: 'photo.jpg',
    focalPoint: null,
    imageWidth: 800,
    imageHeight: 600,
    imageEditorRatios: {},
    allowDegreeFractions: false,
    orientation: 'ltr',
  });
  await settle();

  requestEditorClose();
  await settle();

  expect(nativeConfirm).not.toHaveBeenCalled();
  expect(openDialogs().map((dialog) => dialog.label)).toEqual([
    'Edit Image',
    'Discard changes',
  ]);

  clickButton('Cancel');
  await settle();

  expect(openDialogs().map((dialog) => dialog.label)).toEqual(['Edit Image']);

  requestEditorClose();
  await settle();
  clickButton('Discard changes');
  await settle();

  expect(openDialogs()).toEqual([]);
  expect(document.querySelector('craft-dialog')).toBeNull();
});
