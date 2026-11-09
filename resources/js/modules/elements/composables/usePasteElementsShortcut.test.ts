import {createApp, h, nextTick, ref} from 'vue';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import {usePasteElementsShortcut} from './usePasteElementsShortcut';

vi.mock('../clipboard', () => ({
  isCopiedElementsText: (text: string) =>
    text.replace(/\r\n/g, '\n') === 'Apples\nPears',
}));

let container: HTMLElement;
let app: ReturnType<typeof createApp>;

afterEach(() => {
  app?.unmount();
  container?.remove();
});

async function mount(canPaste: () => boolean) {
  const paste = vi.fn();
  container = document.createElement('div');
  document.body.append(container);
  app = createApp({
    setup() {
      const root = ref<HTMLElement>();
      usePasteElementsShortcut(root, {canPaste, paste});

      return () =>
        h('div', {ref: root}, [
          h('button', {id: 'add'}, 'New entry'),
          h('input', {id: 'title'}),
          h('craft-field', [h('button', {id: 'nested'}, 'Nested')]),
        ]);
    },
  });
  app.mount(container);
  await nextTick();

  return paste;
}

function paste(selector: string, text: string): boolean {
  const event = new Event('paste', {
    bubbles: true,
    cancelable: true,
    composed: true,
  });
  Object.defineProperty(event, 'clipboardData', {
    value: {getData: (type: string) => (type === 'text/plain' ? text : '')},
  });
  container.querySelector(selector)!.dispatchEvent(event);

  return event.defaultPrevented;
}

it('pastes the copied elements while the clipboard holds their titles', async () => {
  const pasted = await mount(() => true);

  expect(paste('#add', 'Apples')).toBe(false);
  expect(paste('#title', 'Apples\nPears')).toBe(false);
  expect(paste('#nested', 'Apples\nPears')).toBe(false);
  expect(pasted).not.toHaveBeenCalled();

  expect(paste('#add', 'Apples\r\nPears')).toBe(true);
  expect(pasted).toHaveBeenCalledOnce();
});

it('leaves the paste alone when the copied elements can’t be pasted here', async () => {
  const pasted = await mount(() => false);

  expect(paste('#add', 'Apples\nPears')).toBe(false);
  expect(pasted).not.toHaveBeenCalled();
});
