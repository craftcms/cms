import {createApp, defineComponent, h, nextTick, ref} from 'vue';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import type {ImportStep} from '@/modules/import/mapping/types';
import ItemPreviewModal from './ItemPreviewModal.vue';
import type {PreviewItem} from './plan-preview';

vi.mock('./plan-preview', async (importOriginal) => ({
  ...(await importOriginal<typeof import('./plan-preview')>()),
  fetchItemPreview: vi.fn(() => new Promise(() => {})),
}));

vi.mock('@/common/components/Modal.vue', () => ({
  default: defineComponent({
    props: ['isActive'],
    setup(props, {slots}) {
      return () => (props.isActive ? h('div', slots.default?.()) : null);
    },
  }),
}));

let container: HTMLElement;
let unmount: () => void;
const position = ref<number | null>(null);

const items: PreviewItem[] = [0, 1, 2].map((index) => ({
  index,
  result: 'new',
  title: `Item ${index}`,
  match: {},
  errors: [],
}));

const navigated: number[] = [];

function mount(
  start: number | null,
  pages: {offset?: number; total?: number} = {}
): void {
  position.value = start;
  navigated.length = 0;
  container = document.createElement('div');
  document.body.append(container);
  const app = createApp({
    render: () =>
      h(ItemPreviewModal, {
        step: {uid: 'step'} as ImportStep,
        items,
        ...pages,
        onNavigate: (index: number) => navigated.push(index),
        modelValue: position.value,
        'onUpdate:modelValue': (value: number | null) =>
          (position.value = value),
      }),
  });
  app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
  app.mount(container);
  unmount = () => app.unmount();
}

function press(key: string, target: EventTarget = document.body): void {
  target.dispatchEvent(
    new KeyboardEvent('keydown', {key, bubbles: true, composed: true})
  );
}

afterEach(() => {
  unmount();
  container.remove();
  document.documentElement.dir = '';
});

it('moves to the next and previous item with the arrow keys, stopping at either end', async () => {
  mount(0);

  press('ArrowLeft');
  expect(position.value).toBe(0);

  press('ArrowRight');
  await nextTick();
  press('ArrowRight');
  await nextTick();
  expect(position.value).toBe(2);

  press('ArrowRight');
  expect(position.value).toBe(2);

  press('ArrowLeft');
  expect(position.value).toBe(1);
});

it('leaves the arrow keys alone while typing, while closed, or with a modifier', async () => {
  mount(1);

  const input = document.createElement('input');
  container.append(input);
  press('ArrowRight', input);
  expect(position.value).toBe(1);

  document.body.dispatchEvent(
    new KeyboardEvent('keydown', {key: 'ArrowRight', metaKey: true})
  );
  expect(position.value).toBe(1);

  position.value = null;
  await nextTick();
  press('ArrowRight');
  expect(position.value).toBeNull();
});

it('swaps the arrows in a right-to-left language', () => {
  document.documentElement.dir = 'rtl';
  mount(1);

  press('ArrowLeft');
  expect(position.value).toBe(2);
});

it('asks for the next or previous page at the ends of the one it has', async () => {
  // the page holds items 10 to 12 of 25
  mount(2, {offset: 10, total: 25});

  press('ArrowRight');
  expect(navigated).toEqual([13]);
  expect(position.value).toBe(2);

  position.value = 0;
  await nextTick();
  press('ArrowLeft');
  expect(navigated).toEqual([13, 9]);
});

it('stops at the last item of all the pages', () => {
  // the last page holds items 22 to 24 of 25
  mount(2, {offset: 22, total: 25});

  press('ArrowRight');

  expect(navigated).toEqual([]);
  expect(position.value).toBe(2);
});
