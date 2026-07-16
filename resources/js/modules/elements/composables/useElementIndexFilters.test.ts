import {beforeEach, describe, expect, it, vi} from 'vitest';
import {ref} from 'vue';
import {useElementIndexFilters} from './useElementIndexFilters';
import type {ConditionConfig} from './useConditionBuilder';
import type {ViewState} from '@/modules/elements/types/view-state';

// Capture the form's transform callback and submit arguments so the test can
// assert what would actually go over the wire.
const {submitSpy, transformState} = vi.hoisted(() => ({
  submitSpy: vi.fn(),
  transformState: {callback: (data: object): object => data},
}));

vi.mock('@inertiajs/vue3', () => ({
  useForm: (data: object) => ({
    ...data,
    transform(callback: (data: object) => object) {
      transformState.callback = callback;
      return this;
    },
    submit: submitSpy,
  }),
}));

const stubRoute = {
  url: (query: Record<string, unknown> = {}) =>
    '/cp/content/entries' +
    (Object.keys(query).length
      ? '?' + new URLSearchParams(query as Record<string, string>)
      : ''),
};

function makeViewState(): ViewState {
  return {
    inlineEditing: false,
    mode: 'table',
    nestedInputNamespace: null,
    showHeaderColumn: true,
    static: false,
  } as ViewState;
}

describe('useElementIndexFilters', () => {
  beforeEach(() => {
    submitSpy.mockClear();
  });

  it('submits the filter condition intact alongside the other filters', () => {
    const condition: ConditionConfig = {
      class: 'craft\\elements\\conditions\\entries\\EntryCondition',
      conditionRules: [
        {
          class: 'craft\\elements\\conditions\\TitleConditionRule',
          uid: 'uid-1',
          value: 'foo',
        },
      ],
    };

    const {submit} = useElementIndexFilters(
      {status: null},
      ref(makeViewState()),
      stubRoute,
      ref(condition)
    );

    submit();

    const data = transformState.callback({search: '', status: ''}) as Record<
      string,
      unknown
    >;
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
      ref(makeViewState()),
      stubRoute,
      ref({class: 'SomeCondition', conditionRules: []})
    );

    submit();

    expect(submitSpy).toHaveBeenCalledWith(
      expect.anything(),
      expect.objectContaining({queryStringArrayFormat: 'indices'})
    );
  });
});
