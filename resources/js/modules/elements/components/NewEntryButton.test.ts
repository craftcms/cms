import {createApp, nextTick} from 'vue';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import type {Source, SourceItem} from '@/modules/elements/types/sources';
import type {ActionItem, ActionItemGroup, ActionItemLink} from '@/common/types';

// Declared here rather than imported: a `.ts` file sees a `.vue` module through
// its generic shim, which carries only the default export.
type EntryType = {
  handle: string;
  id: number;
  name: string;
  icon: {name: string; family?: string} | null;
};

// The shape the index actually receives: a nested resource collection comes
// through wrapped in `data`.
type PublishableSection = {
  entryTypes: {data: Array<EntryType>};
  handle: string;
  id: number;
  name: string;
  sites: Array<number>;
  type: string;
  uid: string;
  canSave: boolean;
};

vi.mock('@/common/composables/useCraftData', () => ({
  default: () => ({site: {value: {id: 1}}}),
}));

// The menu is what's under test, not how it opens: record what it's handed and
// render its invoker so the button inside can be inspected.
const menu = vi.hoisted(() => ({actions: null as null | Array<ActionItem>}));

vi.mock('@/common/components/ActionMenu.vue', async () => {
  const {h} = await import('vue');

  return {
    default: {
      name: 'ActionMenu',
      props: ['actions', 'label'],
      setup(
        props: {actions: Array<ActionItem>},
        {slots}: {slots: Record<string, () => unknown>}
      ) {
        return () => {
          menu.actions = props.actions;
          return h('div', {class: 'menu'}, slots.invoker?.() as never);
        };
      },
    },
  };
});

const NewEntryButton = (await import('./NewEntryButton.vue')).default;

let teardown: (() => void) | undefined;

afterEach(() => {
  teardown?.();
  teardown = undefined;
  menu.actions = null;
  vi.restoreAllMocks();
});

const entryType = (
  handle: string,
  id: number,
  icon: EntryType['icon'] = null
): EntryType => ({
  handle,
  id,
  name: handle[0]!.toUpperCase() + handle.slice(1),
  icon,
});

function section(
  handle: string,
  entryTypes: Array<EntryType>,
  sites = [1]
): PublishableSection {
  return {
    handle,
    id: entryTypes[0]?.id ?? 0,
    name: handle[0]!.toUpperCase() + handle.slice(1),
    uid: handle,
    type: 'channel',
    sites,
    canSave: true,
    entryTypes: {data: entryTypes},
  };
}

const sectionSource = (handle: string, ids?: number[]): SourceItem => ({
  type: 'native',
  key: `section:${handle}`,
  label: handle,
  data: {handle, ...(ids ? {'entry-type-ids': ids} : {})},
});

async function mount(props: {
  sources: Array<Source>;
  source?: SourceItem;
  publishableSections: Array<PublishableSection>;
}) {
  const container = document.createElement('div');
  document.body.append(container);
  const app = createApp(NewEntryButton, {
    elementDisplayName: 'entry',
    ...props,
  });
  app.mount(container);
  teardown = () => {
    app.unmount();
    container.remove();
  };
  await nextTick();

  return container;
}

it('creates the entry type directly when it is the only one on screen', async () => {
  const blog = section('blog', [entryType('article', 1)]);
  const container = await mount({
    sources: [sectionSource('blog')],
    source: sectionSource('blog'),
    publishableSections: [blog],
  });
  const open = vi.spyOn(window, 'open').mockImplementation(() => null);

  const button = container.querySelector('craft-button')!;
  // No menu to choose from — there's nothing to choose.
  expect(container.querySelector('.menu')).toBeNull();

  button.dispatchEvent(new MouseEvent('click', {metaKey: true}));

  expect(open).toHaveBeenCalledOnce();
  const href = open.mock.calls[0]![0] as string;
  expect(href).toContain('/entries/blog/new');
  expect(href).toContain('type=article');
});

it('offers every entry type the section shows when there are several', async () => {
  const blog = section('blog', [
    entryType('article', 1),
    entryType('review', 2),
  ]);
  await mount({
    sources: [sectionSource('blog')],
    source: sectionSource('blog'),
    publishableSections: [blog],
  });

  // One section, so no headings — just its types.
  const items = menu.actions as Array<ActionItemLink>;
  expect(items.map((item) => item.label)).toEqual(['Article', 'Review']);
  expect(items[1]!.href).toContain('type=review');
});

