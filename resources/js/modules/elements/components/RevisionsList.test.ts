import {createApp, h, nextTick} from 'vue';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import type {ElementEditPayload} from '@/modules/elements/composables/useElementEditor';
import RevisionsList from './RevisionsList.vue';

let app: ReturnType<typeof createApp> | undefined;
let container: HTMLElement | undefined;

beforeEach(() => {
  // `craft-icon`s fetch their SVGs; left in flight they're aborted at
  // teardown and reported as unhandled errors.
  vi.stubGlobal(
    'fetch',
    vi.fn(async () => new Response('<svg></svg>'))
  );
});

afterEach(() => {
  app?.unmount();
  container?.remove();
  vi.unstubAllGlobals();
});

async function mount(items: unknown[]): Promise<HTMLElement> {
  container = document.createElement('div');
  document.body.append(container);
  app = createApp({
    render: () =>
      h(RevisionsList, {
        payload: {contextMenu: {items}} as unknown as ElementEditPayload,
      }),
  });
  app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
  app.mount(container);
  await nextTick();

  return container;
}

const link = (label: string, href: string, selected = false) => ({
  type: 'link',
  label,
  href,
  selected,
});

it('links to every revision beside the revisions’ heading, when the server sent only some', async () => {
  const root = await mount([
    link('Current', '/entries/1', true),
    {type: 'heading', label: 'Drafts'},
    link('Working draft', '/entries/1?draftId=4'),
    {type: 'heading', label: 'Recent Revisions'},
    link('Revision 3', '/entries/1?revisionId=9'),
    {type: 'hr'},
    link('View all revisions', '/entries/1/revisions'),
  ]);

  const headings = [...root.querySelectorAll('.revision-group__heading')];
  const viewAll = (heading: Element) =>
    [...heading.querySelectorAll('a')].find(
      (anchor) => anchor.textContent?.trim() === 'View all'
    );

  expect(
    headings.map((heading) => heading.querySelector('h3')?.textContent?.trim())
  ).toEqual(['Drafts', 'Recent Revisions']);
  expect(viewAll(headings[0]!)).toBeUndefined();
  expect(viewAll(headings[1]!)?.getAttribute('href')).toBe(
    '/entries/1/revisions'
  );
  // Not listed as a revision of its own.
  expect(root.textContent).not.toContain('View all revisions');
});

it('has no link to every revision when the server sent them all', async () => {
  const root = await mount([
    link('Current', '/entries/1', true),
    {type: 'heading', label: 'Recent Revisions'},
    link('Revision 3', '/entries/1?revisionId=9'),
  ]);

  expect(root.textContent).not.toContain('View all');
});
