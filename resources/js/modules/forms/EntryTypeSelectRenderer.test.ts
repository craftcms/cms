import {createApp, defineComponent, h, nextTick, ref} from 'vue';
import {afterEach, expect, it} from 'vite-plus/test';
import EntryTypeSelectRenderer from './EntryTypeSelectRenderer.vue';

const mountedApps: Array<ReturnType<typeof createApp>> = [];

afterEach(() => {
  mountedApps.splice(0).forEach((app) => app.unmount());
  document.body.innerHTML = '';
});

it('updates the Form value from the existing component selector', async () => {
  const value = ref<unknown[]>([]);
  const container = document.createElement('div');
  const host = defineComponent({
    setup() {
      return () =>
        h(EntryTypeSelectRenderer, {
          selectHtml: `
            <craft-component-select>
              <ul>
                <li><input type="hidden" value='{"uid":"article"}'></li>
                <li><input type="hidden" value='{"uid":"page"}'></li>
              </ul>
            </craft-component-select>
          `,
          'onUpdate:modelValue': (nextValue: unknown[]) => {
            value.value = nextValue;
          },
        });
    },
  });
  const app = createApp(host);

  document.body.appendChild(container);
  mountedApps.push(app);
  app.mount(container);
  await nextTick();

  container
    .querySelector('craft-component-select')!
    .dispatchEvent(new Event('change', {bubbles: true}));
  await nextTick();

  expect(value.value).toEqual([{uid: 'article'}, {uid: 'page'}]);
});
