import {expect, it} from 'vite-plus/test';
import {createApp, h, nextTick} from 'vue';
import Text from './Text.vue';

it('renders string params as text, not markup', async () => {
  const container = document.createElement('div');
  document.body.append(container);
  const app = createApp({
    render: () =>
      h(Text, {
        template: 'No user groups match “{query}”',
        params: {query: '<img src=x onerror=alert(1)>'},
      }),
  });
  app.mount(container);
  await nextTick();

  expect(container.querySelector('img')).toBeNull();
  expect(container.textContent).toBe(
    'No user groups match “<img src=x onerror=alert(1)>”'
  );

  app.unmount();
  container.remove();
});
