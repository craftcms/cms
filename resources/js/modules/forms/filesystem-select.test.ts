import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import {createApp, h, nextTick} from 'vue';

const state = vi.hoisted(() => ({
  createUrl: '',
  slideoutCount: 0,
  onClose: () => {},
  onSubmit: (_event: {data: {name: string; handle: string}}) => {},
}));

vi.mock('@/modules/slideout/cp-screen-slideout', () => ({
  CpScreenSlideout: class {
    constructor(
      createUrl: string,
      settings: {
        onSubmit: (event: {data: {name: string; handle: string}}) => void;
      }
    ) {
      state.createUrl = createUrl;
      state.slideoutCount++;
      state.onSubmit = settings.onSubmit;
    }

    on(event: string, callback: () => void) {
      if (event === 'close') {
        state.onClose = callback;
      }
    }
  },
}));

let filesystemSelect: HTMLElementTagNameMap['craft-filesystem-select'];

beforeEach(async () => {
  await import('./filesystem-select');
  state.createUrl = '';
  state.slideoutCount = 0;
  state.onClose = () => {};
  filesystemSelect = document.createElement('craft-filesystem-select');
  filesystemSelect.createUrl = '/settings/filesystems/new';
  filesystemSelect.options = [
    {
      type: 'optgroup',
      label: 'Craft Filesystems',
      options: [{label: 'Create a new filesystem…', value: '__add__'}],
    },
  ];
  document.body.append(filesystemSelect);
  await filesystemSelect.updateComplete;
});

afterEach(() => filesystemSelect.remove());

it('selects a filesystem created in the slideout', async () => {
  const selectedValues: string[] = [];
  filesystemSelect.addEventListener('model-value-changed', () => {
    selectedValues.push(filesystemSelect.modelValue);
  });
  filesystemSelect.modelValue = '__add__';
  filesystemSelect.dispatchEvent(new CustomEvent('model-value-changed'));
  filesystemSelect.dispatchEvent(new CustomEvent('model-value-changed'));
  await Promise.resolve();

  expect(state.createUrl).toBe('/settings/filesystems/new');
  expect(state.slideoutCount).toBe(1);
  expect(filesystemSelect.modelValue).toBe('');

  state.onSubmit({data: {name: 'Uploads', handle: 'uploads'}});

  await vi.waitFor(() => {
    expect(filesystemSelect.modelValue).toBe('uploads');
    expect(selectedValues).toContain('uploads');
  });
  expect(filesystemSelect.options[0]).toMatchObject({
    options: [
      {label: 'Uploads', value: 'uploads'},
      {label: 'Create a new filesystem…', value: '__add__'},
    ],
  });
});

it('binds Vue control options and behavior as element properties', async () => {
  const {default: FilesystemSelectControl} =
    await import('./FilesystemSelectControl.vue');
  const control = {
    type: 'CraftCms\\Cms\\Form\\Controls\\FilesystemSelect',
    component: 'craft:filesystem-select',
    props: {
      options: [{label: 'Uploads', value: 'uploads'}],
      createUrl: '/settings/filesystems/new',
      clearable: true,
      requireOptionMatch: true,
      showAllOnEmpty: true,
      showSelectedHint: true,
    },
    path: ['fs'],
    mode: 'editable' as const,
    deltaGroup: ['fs'],
  };
  const container = document.createElement('div');
  document.body.append(container);
  const app = createApp({
    render: () =>
      h(FilesystemSelectControl, {
        control,
        value: 'uploads',
        editable: true,
        invalid: false,
        required: false,
      }),
  });

  app.mount(container);
  await nextTick();
  const select = container.querySelector('craft-filesystem-select')!;
  await select.updateComplete;
  await vi.waitFor(() => expect(select.modelValue).toBe('uploads'));

  expect({
    modelValue: select.modelValue,
    options: select.options,
    createUrl: select.createUrl,
    clearable: select.clearable,
    requireOptionMatch: select.requireOptionMatch,
    showAllOnEmpty: select.showAllOnEmpty,
    showSelectedHint: select.showSelectedHint,
    limit: select.limit,
  }).toEqual({
    modelValue: 'uploads',
    options: control.props.options,
    createUrl: '/settings/filesystems/new',
    clearable: true,
    requireOptionMatch: true,
    showAllOnEmpty: true,
    showSelectedHint: true,
    limit: 150,
  });

  app.unmount();
  container.remove();
});