it('groups the types by section when the view spans several', async () => {
  const blog = section('blog', [entryType('article', 1)]);
  const news = section('news', [entryType('story', 2), entryType('brief', 3)]);
  await mount({
    // The "all entries" view: no single section of its own.
    sources: [
      {type: 'native', key: '*', label: 'All entries', data: {}},
      sectionSource('blog'),
      sectionSource('news'),
    ],
    source: {type: 'native', key: '*', label: 'All entries', data: {}},
    publishableSections: [blog, news],
  });

  const groups = menu.actions as Array<ActionItemGroup>;
  expect(groups.map((group) => group.heading)).toEqual(['Blog', 'News']);
  expect(groups[1]!.items.map((item) => item.label)).toEqual([
    'Story',
    'Brief',
  ]);
});

it('keeps to the sections this index actually shows', async () => {
  const blog = section('blog', [entryType('article', 1)]);
  const hidden = section('hidden', [entryType('secret', 9)]);
  const container = await mount({
    sources: [sectionSource('blog')],
    publishableSections: [blog, hidden],
  });

  // Only one type is on screen, so it's a plain button rather than a menu.
  expect(container.querySelector('.menu')).toBeNull();
  expect(container.querySelector('craft-button')).not.toBeNull();
});

it('leaves out sections the current site does not have', async () => {
  const blog = section('blog', [entryType('article', 1)]);
  const elsewhere = section('elsewhere', [entryType('note', 5)], [2]);
  const container = await mount({
    sources: [sectionSource('blog'), sectionSource('elsewhere')],
    publishableSections: [blog, elsewhere],
  });

  expect(container.querySelector('.menu')).toBeNull();
});

it('creates the pinned type for a custom source', async () => {
  const blog = section('blog', [
    entryType('article', 1),
    entryType('review', 2),
  ]);
  const pinned: SourceItem = {
    type: 'custom',
    key: 'custom:reviews',
    label: 'Reviews',
    data: {'entry-type': 'review'},
  };
  const container = await mount({
    sources: [sectionSource('blog'), pinned],
    source: pinned,
    publishableSections: [blog],
  });
  const open = vi.spyOn(window, 'open').mockImplementation(() => null);

  container
    .querySelector('craft-button')!
    .dispatchEvent(new MouseEvent('click', {ctrlKey: true}));

  expect(open.mock.calls[0]![0]).toContain('type=review');
});

it('shows nothing when there is nothing to create', async () => {
  const container = await mount({
    sources: [sectionSource('blog')],
    publishableSections: [],
  });

  expect(container.innerHTML.replace(/<!--.*?-->/g, '')).toBe('');
});

it('always uses the primary variant', async () => {
  const one = await mount({
    sources: [sectionSource('blog')],
    publishableSections: [section('blog', [entryType('article', 1)])],
  });
  expect(one.querySelector('craft-button')!.getAttribute('variant')).toBe(
    'primary'
  );
  teardown?.();

  const many = await mount({
    sources: [sectionSource('blog')],
    publishableSections: [
      section('blog', [entryType('article', 1), entryType('review', 2)]),
    ],
  });
  expect(many.querySelector('craft-button')!.getAttribute('variant')).toBe(
    'primary'
  );
});

it('copes with a heading that arrives without children', async () => {
  // The index payload is a flat list: headings sit between the sources they
  // head rather than containing them, so there's no `children` to read.
  const container = await mount({
    sources: [
      {type: 'heading', heading: 'Channels'} as unknown as Source,
      sectionSource('blog'),
    ],
    publishableSections: [section('blog', [entryType('article', 1)])],
  });

  expect(container.querySelector('craft-button')).not.toBeNull();
});

it('gives each menu entry its entry type icon by name', async () => {
  // Icons arrive as `Icons::resolveIconData()` — an object, not a name.
  await mount({
    sources: [sectionSource('blog')],
    publishableSections: [
      section('blog', [
        entryType('article', 1, {name: 'newspaper', family: 'light'}),
        entryType('review', 2),
      ]),
    ],
  });

  const items = menu.actions as Array<ActionItemLink>;
  expect(items[0]!.icon).toBe('newspaper');
  expect('icon' in items[1]!).toBe(false);
});
