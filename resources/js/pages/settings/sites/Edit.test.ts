import {createApp, defineComponent, h, nextTick} from 'vue';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import type {Site} from '@/common/types';
import type {FormChange, FormPayload} from '@/modules/forms/types';
import Edit from './Edit.vue';

const state = vi.hoisted<{
  change?: (change: FormChange, values: FormPayload['values']) => void;
  setValue: ReturnType<typeof vi.fn>;
}>(() => ({
  change: undefined,
  setValue: vi.fn(),
}));

vi.mock('@/pages/Form.vue', async () => {
  const {defineComponent, h} = await import('vue');

  return {
    default: defineComponent({
      props: ['form', 'submit', 'refreshUrl'],
      emits: ['change'],
      setup: (_, {emit, expose}) => {
        state.change = (change, values) => emit('change', change, values);
        expose({setValue: state.setValue});

        return () => h('div');
      },
    }),
  };
});

vi.mock('@/common/components/LayoutSlot.vue', () => ({
  default: defineComponent({
    setup:
      (_, {slots}) =>
      () =>
        h('div', slots.default?.()),
  }),
}));

vi.mock('@/common/components/Badge.vue', () => ({
  default: defineComponent({
    setup:
      (_, {slots}) =>
      () =>
        h('div', slots.default?.()),
  }),
}));

const values = {
  siteId: null,
  name: '',
  baseUrl: '',
  hasUrls: true,
};
const form: FormPayload = {
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

it('generates the base URL until it is changed manually', async () => {
  await mount(null);

  state.change!({kind: 'typing', path: ['name']}, {...values, name: 'My Site'});

  expect(state.setValue).toHaveBeenCalledWith(
    ['baseUrl'],
    '$MY_SITE_URL',
    'typing'
  );

  state.change!(
    {kind: 'typing', path: ['baseUrl']},
    {...values, name: 'My Site', baseUrl: '$CUSTOM_URL'}
  );
  state.change!(
    {kind: 'typing', path: ['name']},
    {...values, name: 'Renamed Site', baseUrl: '$CUSTOM_URL'}
  );

  expect(state.setValue).toHaveBeenCalledOnce();
});

it.each([
  ['an existing site', 42, ''],
  ['a preconfigured base URL', null, '$CUSTOM_URL'],
])('preserves the base URL for %s', async (_, siteId, baseUrl) => {
  await mount(siteId, baseUrl);

  state.change!(
    {kind: 'typing', path: ['name']},
    {...values, siteId, name: 'My Site', baseUrl}
  );

  expect(state.setValue).not.toHaveBeenCalled();
});

async function mount(siteId: number | null, baseUrl = ''): Promise<void> {
  app = createApp(Edit, {
    site: {
      id: siteId ?? 0,
      uid: 'site-uid',
      name: 'Test site',
      nameRaw: 'Test site',
      handle: 'testSite',
      language: 'en-US',
      languageRaw: 'en-US',
      enabled: true,
      enabledRaw: true,
      groupId: 1,
      group: null,
      primary: false,
      hasUrls: true,
      baseUrl,
      baseUrlRaw: baseUrl,
      sortOrder: 1,
      dateCreated: '2026-01-01T00:00:00Z',
      dateUpdated: '2026-01-01T00:00:00Z',
    } satisfies Site,
    form: {...form, values: {...values, siteId, baseUrl}},
    submit: {method: 'post', url: '/settings/sites'},
    refreshUrl: '/settings/sites/form',
  });
  app.mount(container);
  await nextTick();
}
