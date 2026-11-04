import {afterEach, describe, expect, it, vi} from 'vite-plus/test';
import {createApp, h, type App} from 'vue';
import type {ElementPreviewTarget} from '@/modules/elements/composables/useElementEditor';
import ElementViewButtons from './ElementViewButtons.vue';

vi.mock('@craftcms/ui', async (importOriginal) => ({
  ...(await importOriginal<typeof import('@craftcms/ui')>()),
  t: (message: string) => message,
}));

let app: App | undefined;
let container: HTMLElement | undefined;

function mount(targets: Array<ElementPreviewTarget>): HTMLElement[] {
  container = document.createElement('div');
  document.body.append(container);
  app = createApp({render: () => h(ElementViewButtons, {targets})});
  app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
  app.mount(container);
  return [...container.querySelectorAll<HTMLElement>('craft-button')];
}

afterEach(() => {
  app?.unmount();
  container?.remove();
});

/** The visible label, without the screen-reader-only new window notice. */
function visibleLabel(link: HTMLElement): string | undefined {
  return [...link.childNodes]
    .filter((node) => node.nodeType === Node.TEXT_NODE)
    .map((node) => node.textContent)
    .join('')
    .trim();
}

describe('ElementViewButtons', () => {
  it('calls a lone target View', () => {
    const [link] = mount([{label: 'Primary entry page', url: '/a'}]);

    expect(visibleLabel(link!)).toBe('View');
  });

  it('links a lone target in a new window, saying so to screen readers', () => {
    const [link] = mount([{label: 'Primary entry page', url: '/a'}]);

    expect(link).toMatchObject({href: '/a', target: '_blank'});
    expect(link!.querySelector('.sr-only')?.textContent).toBe(
      'Opens in a new window'
    );
  });

  it('lists several targets in a View menu, each opening in a new window', () => {
    mount([
      {label: 'Entry page', url: '/a'},
      {label: 'Blog listing', url: '/b'},
    ]);

    const menu = container!.querySelector('craft-action-menu');
    const invoker = menu?.querySelector('[slot="invoker"]');
    const items = [...menu!.querySelectorAll('craft-action-item')];

    expect(visibleLabel(invoker as HTMLElement)).toBe('View');
    expect(
      items.map((item) => [item.textContent?.trim(), item.href, item.target])
    ).toEqual([
      ['Entry page', '/a', '_blank'],
      ['Blog listing', '/b', '_blank'],
    ]);
  });
});
