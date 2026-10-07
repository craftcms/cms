import type {UiPayload} from '@/modules/ui/types';
import {createApp, defineComponent, h, nextTick} from 'vue';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import Edit from './Edit.vue';

const state = vi.hoisted(() => ({
  formProps: vi.fn(),
  save: vi.fn(),
}));

vi.mock('@/common/components/DynamicHtmlRenderer.vue', () => ({
  default: defineComponent({render: () => h('div')}),
}));

vi.mock('@/common/components/LayoutSlot.vue', () => ({
  default: defineComponent({render: () => h('div')}),
}));

vi.mock('@/pages/Form.vue', () => ({
  default: defineComponent({
    props: ['formActions'],
    setup: (props, {expose}) => {
      state.formProps({
        formActions: props.formActions,
      });
      expose({save: state.save});

      return () => h('div');
    },
  }),
}));

const form: UiPayload = {
  scope: [],
  refreshable: true,
  nodes: [],
  values: {},
  errors: [],
  globalErrors: [],
};

let app: ReturnType<typeof createApp>;
let container: HTMLElement;

beforeEach(() => {
  state.formProps.mockClear();
  state.save.mockReset();
  container = document.createElement('div');
  document.body.append(container);
});

afterEach(() => {
  app.unmount();
  container.remove();
});

it('saves the current values as a new entry type', async () => {
  app = createApp(Edit, {
    form,
    submit: {method: 'post', url: '/actions/entry-types/store'},
    refreshUrl: '/actions/entry-types/render-form',
    brandNew: false,
    lowerTypeName: 'entry',
    metadataHtml: null,
  });
  app.mount(container);
  await nextTick();

  state.formProps.mock.calls[0]![0].formActions[0].onClick();

  expect(state.save).toHaveBeenCalledWith({
    data: {saveAsNew: true},
    preserveState: false,
  });
});
