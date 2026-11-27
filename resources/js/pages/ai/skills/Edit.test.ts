import {createApp, h, nextTick, type App} from 'vue';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import {UiFieldIdScope} from '@/modules/ui/runtime';

vi.mock('@inertiajs/vue3', async (importOriginal) => {
  const {reactive} = await import('vue');

  return {
    ...(await importOriginal<object>()),
    useForm: (initial: Record<string, unknown>) =>
      reactive({...initial, errors: {}}),
  };
});
vi.mock('@/common/composables/useAppLayout', () => ({useAppLayout: () => {}}));
vi.mock('@/modules/settings/composables/useSettingsSave', () => ({
  useSettingsSave: () => ({save: () => {}}),
}));
vi.mock('@/actions/CraftCms/Cms/Ai/Http/Controllers/SkillsController', () => ({
  store: () => ({}),
  update: () => ({}),
}));

const {default: Edit} = await import('./Edit.vue');
const {default: ErrorSummary} = await import('@/common/form/ErrorSummary.vue');
let app: App;
let root: HTMLElement;

afterEach(() => {
  app?.unmount();
  root?.remove();
});

it('links its errors to its fields inside a slideout', async () => {
  // A slideout scopes the ids its error summary links to, as two stacked
  // slideouts can show the same fields.
  root = document.createElement('div');
  document.body.append(root);
  app = createApp({
    render: () => [
      h(ErrorSummary, {errors: {title: 'Title cannot be blank.'}}),
      h(Edit, {skill: null}),
    ],
  });
  app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
  app.provide(UiFieldIdScope, 'slideout-1');
  app.mount(root);
  await nextTick();
  await nextTick();

  const link = root.querySelector<HTMLAnchorElement>('a[href^="#"]');

  expect(link?.getAttribute('href')).toBe('#slideout-1-ui-title');
  expect(root.querySelector('#slideout-1-ui-title')?.tagName).toBe(
    'CRAFT-FIELD'
  );
});
