import {afterEach, describe, expect, it, vi} from 'vite-plus/test';
import {createApp, h, type App} from 'vue';
import type {ElementPreviewTarget} from '@/modules/elements/composables/useElementEditor';
import ElementViewButtons from './ElementViewButtons.vue';

vi.mock('@craftcms/ui', () => ({t: (message: string) => message}));

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

  it('names each of several targets', () => {
    const links = mount([
      {label: 'Entry page', url: '/a'},
      {label: 'Blog listing', url: '/b'},
    ]);

    expect(links.map(visibleLabel)).toEqual(['Entry page', 'Blog listing']);
  });

  it('links each target in a new window', () => {
    const links = mount([
      {label: 'Entry page', url: '/a'},
      {label: 'Blog listing', url: '/b'},
    ]);

    expect(
      links.map((link) => [
        link.getAttribute('href'),
        link.getAttribute('target'),
      ])
    ).toEqual([
      ['/a', '_blank'],
      ['/b', '_blank'],
    ]);
  });

  it('tells screen reader users each link opens a new window', () => {
    const links = mount([
      {label: 'Entry page', url: '/a'},
      {label: 'Blog listing', url: '/b'},
    ]);

    expect(
      links.map((link) => link.querySelector('.sr-only')?.textContent)
    ).toEqual(['Opens in a new window', 'Opens in a new window']);
  });
});
