import {effectScope, nextTick, ref} from 'vue';
import {afterEach, describe, expect, it, vi} from 'vite-plus/test';
import type {ElementIndexRoute} from '@/modules/elements/composables/useElementIndexVisits';
import type {Source} from '@/modules/elements/types/sources';

const router = vi.hoisted(() => ({visit: vi.fn(), prefetch: vi.fn()}));

vi.mock('@inertiajs/vue3', () => ({router}));
vi.mock('@/common/composables/useCraftData', () => ({
  default: () => ({site: {handle: 'default'}}),
}));

const {useElementSourceActions} = await import('./useElementSourceActions');

const SOURCES: Array<Source> = [
  {type: 'native', key: '*', label: 'All entries'} as Source,
  {type: 'heading', heading: 'Channels', children: []} as unknown as Source,
  {type: 'native', key: 'section:blog', label: 'Blog'} as Source,
  {
    type: 'native',
    key: 'volume:images',
    label: 'Images',
    data: {'folder-id': 7, 'can-move-to': true},
  } as unknown as Source,
];

const route = {
  url: (params: Record<string, unknown>) =>
    `/admin/entries?source=${params.source}`,
} as unknown as ElementIndexRoute;

let scope: ReturnType<typeof effectScope> | undefined;

function run<T>(fn: () => T): T {
  scope = effectScope();

  return scope.run(fn)!;
}

afterEach(() => {
  scope?.stop();
  router.visit.mockClear();
  router.prefetch.mockClear();
});

describe('useElementSourceActions', () => {
  it('groups the sources under their headings', () => {
    const {actions} = run(() =>
      useElementSourceActions({sources: SOURCES, route, activeSource: '*'})
    );

    expect(
      actions.value.map((action) =>
        action.type === 'group'
          ? [action.heading, action.items.map((item) => item.label)]
          : (action as {label: string}).label
      )
    ).toEqual(['All entries', ['Channels', ['Blog', 'Images']]]);
  });

  it('switches source with a partial visit rather than a navigation', () => {
    const {actions} = run(() =>
      useElementSourceActions({sources: SOURCES, route, activeSource: '*'})
    );

    const blog = (
      actions.value[1] as {items: Array<{onClick?: (event: Event) => void}>}
    ).items[0]!;

    blog.onClick!(new MouseEvent('click', {cancelable: true}));

    // The source list and the publishable sections don't change when the
    // source does, so they're left out rather than the whole page re-fetched.
    const [url, options] = router.visit.mock.calls[0]!;

    expect(url).toBe('/admin/entries?source=section:blog');
    expect(options).toMatchObject({
      except: ['sources', 'publishableSections'],
      preserveState: true,
      preserveScroll: true,
    });
  });

  it('marks the clicked source before the visit settles', async () => {
    const active = ref<string | null>('*');
    const {actions} = run(() =>
      useElementSourceActions({sources: SOURCES, route, activeSource: active})
    );

    const blog = (
      actions.value[1] as {items: Array<{onClick?: (event: Event) => void}>}
    ).items[0]!;

    blog.onClick!(new MouseEvent('click', {cancelable: true}));
    await nextTick();

    // Activated straight away, so the selection doesn't lag the pointer.
    const grouped = actions.value[1] as {
      items: Array<{label: string; selected?: boolean}>;
    };

    expect(grouped.items[0]!.selected).toBe(true);
    expect((actions.value[0] as {selected?: boolean}).selected).toBe(false);
  });

  it('gives a source a real href and takes the click itself', () => {
    const {actions} = run(() =>
      useElementSourceActions({sources: SOURCES, route, activeSource: '*'})
    );

    const blog = (
      actions.value[1] as {
        items: Array<{href?: string; onClick?: (event: Event) => void}>;
      }
    ).items[0]!;

    // `craft-nav-item` only renders an interactive anchor when it has an href
    // — without one the sources are unfocusable text.
    expect(blog.href).toBe('/admin/entries?source=section:blog');

    const click = new MouseEvent('click', {cancelable: true});

    blog.onClick!(click);

    // …but following it would be a navigation, and this is a partial visit.
    expect(click.defaultPrevented).toBe(true);
  });

  it('prefetches on the way down', () => {
    const {actions} = run(() =>
      useElementSourceActions({sources: SOURCES, route, activeSource: '*'})
    );

    const blog = (
      actions.value[1] as {
        items: Array<{onMousedown?: () => void}>;
      }
    ).items[0]!;

    blog.onMousedown!();

    expect(router.prefetch).toHaveBeenCalledTimes(1);
  });

  it('hands off to a visitor instead of visiting, inside a modal', () => {
    const merge = vi.fn();
    const {actions} = run(() =>
      useElementSourceActions({
        sources: SOURCES,
        route,
        activeSource: '*',
        indexVisitor: {merge} as never,
      })
    );

    const blog = (
      actions.value[1] as {items: Array<{onClick?: (event: Event) => void}>}
    ).items[0]!;

    blog.onClick!(new MouseEvent('click', {cancelable: true}));

    // An Inertia visit inside the selector modal would navigate the page
    // behind it.
    expect(router.visit).not.toHaveBeenCalled();
    expect(merge).toHaveBeenCalledWith(
      {source: 'section:blog', viewMode: null},
      {resetPage: true}
    );
  });

  it('carries the drag-and-drop hooks a folder source needs', () => {
    const {actions} = run(() =>
      useElementSourceActions({sources: SOURCES, route, activeSource: '*'})
    );

    const items = (
      actions.value[1] as {
        items: Array<{
          label: string;
          attrs?: Record<string, string | undefined>;
        }>;
      }
    ).items;

    expect(items[1]!.attrs).toEqual({
      'data-folder-drop-target': '',
      'data-folder-id': '7',
      'data-can-move-to': '',
    });
    // A source that isn't a folder isn't a drop target.
    expect(items[0]!.attrs).toEqual({});
  });
});
