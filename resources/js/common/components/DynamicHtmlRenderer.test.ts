import {afterEach, expect, it, vi} from 'vite-plus/test';
import {createApp, h, nextTick} from 'vue';
import DynamicHtmlRenderer from './DynamicHtmlRenderer.vue';

const ICON_WITH_PROLOG =
  '<?xml version="1.0" encoding="UTF-8"?>\n' +
  '<svg viewBox="0 0 108 108"><path d="M0 0"/></svg>';

async function render(html: string) {
  const container = document.createElement('div');
  document.body.append(container);

  const app = createApp({render: () => h(DynamicHtmlRenderer, {html})});
  app.mount(container);
  await nextTick();

  return {
    container,
    teardown: () => {
      app.unmount();
      container.remove();
    },
  };
}

afterEach(() => {
  vi.restoreAllMocks();
});

/**
 * The compiler is only lenient about processing instructions in a development
 * build, where it downgrades the failure to this warning and carries on. A
 * production build throws instead, and the error escapes far enough to take
 * the surrounding subtree with it — so the warning here is the only signal
 * available in tests that production would have blown up.
 */
it('does not hand an XML declaration to the template compiler', async () => {
  const warn = vi.spyOn(console, 'warn').mockImplementation(() => {});

  const {container, teardown} = await render(ICON_WITH_PROLOG);

  expect(
    warn.mock.calls.filter((args) =>
      args.some(
        (arg) =>
          typeof arg === 'string' && arg.includes('allowed only in XML context')
      )
    )
  ).toEqual([]);
  expect(container.querySelector('svg')).not.toBeNull();

  teardown();
});

it('still renders markup that has no declaration to strip', async () => {
  const {container, teardown} = await render('<span class="chip">Entry</span>');

  expect(container.querySelector('span.chip')?.textContent).toBe('Entry');

  teardown();
});

it('renders template interpolation in server HTML as text', async () => {
  const {container, teardown} = await render(
    '<span class="title">{{ 7 * 6 }}</span>'
  );

  expect(container.querySelector('span.title')?.textContent).toBe(
    '{{ 7 * 6 }}'
  );

  teardown();
});

/**
 * Escapers that leave `"` unencoded (`ENT_NOQUOTES`, `strip_tags()`, a DOM
 * re-serialization) must not reopen interpolation, in text or in the
 * `<textarea>`/`<title>` contents the compiler also interpolates.
 */
it.each([
  ['text', '<span>{{"$emit.constructor(`window.pwned=1`)()"}}</span>'],
  [
    'a textarea',
    '<textarea>{{"$emit.constructor(`window.pwned=1`)()"}}</textarea>',
  ],
  [
    'a title',
    '<svg><title>{{"$emit.constructor(`window.pwned=1`)()"}}</title></svg>',
  ],
  [
    'text with every delimiter character',
    '<span><{{ $emit.constructor(`window.pwned=1`)() }}></span>',
  ],
])(
  'does not run template expressions in %s, however it was escaped',
  async (_where, html) => {
    const globals = window as unknown as {pwned?: number};
    delete globals.pwned;

    const {teardown} = await render(html);

    expect(globals.pwned).toBeUndefined();

    teardown();
  }
);

it('still compiles bound attributes', async () => {
  const {container, teardown} = await render('<span :data-n="7 * 6"></span>');

  expect(container.querySelector('span')?.dataset.n).toBe('42');

  teardown();
});
