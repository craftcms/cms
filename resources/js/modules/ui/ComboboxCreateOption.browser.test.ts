import '../../../css/cp.css';
import {createApp, h, nextTick, ref} from 'vue';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import {page, userEvent} from 'vite-plus/test/browser/context';
import type CraftCombobox from '@craftcms/ui/components/combobox/combobox';
import type {ComboboxItem} from '@craftcms/ui/components/combobox/combobox';
import '@craftcms/ui/components/combobox/combobox';
import CraftComboboxVue from '@craftcms/ui/vue/CraftCombobox.vue';
import ComboboxControl from './ComboboxControl.vue';

const slideout = vi.hoisted(() => ({openSlideout: vi.fn()}));
vi.mock('@/common/slideouts', () => slideout);

const options = [
  {label: 'Existing', value: '42'},
  {
    label: 'Create category',
    value: '__add__',
    data: {
      create: {
        url: '/categories/new',
        resultKey: 'category',
        labelField: 'name',
        valueField: 'id',
      },
    },
  },
];

let app: ReturnType<typeof createApp> | undefined;
let container: HTMLElement;
afterEach(() => {
  app?.unmount();
  container?.remove();
  slideout.openSlideout.mockReset();
});

async function fixture(multiple = false, items: ComboboxItem[] = options) {
  container = document.createElement('form');
  document.body.append(container);
  const selection = ref<string | string[]>(multiple ? ['42'] : '42');
  const changes = vi.fn(
    (value: string | string[]) => (selection.value = value)
  );
  app = createApp({
    render: () =>
      h(ComboboxControl, {
        control: {
          component: 'craft:combobox',
          type: 'Combobox',
          path: ['category'],
          deltaGroup: ['category'],
          mode: 'editable',
          props: {options: items, multiple},
        },
        value: selection.value,
        editable: true,
        invalid: false,
        required: false,
        'onUpdate:value': changes,
      }),
  });
  app.mount(container);
  await nextTick();
  const combobox = container.querySelector('craft-combobox') as CraftCombobox;
  await combobox.updateComplete;
  await vi.waitFor(() =>
    expect(combobox.querySelectorAll('craft-option')).toHaveLength(2)
  );
  return {combobox, selection, changes};
}

it('selects ordinary options without opening a slideout', async () => {
  const {combobox, selection} = await fixture();
  selection.value = '';
  await nextTick();
  combobox.querySelectorAll<HTMLElement>('craft-option')[0]!.click();

  await vi.waitFor(() => expect(selection.value).toBe('42'));
  expect(slideout.openSlideout).not.toHaveBeenCalled();
});

it('exposes a native selection event before syncing the Vue model', async () => {
  container = document.createElement('div');
  document.body.append(container);
  const selection = ref('');
  const updated = vi.fn(
    (value: string | string[] | number | boolean | undefined) =>
      (selection.value = String(value ?? ''))
  );
  app = createApp({
    render: () =>
      h(CraftComboboxVue, {
        options,
        modelValue: selection.value,
        'onUpdate:modelValue': updated,
        'onModel-value-changed': (
          event: CustomEvent,
          cancelModelUpdate: () => void
        ) => {
          if ((event.target as CraftCombobox).modelValue === '__add__') {
            cancelModelUpdate();
          }
        },
      }),
  });
  app.mount(container);
  const combobox = container.querySelector('craft-combobox') as CraftCombobox;
  await vi.waitFor(() =>
    expect(combobox.querySelectorAll('craft-option')).toHaveLength(2)
  );

  combobox.querySelectorAll<HTMLElement>('craft-option')[0]!.click();
  await vi.waitFor(() => expect(selection.value).toBe('42'));
  updated.mockClear();

  combobox.querySelectorAll<HTMLElement>('craft-option')[1]!.click();
  await nextTick();
  expect(updated).not.toHaveBeenCalled();
});

it('opens the create screen when its option is selected by keyboard', async () => {
  slideout.openSlideout.mockResolvedValue(null);
  const {combobox, selection} = await fixture();
  combobox.querySelector('input')!.focus();
  await userEvent.keyboard('{ArrowDown}');
  await expect.element(page.getByRole('listbox')).toBeVisible();
  await userEvent.keyboard('{ArrowUp}{Enter}');

  await vi.waitFor(() =>
    expect(slideout.openSlideout).toHaveBeenCalledTimes(1)
  );
  await vi.waitFor(() => expect(selection.value).toBe('42'));
  expect(combobox.modelValue).toBe('42');
});

it.each([false, true])(
  'keeps the prior selection until the created record is saved (multiple: %s)',
  async (multiple) => {
    slideout.openSlideout.mockResolvedValue(null);
    const {combobox, selection, changes} = await fixture(multiple);
    combobox.querySelectorAll<HTMLElement>('craft-option')[1]!.click();

    await vi.waitFor(() =>
      expect(slideout.openSlideout).toHaveBeenCalledTimes(1)
    );
    await vi.waitFor(() =>
      expect(combobox.modelValue).toEqual(multiple ? ['42'] : '42')
    );
    expect(selection.value).toEqual(multiple ? ['42'] : '42');
    await vi.waitFor(() =>
      expect(
        new FormData(container as HTMLFormElement).getAll(
          multiple ? 'category[]' : 'category'
        )
      ).toEqual(['42'])
    );
    expect(changes.mock.calls.flatMap(([value]) => value)).not.toContain(
      '__add__'
    );

    const [url, config] = slideout.openSlideout.mock.calls[0]!;
    expect(url).toBe('/categories/new');
    config.onSaved({data: {category: {name: 'New category', id: 73}}});
    await vi.waitFor(() =>
      expect(selection.value).toEqual(multiple ? ['42', '73'] : '73')
    );
    expect(
      combobox.options.flatMap((item) =>
        item.type === 'optgroup'
          ? item.options.map((option) => option.value)
          : [item.value]
      )
    ).toEqual(['42', '73', '__add__']);
    expect(changes).toHaveBeenLastCalledWith(
      multiple ? ['42', '73'] : '73',
      'discrete'
    );
    await vi.waitFor(() =>
      expect(
        new FormData(container as HTMLFormElement).getAll(
          multiple ? 'category[]' : 'category'
        )
      ).toEqual(multiple ? ['42', '73'] : ['73'])
    );
    if (!multiple) {
      await vi.waitFor(() =>
        expect(combobox.querySelector('input')?.value).toBe('New category')
      );
    }
  }
);

it('selects a created volume by UID rather than its numeric ID', async () => {
  slideout.openSlideout.mockResolvedValue(null);
  const {combobox, selection} = await fixture(false, [
    {label: 'Existing', value: '42'},
    {
      label: 'Create a new volume…',
      value: '__createVolume__',
      data: {
        create: {
          url: '/settings/assets/new',
          resultKey: 'volume',
          labelField: 'name',
          valueField: 'uid',
        },
      },
    },
  ]);
  combobox.querySelectorAll<HTMLElement>('craft-option')[1]!.click();

  await vi.waitFor(() =>
    expect(slideout.openSlideout).toHaveBeenCalledTimes(1)
  );
  slideout.openSlideout.mock.calls[0]![1].onSaved({
    data: {volume: {id: 73, name: 'User Photos', uid: 'volume-uid'}},
  });

  await vi.waitFor(() => expect(selection.value).toBe('volume-uid'));
  expect(
    combobox.options.flatMap((item) =>
      item.type === 'optgroup'
        ? item.options.map((option) => option.value)
        : [item.value]
    )
  ).toEqual(['42', 'volume-uid', '__createVolume__']);
});
