import {readFileSync} from 'node:fs';
import {createApp, nextTick} from 'vue';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import MarkdownControl from '../forms/MarkdownControl.vue';
// The stylesheet's own text. happy-dom applies none of it, so an assertion
// that went through the cascade would pass against anything — and the test
// runner stubs CSS imports out to an empty string, so it's read from disk.
const styles = readFileSync(
  'resources/js/modules/markdown-field/markdown-field.css',
  'utf8'
);

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

it('scopes its styling to the element, which every render path has', () => {
  // The legacy Twig input wraps the element in `.markdown-field`; the Vue
  // control and the server-rendered control HTML don't, so anything keyed to
  // that wrapper — the border around `.overtype-container` included — was dead
  // in the CP.
  expect(styles).not.toMatch(/\.markdown-field[\s{]/);

  // OverType injects `.overtype-container * { border: 0 !important }` at
  // runtime. A bare element scope ties with that on specificity and loses on
  // source order, so the border has to outrank it.
  expect(styles).toMatch(
    /craft-markdown-field:defined \.overtype-container \{[^}]*border: var\(--markdown-field-border\)/
  );
});
