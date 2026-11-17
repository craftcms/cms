import {afterEach, expect, it, vi} from 'vite-plus/test';

const legacyWindow = window as any;

const article = {id: 1, handle: 'article', name: 'Article', icon: null};
const review = {
  id: 2,
  handle: 'review',
  name: 'Review',
  icon: {name: 'star', family: 'regular'},
};
const page = {id: 3, handle: 'page', name: 'Page', icon: null};

/** Core's `creatableSections`. */
const creatableSections = [
  {
    uid: 'blog-uid',
    id: 10,
    name: 'Blog',
    handle: 'blog',
    type: 'channel',
    sites: [1, 2],
    entryTypes: [article, review],
    entryTypeIdsBySite: {1: [1, 2], 2: [2]},
    canSave: true,
  },
  {
    uid: 'pages-uid',
    id: 11,
    name: 'Pages',
    handle: 'pages',
    type: 'structure',
    sites: [3],
    entryTypes: [page],
    entryTypeIdsBySite: {3: [3]},
    canSave: false,
  },
  {
    uid: 'closed-uid',
    id: 12,
    name: 'Closed',
    handle: 'closed',
    type: 'channel',
    sites: [],
    entryTypes: [],
    entryTypeIdsBySite: {},
    canSave: true,
  },
];

const craft5Article = {handle: 'article', id: 1, name: 'Article'};
const craft5Review = {handle: 'review', id: 2, name: 'Review'};
const craft5Page = {handle: 'page', id: 3, name: 'Page'};

/** What Craft 5's `CpAsset::_publishableSections()` gives for site 2. */
const craft5Blog = {
  entryTypes: [craft5Review],
  entryTypesBySite: {1: [craft5Article, craft5Review], 2: [craft5Review]},
  handle: 'blog',
  id: 10,
  name: 'Blog',
  sites: [1, 2],
  type: 'channel',
  uid: 'blog-uid',
  canSave: true,
};
const craft5Pages = {
  // Not in site 2, so the first site's
  entryTypes: [craft5Page],
  entryTypesBySite: {3: [craft5Page]},
  handle: 'pages',
  id: 11,
  name: 'Pages',
  sites: [3],
  type: 'structure',
  uid: 'pages-uid',
  canSave: false,
};

async function install(craft: Record<string, unknown>) {
  legacyWindow.Craft = craft;
  vi.resetModules();
  await import('./publishable-sections.js');

  return legacyWindow.Craft;
}

afterEach(() => {
  delete legacyWindow.Craft;
});

it('builds Craft 5’s publishable sections from core’s creatable sections', async () => {
  const Craft = await install({creatableSections, siteId: 2});

  // Craft 5 left out sections without entry types in any site
  expect(Craft.publishableSections).toStrictEqual([craft5Blog, craft5Pages]);
  expect(Craft.publishableSections).toBe(Craft.publishableSections);
});

it('is undefined without creatable sections', async () => {
  const Craft = await install({siteId: 1});

  expect(Craft.publishableSections).toBeUndefined();
});

it('follows the sections and the site of the latest Inertia page', async () => {
  const Craft = await install({creatableSections, siteId: 2});
  Craft.publishableSections;

  const nextPage = {
    props: {
      craft: {
        site: {id: 1},
        general: {creatableSections: creatableSections.slice(0, 1)},
      },
    },
  };
  document.dispatchEvent(
    new CustomEvent('inertia:navigate', {detail: {page: nextPage}})
  );

  expect(Craft.publishableSections).toStrictEqual([
    {...craft5Blog, entryTypes: [craft5Article, craft5Review]},
  ]);
});

it('keeps a value a script assigns', async () => {
  const Craft = await install({creatableSections, siteId: 2});
  const assigned = [{handle: 'custom'}];

  Craft.publishableSections = assigned;

  expect(Craft.publishableSections).toBe(assigned);
});
