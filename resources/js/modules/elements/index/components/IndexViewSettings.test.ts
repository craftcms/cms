import {createApp, h, nextTick, ref, type App} from 'vue';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import IndexViewSettings from './IndexViewSettings.vue';

vi.mock('./ViewSettingsFields.vue', () => ({
  default: {render: () => null, methods: {takeSnapshot: () => {}}},
}));

let app: App | undefined;
let host: HTMLElement | undefined;

afterEach(() => {
  app?.unmount();
  host?.remove();
});

async function mountSettings() {
  const sortDirection = ref<'asc' | 'desc'>('asc');
  host = document.createElement('div');
  document.body.append(host);
  app = createApp({
    setup: () => () =>
      h(IndexViewSettings, {
        options: [],
        sortOptions: [],
        sortField: 'title',
        tableColumns: [],
        sortDirection: sortDirection.value,
        'onUpdate:sortDirection': (value: 'asc' | 'desc') =>
          (sortDirection.value = value),
      }),
  });
  app.mount(host);
  await nextTick();

  const popover = host.querySelector('craft-popover')!;
  popover.dispatchEvent(new CustomEvent('craft-show'));
  // A change made in the menu, as its fields would make it.
  sortDirection.value = 'desc';
  await nextTick();

  return {popover, sortDirection};
}

function button(label: string): HTMLElement {
  return [...host!.querySelectorAll<HTMLElement>('craft-button')].find(
    (candidate) => candidate.textContent?.trim() === label
  )!;
}

it('keeps changes when the menu closes without Apply', async () => {
  const {popover, sortDirection} = await mountSettings();

  popover.dispatchEvent(new CustomEvent('craft-hide'));
  await nextTick();

  expect(sortDirection.value).toBe('desc');
});

it('puts the settings back on Cancel', async () => {
  const {sortDirection} = await mountSettings();

  button('Cancel').click();
  await nextTick();

  expect(sortDirection.value).toBe('asc');
});
