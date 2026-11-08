import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import {ref} from 'vue';
import '@craftcms/ui/components/dialog/dialog';
import '@craftcms/ui/components/button/button';
import '@craftcms/ui/components/tabs/tabs';
import '@craftcms/ui/components/tab/tab';
import '@craftcms/ui/components/tooltip/tooltip';
import type CraftDialog from '@craftcms/ui/components/dialog/dialog';
import {openImageEditorDialog} from './open-image-editor-dialog';

const reset = vi.hoisted(() => vi.fn());

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
    reset,
  }),
}));

const nativeConfirm = vi.fn(() => false);

beforeEach(async () => {
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
  await expectOpenDialogs(['Edit Image']);
});

afterEach(async () => {
  const editor = document.querySelector<CraftDialog>('.image-editor-dialog');

  if (editor) {
    editor.opened = false;
    await vi.waitFor(() =>
      expect(document.querySelector('.image-editor-dialog')).toBeNull()
    );
  }

  document.body.innerHTML = '';
  vi.unstubAllGlobals();
  vi.clearAllMocks();
});

function openDialogLabels(): string[] {
  return [...document.querySelectorAll<CraftDialog>('craft-dialog')]
    .filter((dialog) => dialog.opened)
    .map((dialog) => dialog.label);
}

async function expectOpenDialogs(labels: string[]): Promise<void> {
  await vi.waitFor(() => expect(openDialogLabels()).toEqual(labels));
}

function clickButton(label: string, within: ParentNode): void {
  const button = [...within.querySelectorAll('craft-button')].find(
    (candidate) => candidate.textContent?.trim() === label
  );

  (button as HTMLElement).click();
}

function editorDialog(): CraftDialog {
  return document.querySelector<CraftDialog>('.image-editor-dialog')!;
}

function confirmationDialog(): CraftDialog {
  return document.querySelector<CraftDialog>('craft-dialog:not([fullscreen])')!;
}

function requestEditorClose(): void {
  editorDialog()
    .shadowRoot!.querySelector<HTMLElement>('[part="close"]')!
    .click();
}

it('confirms closing an edited image in a dialog of its own', async () => {
  requestEditorClose();
  await expectOpenDialogs(['Edit Image', 'Discard changes']);

  expect(nativeConfirm).not.toHaveBeenCalled();

  clickButton('Cancel', confirmationDialog());
  await expectOpenDialogs(['Edit Image']);

  requestEditorClose();
  await expectOpenDialogs(['Edit Image', 'Discard changes']);
  clickButton('Discard changes', confirmationDialog());

  await vi.waitFor(() =>
    expect(document.querySelector('craft-dialog')).toBeNull()
  );
});

it('keeps the editor open when one of its tooltips hides', async () => {
  const tooltip = editorDialog().querySelector<HTMLElement & {opened: boolean}>(
    'craft-tooltip'
  )!;
  const hidden = new Promise((resolve) =>
    tooltip.addEventListener('craft-after-hide', resolve, {once: true})
  );

  tooltip.opened = true;
  await vi.waitFor(() => expect(tooltip.opened).toBe(true));
  tooltip.opened = false;
  await hidden;
  await new Promise((resolve) => setTimeout(resolve));

  expect(openDialogLabels()).toEqual(['Edit Image']);
  expect(nativeConfirm).not.toHaveBeenCalled();
});

it('resets the image after confirming, and keeps focus in the editor', async () => {
  clickButton('Reset to original', editorDialog());
  await expectOpenDialogs(['Edit Image', 'Reset to original']);

  expect(nativeConfirm).not.toHaveBeenCalled();
  expect(reset).not.toHaveBeenCalled();

  clickButton('Reset to original', confirmationDialog());
  await expectOpenDialogs(['Edit Image']);

  await vi.waitFor(() => {
    expect(reset).toHaveBeenCalledOnce();
    expect(document.activeElement).toBe(
      editorDialog().querySelector('craft-tab[selected]')
    );
  });
});
