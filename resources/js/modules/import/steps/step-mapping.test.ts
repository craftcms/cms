import {expect, it, vi} from 'vite-plus/test';
import {actionClient} from '@craftcms/ui';
import type {ImportStep} from '@/modules/import/mapping/types';
import {takeNestedMappingContext} from '@/modules/import/mapping/nested-mapping';
import {
  openStepMapping,
  openStepNestedMapping,
  StepMappingUnavailableError,
  takeStepMappingContext,
} from './step-mapping';

const state = vi.hoisted(() => ({
  openSlideoutWith: vi.fn(),
}));

vi.mock('@/common/slideouts', () => ({
  openSlideoutWith: state.openSlideoutWith,
}));

const step: ImportStep = {
  uid: 'step-1',
  type: 'CraftCms\\Cms\\Entry\\Import\\EntryImporter',
  source: 'people.csv',
  transformer: null,
  batchSize: null,
  enabled: true,
  settings: {},
};

async function contextFor(data: Record<string, unknown>) {
  let contextId: string | undefined;
  state.openSlideoutWith.mockReset();
  state.openSlideoutWith.mockImplementation((_component, props) => {
    contextId = props.contextId;

    return {};
  });
  vi.spyOn(actionClient, 'post').mockResolvedValue({data} as never);

  await openStepMapping(
    {step, editable: true, opener: null, apply: () => {}},
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

it('throws with the server’s reason and attribute when the step can’t be mapped', async () => {
  vi.spyOn(actionClient, 'post').mockResolvedValue({
    data: {
      available: false,
      message: 'The data in “people.csv” couldn’t be read.',
      attribute: 'source',
    },
  } as never);

  const opening = openStepMapping(
    {step, editable: true, opener: null, apply: () => {}},
    'Edit mapping'
  );

  await expect(opening).rejects.toBeInstanceOf(StepMappingUnavailableError);
  await expect(opening).rejects.toMatchObject({
    message: 'The data in “people.csv” couldn’t be read.',
    attribute: 'source',
  });
});

it('opens a container’s own mapping straight away, found by its layout uid or by its field’s handle', async () => {
  const content = {
    handle: 'guideContent',
    prefixedHandle: 'layout-uid',
    prefixedHandleAsArray: ['layout-uid'],
    isContainer: true,
  };
  const opened = async (key: string) => {
    let contextId: string | undefined;
    state.openSlideoutWith.mockReset();
    state.openSlideoutWith.mockImplementation((_component, props) => {
      contextId = props.contextId;

      return {};
    });
    vi.spyOn(actionClient, 'post').mockImplementation(async (url: string) =>
      url.includes('nested')
        ? ({
            data: {title: 'Content', fieldName: 'Content', groups: []},
          } as never)
        : ({
            data: {
              available: true,
              destinationCols: [
                {
                  ...content,
                  handle: 'title',
                  prefixedHandleAsArray: ['title'],
                  isContainer: false,
                },
                {heading: 'Content', subfields: [content]},
              ],
              sourceDataCols: [],
              values: {map: {}, matchCriteria: {}, clearableItems: {}},
            },
          } as never)
    );

    await openStepNestedMapping({
      step,
      key,
      editable: true,
      opener: null,
      apply: () => {},
    });

    return takeNestedMappingContext(contextId!).col;
  };

  expect(await opened('layout-uid')).toMatchObject({handle: 'guideContent'});
  expect(await opened('guideContent')).toMatchObject({handle: 'guideContent'});
});
