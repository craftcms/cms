import {createApp, defineComponent, h, nextTick} from 'vue';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import type {Source} from '@/modules/elements/types/sources';

const router = vi.hoisted(() => ({
  visit: vi.fn(),
  prefetch: vi.fn(),
}));

vi.mock('@inertiajs/vue3', () => ({router}));

vi.mock('@/common/composables/useCraftData', () => ({
  default: () => ({site: {handle: 'default'}}),
}));

const SOURCES: Source[] = [
  {type: 'source', key: 'section:news', label: 'News'} as unknown as Source,
  {type: 'source', key: 'section:pages', label: 'Pages'} as unknown as Source,
];

function visitor() {
  return {
    currentQuery: vi.fn(() => ({})),
    visit: vi.fn(),
    merge: vi.fn(),
  };
}

async function mount(props: Record<string, unknown>) {
  const {default: ElementSources} = await import('./ElementSources.vue');

  const app = createApp(
    defineComponent({
      setup: () => () =>
        h(ElementSources, {
          sources: SOURCES,
          route: {url: () => '/admin/entries'},
          ...props,
        }),
    })
  );
  app.config.compilerOptions.isCustomElement = (tag: string) =>
    tag.includes('-');

  const host = document.createElement('div');
  document.body.append(host);
  app.mount(host);
  await nextTick();

  return {host, unmount: () => app.unmount()};
}

/**
 * Picks a nav item by its label.
 *
 * Not by href: the route stub returns the same URL for every source, which is
 * also true of the modal, where the route exists only so the items render as
 * links at all.
 */
function sourceLink(host: HTMLElement, label: string): HTMLElement {
  const item = [...host.querySelectorAll('craft-nav-item')].find(
    (el) => el.textContent?.trim() === label
  );

  if (!item) {
    throw new Error(`No source nav item labelled “${label}”.`);
  }

  return item as HTMLElement;
}

beforeEach(() => {
  document.body.innerHTML = '';
  router.visit.mockClear();
  router.prefetch.mockClear();
});

afterEach(() => {
  document.body.innerHTML = '';
});

describe('ElementSources', () => {
  it('navigates the page with Inertia by default', async () => {
    const {host, unmount} = await mount({activeSource: 'section:news'});

    sourceLink(host, 'Pages').dispatchEvent(
      new MouseEvent('click', {bubbles: true, cancelable: true})
    );
    await nextTick();

    expect(router.visit).toHaveBeenCalledTimes(1);
    unmount();
  });

  describe('with an index visitor', () => {
    it('loads through the visitor instead of navigating', async () => {
      // Inside the element selector modal an Inertia visit would navigate the
      // page *behind* the modal — the bug this seam exists to stop.
      const indexVisitor = visitor();
      const {host, unmount} = await mount({
        activeSource: 'section:news',
        indexVisitor,
      });

      sourceLink(host, 'Pages').dispatchEvent(
        new MouseEvent('click', {bubbles: true, cancelable: true})
      );
      await nextTick();

      expect(router.visit).not.toHaveBeenCalled();
      expect(indexVisitor.merge).toHaveBeenCalledTimes(1);
      expect(indexVisitor.merge.mock.calls[0]![0]).toMatchObject({
        source: 'section:pages',
      });
      // A different source is a different result set, so page 1.
      expect(indexVisitor.merge.mock.calls[0]![1]).toMatchObject({
        resetPage: true,
      });
      unmount();
    });

    it('carries the active view mode through', async () => {
      const indexVisitor = visitor();
      const {host, unmount} = await mount({
        activeSource: 'section:news',
        viewMode: 'cards',
        indexVisitor,
      });

      sourceLink(host, 'Pages').dispatchEvent(
        new MouseEvent('click', {bubbles: true, cancelable: true})
      );
      await nextTick();

      expect(indexVisitor.merge.mock.calls[0]![0]).toMatchObject({
        viewMode: 'cards',
      });
      unmount();
    });

    it('does not prefetch — that is an Inertia notion', async () => {
      const indexVisitor = visitor();
      const {host, unmount} = await mount({
        activeSource: 'section:news',
        indexVisitor,
      });

      sourceLink(host, 'Pages').dispatchEvent(
        new MouseEvent('mousedown', {bubbles: true, cancelable: true})
      );
      await nextTick();

      expect(router.prefetch).not.toHaveBeenCalled();
      unmount();
    });

    it('ignores a click on the source already showing', async () => {
      const indexVisitor = visitor();
      const {host, unmount} = await mount({
        activeSource: 'section:news',
        indexVisitor,
      });

      sourceLink(host, 'News').dispatchEvent(
        new MouseEvent('click', {bubbles: true, cancelable: true})
      );
      await nextTick();

      expect(indexVisitor.merge).not.toHaveBeenCalled();
      unmount();
    });
  });
});
