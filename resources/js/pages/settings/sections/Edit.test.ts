import type {UiChange, UiPayload} from '@/modules/ui/types';
import {createApp, nextTick} from 'vue';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import Edit from './Edit.vue';

const state = vi.hoisted<{
  change?: (change: UiChange, values: UiPayload['values']) => void;
  setValue: ReturnType<typeof vi.fn>;
}>(() => ({
  change: undefined,
  setValue: vi.fn(),
}));

vi.mock('@/pages/Ui.vue', async () => {
  const {defineComponent, h} = await import('vue');

  return {
    default: defineComponent({
      props: ['form'],
      emits: ['change'],
      setup: (_props, {emit, expose}) => {
        state.change = (change, values) => emit('change', change, values);
        expose({setValue: state.setValue});

        return () => h('div');
      },
    }),
  };
});

const values = {
  sectionId: null,
  name: '',
  type: 'channel',
  entryTypes: [{id: 2}, {id: 1}],
  sites: {
    default: {
      enabled: true,
      siteId: 1,
      name: 'Default',
      singleHomepage: false,
      singleUri: '',
      uriFormat: '',
      routeType: 'template',
      route: '',
      enabledByDefault: true,
    },
  },
};
const form: UiPayload = {
  scope: [],
  refreshable: true,
  nodes: [],
  values,
  errors: [],
  globalErrors: [],
};

let app: ReturnType<typeof createApp>;
let container: HTMLElement;

beforeEach(() => {
  state.change = undefined;
  state.setValue.mockReset();
  container = document.createElement('div');
  document.body.append(container);
});

afterEach(() => {
  app.unmount();
  container.remove();
});

it('generates new section site settings from the name', async () => {
  await mount(true);

  state.change!({kind: 'typing', path: ['name']}, {...values, name: 'News'});

  expect(state.setValue).toHaveBeenCalledWith(
    ['sites'],
    {
      default: {
        ...values.sites.default,
        singleUri: 'news',
        uriFormat: 'news/{slug}',
        routeType: 'template',
        route: 'news/_entry.twig',
      },
    },
    'typing'
  );
});

it('does not generate site settings for an existing section', async () => {
  await mount(false);

  state.change!({kind: 'typing', path: ['name']}, {...values, name: 'News'});

  expect(state.setValue).not.toHaveBeenCalled();
});

async function mount(brandNew: boolean): Promise<void> {
  app = createApp(Edit, {
    form,
    submit: {method: 'post', url: '/sections'},
    refreshUrl: '/sections/form',
    brandNew,
  });
  app.mount(container);
  await nextTick();
}
