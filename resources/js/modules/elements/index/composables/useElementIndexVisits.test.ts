import {expect, it, vi} from 'vite-plus/test';
import {createDetachedIndexVisitor} from './useElementIndexVisits';

it('keeps its query locally rather than in the page URL', () => {
  const load = vi.fn().mockResolvedValue(true);
  const visitor = createDetachedIndexVisitor((query) => load(query), {
    source: 'admins',
  });

  expect(visitor.currentQuery()).toEqual({source: 'admins'});
  expect(window.location.search).toBe('');
});

it('loads a fresh payload instead of navigating', async () => {
  const load = vi.fn().mockResolvedValue(true);
  const visitor = createDetachedIndexVisitor((query) => load(query));

  visitor.merge({search: 'cat'});

  expect(load).toHaveBeenCalledWith({search: 'cat'});
});

it('merges into the existing query, replacing named keys', () => {
  const load = vi.fn().mockResolvedValue(true);
  const visitor = createDetachedIndexVisitor((query) => load(query), {
    source: 'admins',
    page: '2',
  });

  visitor.merge({search: 'cat'});

  expect(visitor.currentQuery()).toEqual({
    source: 'admins',
    page: '2',
    search: 'cat',
  });
});

it('drops a param set to null', () => {
  const load = vi.fn().mockResolvedValue(true);
  const visitor = createDetachedIndexVisitor((query) => load(query), {
    search: 'cat',
  });

  visitor.merge({search: null});

  expect(visitor.currentQuery()).toEqual({});
});

it('resets the page when asked, so a filter change starts at page 1', () => {
  const load = vi.fn().mockResolvedValue(true);
  const visitor = createDetachedIndexVisitor((query) => load(query), {
    page: '3',
    source: 'admins',
  });

  visitor.merge({search: 'cat'}, {resetPage: true});

  expect(visitor.currentQuery()).toEqual({source: 'admins', search: 'cat'});
});

it('strips bracketed forms of a replaced key', () => {
  const load = vi.fn().mockResolvedValue(true);
  const visitor = createDetachedIndexVisitor((query) => load(query), {
    'sort[0][field]': 'title',
    source: 'admins',
  });

  visitor.merge({sort: 'dateCreated'});

  expect(visitor.currentQuery()).toEqual({
    source: 'admins',
    sort: 'dateCreated',
  });
});

it('keeps structured values intact rather than stringifying them', async () => {
  const load = vi.fn().mockResolvedValue(true);
  const visitor = createDetachedIndexVisitor((query) => load(query));
  const sort = {0: {field: 'title', direction: 'asc'}};

  await visitor.visit({source: 'admins', sort});

  expect(load).toHaveBeenCalledWith({source: 'admins', sort});
  expect(visitor.currentQuery().sort).toEqual(sort);
});

it('carries the chosen source through a later sort or page change', async () => {
  const load = vi.fn().mockResolvedValue(true);
  const visitor = createDetachedIndexVisitor((query) => load(query));

  visitor.merge({source: 'section:news'}, {resetPage: true});

  const next = {...visitor.currentQuery(['sort']), sort: {0: {field: 'title'}}};
  await visitor.visit(next);

  expect(load).toHaveBeenLastCalledWith(
    expect.objectContaining({source: 'section:news'})
  );
});

it('keeps the last applied query when a newer request fails', async () => {
  let finishFirst!: (applied: boolean) => void;
  let failSecond!: (error: Error) => void;
  const first = new Promise<boolean>((resolve) => {
    finishFirst = resolve;
  });
  const second = new Promise<boolean>((_, reject) => {
    failSecond = reject;
  });
  const error = new Error('Index request failed');
  const load = vi.fn().mockReturnValueOnce(first).mockReturnValueOnce(second);
  const visitor = createDetachedIndexVisitor((query) => load(query));
  const initial = visitor.visit({search: 'foo'});
  const next = visitor.visit({search: 'foobar'});
  finishFirst(true);
  await initial;
  failSecond(error);
  await expect(next).rejects.toBe(error);

  expect(visitor.currentQuery()).toEqual({search: 'foo'});
});
