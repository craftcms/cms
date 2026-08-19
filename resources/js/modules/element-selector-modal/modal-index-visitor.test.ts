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
