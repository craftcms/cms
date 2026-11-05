import {createApp, h, nextTick, type App} from 'vue';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import PaginationControls from './PaginationControls.vue';

type PageInput = HTMLElement & {value: string; disabled?: boolean};

let app: App | null = null;
let container: HTMLElement;

beforeEach(() => {
  vi.useFakeTimers();
  container = document.createElement('div');
  document.body.append(container);
});

afterEach(() => {
  app?.unmount();
  app = null;
  container.remove();
  vi.useRealTimers();
});

async function mount(props: Record<string, unknown> = {}) {
  const pageChange = vi.fn();
  const pageInput = vi.fn();
  app = createApp(() =>
    h(PaginationControls, {
      pageIndex: 1,
      pageSize: 50,
      pageCount: 20,
      onPageChange: pageChange,
      onPageInput: pageInput,
      ...props,
    })
  );
  app.mount(container);
  await nextTick();

  return {
    input: container.querySelector<PageInput>('craft-input')!,
    pageChange,
    pageInput,
  };
}

async function type(input: PageInput, value: string): Promise<void> {
  input.value = value;
  input.dispatchEvent(new Event('input', {bubbles: true}));
  await nextTick();
}

it('keeps the page input editable while a page loads', async () => {
  const {input} = await mount({disabled: true});

  expect(input.disabled).toBeFalsy();
});

it('waits for typing to pause before going to the typed page', async () => {
  const {input, pageChange, pageInput} = await mount();

  await type(input, '1');
  await type(input, '10');

  expect(pageInput).toHaveBeenCalledTimes(2);
  expect(pageChange).not.toHaveBeenCalled();
  expect(input.value).toBe('10');

  vi.advanceTimersByTime(500);

  expect(pageChange).toHaveBeenCalledExactlyOnceWith(9);
});

it('goes to the typed page right away on Enter', async () => {
  const {input, pageChange} = await mount();

  await type(input, '7');
  input.dispatchEvent(new KeyboardEvent('keydown', {key: 'Enter'}));

  expect(pageChange).toHaveBeenCalledExactlyOnceWith(6);
});

it('restores the current page when the typed one doesn’t exist', async () => {
  const {input, pageChange} = await mount();

  await type(input, '99');
  vi.advanceTimersByTime(500);
  await nextTick();

  expect(pageChange).not.toHaveBeenCalled();
  expect(input.value).toBe('2');
});

it('names the items with the given labels', async () => {
  await mount({
    from: 1,
    to: 50,
    total: 150,
    itemLabel: 'entry',
    itemsLabel: 'entries',
    pageSizeLabel: 'Entries per Page',
    enableAdjustPageSize: true,
  });

  expect(container.textContent).toContain('1 – 50 of 150 entries');
  expect(container.textContent).toContain('Entries per Page');
  expect(container.textContent).not.toContain('items');
});

it('names a single item with the singular label', async () => {
  await mount({
    from: 1,
    to: 1,
    total: 1,
    itemLabel: 'entry',
    itemsLabel: 'entries',
  });

  expect(container.textContent).toContain('1 – 1 of 1 entry');
});
