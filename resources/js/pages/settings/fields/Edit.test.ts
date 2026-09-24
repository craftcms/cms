import {createApp, defineComponent, h, nextTick} from 'vue';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import type {FormChange, FormPayload} from '@/modules/forms/types';
import Edit from './Edit.vue';

const state = vi.hoisted<{
  layout: ReturnType<typeof vi.fn>;
  save: ReturnType<typeof vi.fn>;
  setValue: ReturnType<typeof vi.fn>;
  change?: (change: FormChange, values: FormPayload['values']) => void;
}>(() => ({
  layout: vi.fn(),
  save: vi.fn(),
  setValue: vi.fn(),
  change: undefined,
}));

vi.mock('@/common/composables/useAppLayout', () => ({
  useAppLayout: state.layout,
}));

vi.mock('@/common/components/DynamicHtmlRenderer.vue', () => ({
  default: defineComponent({
    props: {html: String},
    setup: (props) => () => h('div', {class: 'details-html'}, props.html),
  }),
}));

vi.mock('@/common/components/LayoutSlot.vue', () => ({
  default: defineComponent({
    setup:
      (_, {slots}) =>
      () =>
        h('div', slots.default?.()),
  }),
}));

vi.mock('@/pages/Form.vue', () => ({
  default: defineComponent({
    emits: ['change'],
    setup: (_, {emit, expose}) => {
      state.change = (change, values) => emit('change', change, values);
      expose({save: state.save, setValue: state.setValue});

      return () => h('div');
    },
  }),
}));

const form: FormPayload = {
  scope: [],
  refreshable: true,
  nodes: [],
  values: {type: 'OldField', translationMethod: 'custom'},
  errors: [],
  globalErrors: [],
};

let app: ReturnType<typeof createApp>;
let container: HTMLElement;

beforeEach(() => {
  state.layout.mockClear();
  state.save.mockReset();
  state.setValue.mockReset();
  state.change = undefined;
  container = document.createElement('div');
  document.body.append(container);
});

afterEach(() => {
  app.unmount();
  container.remove();
});

function mount(details: string | null = null): void {
  app = createApp(Edit, {
    form,
    submit: {method: 'post', url: '/actions/fields/store'},
    refreshUrl: '/actions/fields/render-form',
    supportedTranslationMethods: {
      OldField: ['none', 'custom'],
      NewField: ['none'],
    },
    details,
  });
  app.mount(container);
}

it('selects a supported translation method when the field type changes', async () => {
  mount();
  await nextTick();

  state.change!(
    {kind: 'discrete', path: ['type']},
    {type: 'NewField', translationMethod: 'custom'}
  );

  expect(state.setValue).toHaveBeenCalledWith(
    ['translationMethod'],
    'none',
    'discrete'
  );
});

it('saves and starts another field from the form action', async () => {
  mount();
  await nextTick();

  state.layout.mock.calls[0]![0].formActions[0].onClick();

  expect(state.save).toHaveBeenCalledWith({
    data: {addAnother: 1},
    preserveState: false,
  });
});

it('shows the field’s details in an Info tab', async () => {
  mount('<dl>ID 1</dl>');
  await nextTick();

  expect(container.querySelector('craft-tab')?.id).toBe('details-tab-info');
  expect(container.querySelector('.details-html')?.textContent).toBe(
    '<dl>ID 1</dl>'
  );
});

it('omits the details column for a new field', async () => {
  mount();
  await nextTick();

  expect(container.querySelector('craft-tabs')).toBeNull();
});
