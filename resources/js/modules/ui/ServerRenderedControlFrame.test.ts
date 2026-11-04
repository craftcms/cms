import {actionClient} from '@craftcms/ui';
import {createApp, h, nextTick} from 'vue';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import EntryTypeSelectControl from './EntryTypeSelectControl.vue';

const html =
  '<ul data-field-html><li>Article</li><li>Page</li></ul><button data-field-button>Choose</button>';

let app: ReturnType<typeof createApp> | undefined;

afterEach(() => {
  app?.unmount();
  app = undefined;
  document.body.innerHTML = '';
  vi.restoreAllMocks();
});

const flush = async () => {
  for (let i = 0; i < 5; i++) {
    await Promise.resolve();
    await nextTick();
  }
};

function mount(): HTMLElement {
  const container = document.createElement('div');
  document.body.append(container);

  app = createApp({
    render: () =>
      h(EntryTypeSelectControl, {
        control: {path: ['entryTypes'], props: {create: true}} as never,
        value: [{id: 1}, {id: 2}],
        editable: true,
      }),
  });
  app.mount(container);

  return container;
}

it('shows placeholder rows while loading, then the real field', async () => {
  let resolve!: (response: unknown) => void;
  vi.spyOn(actionClient, 'post').mockReturnValue(
    new Promise((r) => (resolve = r)) as never
  );
  const container = mount();
  await flush();

  const placeholder = () =>
    container.querySelector('[data-server-rendered-control-placeholder]');

  expect(placeholder()?.getAttribute('aria-hidden')).toBe('true');
  expect(placeholder()?.querySelectorAll('li')).toHaveLength(2);
  expect(container.querySelector('[data-field-html]')).toBeNull();

  resolve({data: {html, headHtml: '', bodyHtml: ''}});
  await flush();

  expect(placeholder()).toBeNull();
  expect(container.querySelector('[data-field-html]')).not.toBeNull();
});

it('shows an error with a retry button when the field fails to load', async () => {
  vi.spyOn(console, 'error').mockImplementation(() => {});
  vi.spyOn(actionClient, 'post')
    .mockRejectedValueOnce(new Error('Server error'))
    .mockResolvedValueOnce({
      data: {html, headHtml: '', bodyHtml: ''},
    } as never);
  const container = mount();
  await flush();

  const alert = () => container.querySelector('[role="alert"]');

  expect(
    container.querySelector('[data-server-rendered-control-placeholder]')
  ).toBeNull();
  expect(alert()?.textContent).toContain('Couldn’t load this field.');

  const retryButton = alert()!.querySelector<HTMLElement>('craft-button')!;
  retryButton.focus();
  retryButton.click();
  await flush();

  expect(alert()?.textContent?.trim()).toBe('');
  expect(container.querySelector('[data-field-html]')).not.toBeNull();
  expect(document.activeElement).toBe(
    container.querySelector('[data-field-button]')
  );
});
