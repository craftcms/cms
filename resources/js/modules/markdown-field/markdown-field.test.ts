import {createApp, nextTick} from 'vue';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import MarkdownControl from '../forms/MarkdownControl.vue';
import './markdown-field';

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
      props: {
        rows: 6,
        toolbarButtons: ['bold'],
        showToolbar: true,
        textExpanderTriggers: [
          {
            trigger: '@',
            boundary: 'whitespace',
            options: [{label: 'Ada Lovelace', value: '@ada'}],
          },
        ],
      },
      path: ['body'],
      mode: 'editable',
      deltaGroup: ['body'],
    },
    value: '**Markdown** value',
    editable: true,
    invalid: false,
    required: false,
    slot: 'input',
    'data-form-control-path': '["body"]',
  }).mount(container);
  await nextTick();

  const textarea = container.querySelector('textarea')!;
  const textExpander = container.querySelector('craft-text-expander')!;

  expect(textarea.getAttribute('rows')).toBe('6');
  expect(textarea.closest('craft-markdown-field')).toMatchObject({
    slot: 'input',
    dataset: {formControlPath: '["body"]'},
  });
  expect(container.querySelector('.overtype-toolbar')).not.toBeNull();
  expect(textExpander.for).toBe(textarea.id);
  expect(textExpander.slot).toBe('input');

  textarea.focus();
  textarea.value = '@a';
  textarea.setSelectionRange(2, 2);
  textarea.dispatchEvent(new InputEvent('input', {bubbles: true}));

  expect(container.querySelector('craft-option')?.textContent).toContain(
    'Ada Lovelace'
  );
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

it('resizes after becoming visible', () => {
  let notifyVisible: (() => void) | undefined;
  let laidOut = false;

  vi.stubGlobal('requestAnimationFrame', vi.fn());
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue({ok: false}));
  vi.stubGlobal(
    'ResizeObserver',
    class {
      constructor(callback: ResizeObserverCallback) {
        notifyVisible = () => {
          callback(
            [
              {
                contentRect: {width: 320},
              } as ResizeObserverEntry,
            ],
            this as ResizeObserver
          );
        };
      }

      observe() {}

      unobserve() {}

      disconnect() {}
    }
  );
  vi.spyOn(
    HTMLTextAreaElement.prototype,
    'scrollHeight',
    'get'
  ).mockImplementation(() => (laidOut ? 120 : 0));

  const markdownField = document.createElement('craft-markdown-field');
  document.body.append(markdownField);

  expect(
    markdownField.querySelector<HTMLElement>('.overtype-wrapper')?.style.height
  ).toBe('0px');

  laidOut = true;
  notifyVisible?.();

  expect(
    markdownField.querySelector<HTMLElement>('.overtype-wrapper')?.style.height
  ).toBe('120px');
});
