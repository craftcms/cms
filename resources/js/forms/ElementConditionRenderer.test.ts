import {actionClient} from '@craftcms/ui';
import {createApp, defineComponent, h, nextTick, ref} from 'vue';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import type {ConditionConfig} from '@/modules/elements/composables/useConditionBuilder';
import ElementConditionRenderer from './ElementConditionRenderer.vue';

const mountedApps: Array<ReturnType<typeof createApp>> = [];

afterEach(() => {
  mountedApps.splice(0).forEach((app) => app.unmount());
  document.body.innerHTML = '';
  vi.unstubAllGlobals();
  vi.restoreAllMocks();
});

it('renders and normalizes the existing server condition builder', async () => {
  const process = vi.fn();
  const initUiElements = vi.fn();

  vi.stubGlobal('htmx', {process});
  vi.stubGlobal('Craft', {initUiElements});
  const post = vi.spyOn(actionClient, 'post').mockResolvedValue({
    data: `
      <div class="condition-main">
        <input type="hidden" name="condition[class]" value="EntryCondition">
        <input name="condition[conditionRules][1][type]" value="title">
      </div>
    `,
  });
  const condition = ref<ConditionConfig | null>(null);
  const container = document.createElement('div');
  const host = defineComponent({
    setup() {
      return () =>
        h(ElementConditionRenderer, {
          id: 'entry-condition',
          conditionClass: 'EntryCondition',
          builderConfig: {elementType: 'entry'},
          renderUrl: '/actions/conditions/render',
          modelValue: condition.value,
          'onUpdate:modelValue': (value) => (condition.value = value),
        });
    },
  });

  document.body.append(container);
  const app = createApp(host);

  mountedApps.push(app);
  app.mount(container);

  await vi.waitFor(() => {
    expect(container.querySelector('.condition-main')).not.toBeNull();
  });

  const request = post.mock.calls[0]![1] as {config: string};

  expect(JSON.parse(request.config)).toMatchObject({
    class: 'EntryCondition',
    elementType: 'entry',
    id: 'entry-condition',
    name: 'condition',
  });
  expect(process).toHaveBeenCalledWith(
    container.querySelector('#entry-condition')
  );
  expect(initUiElements).toHaveBeenCalledWith(
    container.querySelector('#entry-condition')
  );

  container
    .querySelector('.condition-main')!
    .dispatchEvent(new Event('htmx:afterSwap', {bubbles: true}));
  await nextTick();

  expect(condition.value).toEqual({
    class: 'EntryCondition',
    conditionRules: [{type: 'title'}],
  });
});
