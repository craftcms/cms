import {expect, it, vi} from 'vite-plus/test';
import {createModalIndexVisitor} from './modal-index-visitor';

it('keeps its query locally rather than in the page URL', () => {
  const load = vi.fn().mockResolvedValue(undefined);
  const visitor = createModalIndexVisitor(load, {source: 'admins'});

  expect(visitor.currentQuery()).toEqual({source: 'admins'});
  // The page's URL is irrelevant to a modal, and must stay untouched.
  expect(window.location.search).toBe('');
});

it('loads a fresh payload instead of navigating', async () => {
  const load = vi.fn().mockResolvedValue(undefined);
  const visitor = createModalIndexVisitor(load);

  visitor.merge({search: 'cat'});

  expect(load).toHaveBeenCalledWith({search: 'cat'});
});

it('merges into the existing query, replacing named keys', () => {
  const load = vi.fn().mockResolvedValue(undefined);
  const visitor = createModalIndexVisitor(load, {source: 'admins', page: '2'});

  visitor.merge({search: 'cat'});

  expect(visitor.currentQuery()).toEqual({
    source: 'admins',
    page: '2',
    search: 'cat',
  });
});

it('drops a param set to null', () => {
  const load = vi.fn().mockResolvedValue(undefined);
  const visitor = createModalIndexVisitor(load, {search: 'cat'});

  visitor.merge({search: null});

  expect(visitor.currentQuery()).toEqual({});
});

it('resets the page when asked, so a filter change starts at page 1', () => {
  const load = vi.fn().mockResolvedValue(undefined);
  const visitor = createModalIndexVisitor(load, {page: '3', source: 'admins'});

  visitor.merge({search: 'cat'}, {resetPage: true});

  expect(visitor.currentQuery()).toEqual({source: 'admins', search: 'cat'});
});

it('strips bracketed forms of a replaced key', () => {
  const load = vi.fn().mockResolvedValue(undefined);
  const visitor = createModalIndexVisitor(load, {
    'sort[0][field]': 'title',
    source: 'admins',
  });

  visitor.merge({sort: 'dateCreated'});

  expect(visitor.currentQuery()).toEqual({
    source: 'admins',
    sort: 'dateCreated',
  });
});

it('keeps structured values intact rather than stringifying them', () => {
  // This one POSTs JSON, so a `sort` object should survive. Coercing every
  // value with `String()` sent the server a literal "[object Object]".
  const load = vi.fn().mockResolvedValue(undefined);
  const visitor = createModalIndexVisitor(load);
  const sort = {0: {field: 'title', direction: 'asc'}};

  visitor.visit({source: 'admins', sort});

  expect(load).toHaveBeenCalledWith({source: 'admins', sort});
  expect(visitor.currentQuery().sort).toEqual(sort);
});

it('carries the chosen source through a later sort or page change', () => {
  // The composables build their next query from `currentQuery()`, so whatever
  // navigation put there has to still be there. Reading the host page's URL
  // instead is what used to drop the source a click had just applied.
  const load = vi.fn().mockResolvedValue(undefined);
  const visitor = createModalIndexVisitor(load);

  visitor.merge({source: 'section:news'}, {resetPage: true});

  const next = {...visitor.currentQuery(['sort']), sort: {0: {field: 'title'}}};
  visitor.visit(next);

  expect(load).toHaveBeenLastCalledWith(
    expect.objectContaining({source: 'section:news'})
  );
});
