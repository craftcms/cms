import {createApp, h, nextTick} from 'vue';
import {afterEach, expect, it} from 'vite-plus/test';
import ActivityTimelineChanges from './ActivityTimelineChanges.vue';

const container = document.createElement('div');
let app: ReturnType<typeof createApp>;

afterEach(() => {
  app.unmount();
  container.remove();
});

it('starts a short change list collapsed', async () => {
  document.body.append(container);
  app = createApp({
    render: () =>
      h(ActivityTimelineChanges, {
        changes: [{label: 'Title', old: 'Before', new: 'After'}],
      }),
  });
  app.mount(container);
  await nextTick();

  const disclosure = container.querySelector('craft-disclosure')!;

  expect(disclosure).not.toBeNull();
  expect(disclosure.hasAttribute('opened')).toBe(false);
});
