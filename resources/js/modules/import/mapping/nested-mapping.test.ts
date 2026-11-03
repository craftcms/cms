import {expect, it, vi} from 'vite-plus/test';
import {actionClient} from '@craftcms/ui';
import {fetchNestedMapping} from './nested-mapping';
import type {ImportStep, MappingColumn, MappingValues} from './types';

const step: ImportStep = {
  uid: 'step-1',
  type: 'CraftCms\\Cms\\Entry\\Import\\EntryImporter',
  source: 'people.csv',
  transformer: null,
  batchSize: null,
  enabled: true,
  settings: {site: 'default'},
};

const matrix = {
  handle: 'blocks',
  label: 'Blocks',
  prefixedHandle: 'blocks',
  prefixedHandleAsArray: ['blocks'],
  fieldUid: 'field-uid',
  isContainer: true,
} as MappingColumn;

function values(): MappingValues {
  return {
    map: {title: 'name'},
    matchCriteria: {},
    clearableItems: {},
    keepMissingNestedElements: {},
    fieldSettings: {},
    templates: {},
  };
}

it('posts the step with the trees as they stand, so its guesses skip what’s chosen', async () => {
  const post = vi
    .spyOn(actionClient, 'post')
    .mockResolvedValue({data: {groups: []}} as never);

  await fetchNestedMapping(step, values(), matrix);

  expect(post.mock.calls[0]![1]).toEqual({
    step: {...step, settings: {site: 'default', ...values()}},
    fieldUid: 'field-uid',
    fieldHandle: 'blocks',
    fieldIsProperty: 0,
  });
});

it('returns the container’s groups and its guesses, with PHP lists as keyed objects', async () => {
  const groups = [{providerName: 'Text', destinationCols: []}];
  vi.spyOn(actionClient, 'post').mockResolvedValue({
    data: {groups, suggestions: {blocks: [{fields: {body: 'body'}}]}},
  } as never);

  expect(await fetchNestedMapping(step, values(), matrix)).toEqual({
    groups,
    suggestions: {blocks: {0: {fields: {body: 'body'}}}},
  });
});
