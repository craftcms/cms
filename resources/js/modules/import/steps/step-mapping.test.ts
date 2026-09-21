import {expect, it, vi} from 'vite-plus/test';
import {actionClient} from '@craftcms/ui';
import type {StepPayload} from '@/modules/import/mapping/types';
import {openStepMapping, takeStepMappingContext} from './step-mapping';

const state = vi.hoisted(() => ({
  openSlideoutWith: vi.fn(),
}));

vi.mock('@/common/slideouts', () => ({
  openSlideoutWith: state.openSlideoutWith,
}));

const step: StepPayload = {
  uid: 'step-1',
  type: 'CraftCms\\Cms\\Entry\\Import\\EntryImporter',
  file: 'people.csv',
  transformer: null,
  batchSize: null,
  settings: {},
};

const urls = {
  settingsUrl: '/actions/import/step-settings',
  mappingUrl: '/actions/import/step-mapping',
  nestedColsUrl: '/actions/import/nested-mapping-cols',
};

/** The context the panel would be handed, for a given server response. */
async function contextFor(data: Record<string, unknown>) {
  let contextId: string | undefined;
  state.openSlideoutWith.mockReset();
  state.openSlideoutWith.mockImplementation((_component, props) => {
    contextId = props.contextId;

    return {};
  });
  vi.spyOn(actionClient, 'post').mockResolvedValue({data} as never);

  await openStepMapping(
    {step, urls, editable: true, opener: null, apply: () => {}},
    'Edit mapping'
  );

  return takeStepMappingContext(contextId!);
}

it('flags the leaves it filled from the server’s suggestions', async () => {
  // PHP has one array type, so an untouched map and an empty tree both arrive as `[]`.
  const context = await contextFor({
    available: true,
    destinationCols: [],
    sourceDataCols: [{label: 'Name', value: 'name'}],
    values: {
      map: [],
      matchCriteria: [],
      clearableItems: [],
      keepMissingNestedElements: [],
    },
    suggestions: {title: 'name'},
  });

  expect(context.values.map).toEqual({title: 'name'});
  expect(context.suggestedMap).toEqual({title: true});
});

it('flags nested leaves inside a container’s branch', async () => {
  const context = await contextFor({
    available: true,
    destinationCols: [],
    sourceDataCols: [{label: 'Name', value: 'name'}],
    values: {
      map: [],
      matchCriteria: [],
      clearableItems: [],
      keepMissingNestedElements: [],
    },
    suggestions: {outerMatrix: {outerEt: {title: 'name'}}},
  });

  expect(context.values.map).toEqual({
    outerMatrix: {outerEt: {title: 'name'}},
  });
  expect(context.suggestedMap).toEqual({
    outerMatrix: {outerEt: {title: true}},
  });
});

it('leaves an already-mapped leaf unflagged', async () => {
  const context = await contextFor({
    available: true,
    destinationCols: [],
    sourceDataCols: [{label: 'Name', value: 'name'}],
    values: {
      map: {title: 'email'},
      matchCriteria: [],
      clearableItems: [],
      keepMissingNestedElements: [],
    },
    suggestions: {title: 'name'},
  });

  expect(context.values.map).toEqual({title: 'email'});
  expect(context.suggestedMap).toEqual({});
});
