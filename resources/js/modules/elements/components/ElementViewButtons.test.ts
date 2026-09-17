import {afterEach, describe, expect, it, vi} from 'vite-plus/test';
import {createApp, h, type App} from 'vue';
import ElementViewButtons, {
  type ElementPreviewTarget,
} from './ElementViewButtons.vue';

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
  vi.restoreAllMocks();
});

describe('ElementViewButtons', () => {
  it('calls a lone target View', () => {
    const [button] = mount([{label: 'Primary entry page', url: '/a'}]);

    expect(button!.textContent?.trim()).toBe('View');
  });

  it('names each of several targets', () => {
    const buttons = mount([
      {label: 'Entry page', url: '/a'},
      {label: 'Blog listing', url: '/b'},
    ]);

    expect(buttons.map((button) => button.textContent?.trim())).toEqual([
      'Entry page',
      'Blog listing',
    ]);
  });

  it('opens the target in a new tab', () => {
    const open = vi.spyOn(window, 'open').mockReturnValue(null);
    const [button] = mount([{label: 'Entry page', url: '/a'}]);

    button!.click();

    expect(open).toHaveBeenCalledWith('/a', '_blank', 'noopener');
  });
});
