import {beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import {reactive, shallowRef} from 'vue';
import {useElementIndexFilters} from './useElementIndexFilters';
import type {ConditionConfig} from '@/modules/conditions/types';
import type {ViewState} from '@/modules/elements/types/view-state';
import type {IndexQueryParams} from './useElementIndexVisits';
import type {SourceItem} from '@/modules/elements/types/sources';

interface FilterData {
  source?: string;
  search?: string;
  status?: string | null;
  condition?: ConditionConfig;
}

// Capture the form's transform callback and submit arguments so the test can
// assert what would actually go over the wire.
const {submitSpy, transformState} = vi.hoisted(() => ({
  submitSpy: vi.fn(),
  transformState: {callback: (data: FilterData): FilterData => data},
}));

vi.mock('@inertiajs/vue3', () => ({
  useForm: (data: FilterData) => ({
    ...data,
    transform(callback: (data: FilterData) => FilterData) {
      transformState.callback = callback;
      return this;
    },
    submit: submitSpy,
  }),
}));

const stubRoute = {
  url: (query: IndexQueryParams = {}) =>
    '/cp/content/entries' +
    (Object.keys(query).length
      ? '?' +
        new URLSearchParams(
          Object.entries(query).map(([key, value]) => {
            const encoded = JSON.stringify(value);
            return [
              key,
              Object(value).constructor === String
                ? encoded.slice(1, -1)
                : encoded,
            ];
          })
        )
      : ''),
};

function makeViewState(): ViewState {
  return {
    inlineEditing: false,
    mode: 'table',
    nestedInputNamespace: null,
    showHeaderColumn: true,
    static: false,
  };
}

describe('useElementIndexFilters', () => {
  beforeEach(() => {
    submitSpy.mockClear();
  });

  it('applies condition rules to the currently selected source', () => {
    const props = reactive<{status: null; source: SourceItem}>({
      status: null,
      source: {type: 'native', key: '*', label: 'All entries'},
    });
    const condition: ConditionConfig = {
      class: 'EntryCondition',
      conditionRules: [{class: 'TitleConditionRule', value: 'foo'}],
    };
    const {submit} = useElementIndexFilters(
      props,
      shallowRef<ViewState>(makeViewState()),
      stubRoute,
      shallowRef(condition)
    );

    props.source = {type: 'native', key: 'section:news', label: 'News'};
    submit();

    expect(transformState.callback({search: '', status: ''})).toMatchObject({
      source: 'section:news',
      condition,
    });
  });

  it('submits the filter condition intact alongside the other filters', () => {
    const condition: ConditionConfig = {
      class: 'craft\\elements\\conditions\\entries\\EntryCondition',
      conditionRules: {
        operator: 'or',
        rules: [
          {class: 'TitleConditionRule', value: 'Alpha'},
          {
            operator: 'and',
            rules: [{class: 'TitleConditionRule', value: 'Beta'}],
          },
        ],
      },
    };

    const {submit} = useElementIndexFilters(
      {status: null},
      shallowRef<ViewState>(makeViewState()),
      stubRoute,
      shallowRef(condition)
    );

    submit();

    const data = transformState.callback({search: '', status: ''});
    expect(data.condition).toEqual(condition);
  });

  it('requests index-style query array serialization, which PHP can parse', () => {
    // Inertia's default 'brackets' format serializes an array of objects as
    // repeated `conditionRules[][key]=...` params; PHP's parse_str allocates a
    // NEW array element per `[]`, splitting one rule into per-field fragments
    // (and one applied filter into duplicate rules). 'indices' keeps each
    // rule's fields grouped under a stable numeric key.
    const {submit} = useElementIndexFilters(
      {status: null},
      shallowRef<ViewState>(makeViewState()),
      stubRoute,
      shallowRef({class: 'SomeCondition', conditionRules: []})
    );

    submit();

    expect(submitSpy).toHaveBeenCalledWith(
      expect.anything(),
      expect.objectContaining({queryStringArrayFormat: 'indices'})
    );
  });
});
