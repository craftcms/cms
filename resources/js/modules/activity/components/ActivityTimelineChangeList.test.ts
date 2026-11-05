import {createApp, h, nextTick} from 'vue';
import {afterEach, expect, it} from 'vite-plus/test';
import ActivityTimelineChangeList from './ActivityTimelineChangeList.vue';

const container = document.createElement('div');
let app: ReturnType<typeof createApp>;

afterEach(() => {
  app.unmount();
  container.remove();
});

it('shows a change from one text to another as a diff, and any other change before and after', async () => {
  document.body.append(container);
  app = createApp({
    render: () =>
      h(ActivityTimelineChangeList, {
        changes: [
          {label: 'Title', old: 'Old title', new: 'New title'},
          {label: 'Summary', old: null, new: 'A summary'},
          {label: 'Intro', old: '', new: 'An intro'},
          {label: 'Featured', old: false, new: true},
        ],
      }),
  });
  app.mount(container);
  await nextTick();

  const [title, ...others] = container.querySelectorAll('li');

  expect(title!.querySelector('craft-text-diff')).toMatchObject({
    current: 'Old title',
    proposed: 'New title',
  });
  expect(title!.querySelector('dl')).toBeNull();
  for (const item of others) {
    expect(item.querySelector('craft-text-diff')).toBeNull();
    expect(item.querySelector('dl')).not.toBeNull();
  }
});
