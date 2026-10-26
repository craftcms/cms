import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import {VolumeFolderSelectorModal} from './volume-folder-selector-modal';

/**
 * Stands in for the real index, which needs the whole element-index tree. Like
 * the real one, it reports the open folder's breadcrumb trail as it mounts.
 */
const picker = vi.hoisted(() => ({
  lastProps: null as Record<string, any> | null,
}));

vi.mock('./ElementPicker.vue', async () => {
  const {defineComponent, h} = await import('vue');

  return {
    default: defineComponent({
      name: 'ElementPickerStub',
      props: [
        'action',
        'initial',
        'params',
        'selectFolders',
        'disabledFolderIds',
      ],
      emits: ['selection-change', 'choose', 'folder-path-change'],
      setup(props, {emit}) {
        emit('folder-path-change', props.initial.folderBreadcrumbs);

        return () => {
          picker.lastProps = props as Record<string, any>;
          return h('div', {class: 'modal-element-index'});
        };
      },
    }),
  };
});

/** A volume's subfolder, open in the picker. */
const folderData = {
  folderBreadcrumbs: [
    {label: 'Uploads', folderId: 1, url: null},
    {label: 'Photos', folderId: 5, url: null},
  ],
};

function createModal(settings: Record<string, unknown> = {}) {
  return new VolumeFolderSelectorModal({
    loadIndexBody: async () => ({html: '', props: folderData}),
    ...settings,
  } as any);
}

/** Mounting imports the Vue modal, then loads the index into it. */
async function settle(modal: VolumeFolderSelectorModal): Promise<void> {
  await vi.waitFor(
    () => {
      if (!modal.element.querySelector('.modal-element-index')) {
        throw new Error('Not mounted yet');
      }
    },
    {timeout: 5000}
  );
  await new Promise((r) => setTimeout(r, 0));
}

beforeEach(() => {
  vi.stubGlobal('Craft', {
    systemUid: 'test',
    pageTrigger: 'p',
    t: (_category: string, message: string) => message,
  });
});

afterEach(() => {
  vi.unstubAllGlobals();
  document.body.replaceChildren();
});

it('chooses the open folder when nothing is highlighted', async () => {
  const onSelect = vi.fn();
  const modal = createModal({onSelect});
  await settle(modal);

  expect(modal.controller.state.canSubmit).toBe(true);

  await modal.controller.submit();

  expect(onSelect).toHaveBeenCalledWith(
    [expect.objectContaining({folderId: 5, label: 'Photos'})],
    expect.anything()
  );
});

it('won’t choose the open folder when it’s one being moved', async () => {
  const modal = createModal({disabledFolderIds: [5]});
  await settle(modal);

  expect(modal.controller.state.canSubmit).toBe(false);
  expect(picker.lastProps?.disabledFolderIds).toEqual([5]);
});

it('removes itself once the modal has hidden, but not when a menu in it does', async () => {
  const modal = createModal();
  await settle(modal);

  const dialog = modal.element.querySelector('craft-element-selector-modal')!;
  const menu = document.createElement('craft-action-menu');
  dialog.append(menu);

  menu.dispatchEvent(new CustomEvent('craft-after-hide', {bubbles: true}));
  expect(document.body.contains(modal.element)).toBe(true);

  dialog.dispatchEvent(new CustomEvent('craft-after-hide', {bubbles: true}));
  expect(document.body.contains(modal.element)).toBe(false);
});
