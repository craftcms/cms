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

function mount(start: number | null): void {
  position.value = start;
  container = document.createElement('div');
  document.body.append(container);
  const app = createApp({
    render: () =>
      h(ItemPreviewModal, {
        step: {uid: 'step'} as ImportStep,
        items,
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
