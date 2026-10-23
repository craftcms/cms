import {createApp, h, nextTick, ref} from 'vue';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import NestedElementsCreateButton from './NestedElementsCreateButton.vue';

vi.mock('@vueuse/core', async (original) => ({
  ...(await original<Record<string, unknown>>()),
  useElementSize: () => ({width: ref(10), height: ref(10)}),
}));

let teardown: (() => void) | undefined;

beforeEach(() => {
  vi.spyOn(HTMLElement.prototype, 'scrollWidth', 'get').mockReturnValue(200);
});

afterEach(() => {
  teardown?.();
  teardown = undefined;
  vi.restoreAllMocks();
});

async function mount(choices: Array<{value: string; label: string}>) {
  const onCreate = vi.fn();
  const container = document.createElement('div');
  document.body.append(container);
  const app = createApp({
    render: () =>
      h(NestedElementsCreateButton, {
        choices,
        label: 'New variant',
        disabled: false,
        onCreate,
      }),
  });

  app.mount(container);
  teardown = () => {
    app.unmount();
    container.remove();
  };
  await nextTick();
  await nextTick();

  return {container, onCreate};
}

it('creates directly from a single choice even when the area is too narrow', async () => {
  const {container, onCreate} = await mount([
    {value: 'variant', label: 'New variant'},
  ]);

  expect(container.querySelector('craft-action-menu')).toBeNull();
  const buttons = container.querySelectorAll<HTMLElement>(
    '[data-create-element]'
  );
  expect(buttons).toHaveLength(1);
  expect(buttons[0]!.textContent?.trim()).toBe('New variant');

  buttons[0]!.click();

  expect(onCreate).toHaveBeenCalledWith('variant', buttons[0]);
});

it('moves multiple choices into a menu when the area is too narrow', async () => {
  const {container} = await mount([
    {value: 'text', label: 'Text'},
    {value: 'quote', label: 'Quote'},
  ]);

  expect(container.querySelector('craft-action-menu')).not.toBeNull();
});
