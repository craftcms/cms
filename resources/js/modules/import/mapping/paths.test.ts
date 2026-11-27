import {expect, it} from 'vite-plus/test';
import {dirtyState} from './paths';

it('ignores key order', () => {
  expect(dirtyState({a: 1, b: {c: 2, d: 3}})).toBe(
    dirtyState({b: {d: 3, c: 2}, a: 1})
  );
});

it('treats empty values, missing keys and empty branches alike', () => {
  expect(
    dirtyState({
      type: 'entry',
      source: '',
      transformer: null,
      settings: [],
      map: {title: '', body: {nested: null}},
    })
  ).toBe(dirtyState({type: 'entry', settings: {}, map: {}}));
});

it('sees a real change', () => {
  expect(dirtyState({map: {title: 'name'}})).not.toBe(
    dirtyState({map: {title: 'email'}})
  );
  expect(dirtyState({map: {title: 'name'}})).not.toBe(dirtyState({map: {}}));
  expect(dirtyState({batchSize: 0})).not.toBe(dirtyState({}));
});
