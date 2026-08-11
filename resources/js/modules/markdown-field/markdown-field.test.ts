import {createApp, nextTick} from 'vue';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import MarkdownControl from '../forms/MarkdownControl.vue';

afterEach(() => {
  vi.restoreAllMocks();
  vi.unstubAllGlobals();
  document.body.replaceChildren();
});

it('applies properties from the Vue Markdown Control', async () => {
  const frames: FrameRequestCallback[] = [];
  let laidOut = false;
  vi.stubGlobal('requestAnimationFrame', (callback: FrameRequestCallback) => {
    frames.push(callback);

    return frames.length;
  });
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue({ok: false}));
  vi.spyOn(
    HTMLTextAreaElement.prototype,
    'scrollHeight',
    'get'
  ).mockImplementation(() => (laidOut ? 120 : 20));
  const container = document.createElement('div');
  document.body.append(container);
  createApp(MarkdownControl, {
    control: {
      type: 'Markdown',
      component: 'craft:markdown',
      props: {rows: 6, toolbarButtons: ['bold'], showToolbar: true},
      path: ['body'],
      mode: 'editable',
      deltaGroup: ['body'],
    },
    value: '**Markdown** value',
    editable: true,
    invalid: false,
    required: false,
  }).mount(container);
  await nextTick();

  expect(container.querySelector('textarea')?.getAttribute('rows')).toBe('6');
  expect(container.querySelector('.overtype-toolbar')).not.toBeNull();
  expect(
    container.querySelector<HTMLElement>('.overtype-wrapper')?.style.height
  ).toBe('20px');

  laidOut = true;
  while (frames.length) {
    frames.shift()!(performance.now());
  }

  expect(
    container.querySelector<HTMLElement>('.overtype-wrapper')?.style.height
  ).toBe('120px');
});
