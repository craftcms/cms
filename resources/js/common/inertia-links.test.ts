import {ConfigService} from '@craftcms/ui/services/Config';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import {createApp, h, nextTick} from 'vue';
import CpLink from '@/common/components/CpLink.vue';
import {handleControlPanelLinks} from './inertia-links';

const visit = vi.hoisted(() => vi.fn());

vi.mock('@inertiajs/vue3', async () => ({
  ...(await vi.importActual('@inertiajs/vue3')),
  router: {visit},
}));

const cpUrl = `${location.origin}/admin`;
let stop = () => {};

beforeEach(() => {
  ConfigService.getInstance().initialize({
    baseCpUrl: cpUrl,
    actionUrl: `${cpUrl}/actions`,
  });
  stop = handleControlPanelLinks();
  window.history.replaceState({}, '', '/admin/entries');
});

afterEach(() => {
  stop();
  document.body.innerHTML = '';
  visit.mockClear();
  window.history.replaceState({}, '', '/');
});

/** Clicks a link made from the HTML, and returns whether the browser was stopped from following it. */
function click(html: string, init: MouseEventInit = {}): boolean {
  document.body.innerHTML = html;
  const event = new MouseEvent('click', {
    bubbles: true,
    cancelable: true,
    composed: true,
    ...init,
  });
  document.querySelector('a')!.dispatchEvent(event);

  return event.defaultPrevented;
}

it.each([
  ['another page', `${cpUrl}/assets/images`],
  ['another page’s fragment', `${cpUrl}/settings#plugins`],
  ['the same page with another query', `${cpUrl}/entries?site=fr`],
  ['a link in a popover', `${cpUrl}/assets/images`, 'role="dialog"'],
])(
  'opens plain links to control panel pages with Inertia: %s',
  (_case, href, wrapper = '') => {
    expect(click(`<div ${wrapper}><a href="${href}">Link</a></div>`)).toBe(
      true
    );
    expect(visit).toHaveBeenCalledWith(href);
  }
);

// happy-dom doesn’t retarget events from shadow roots, unlike browsers, so this can’t prove the composed path is used.
it('opens links that web components render in their shadow roots', () => {
  document.body.innerHTML = '<span data-host></span>';
  const host = document.querySelector('[data-host]')!;
  host.attachShadow({
    mode: 'open',
  }).innerHTML = `<a href="${cpUrl}/entries">Entries</a>`;
  const event = new MouseEvent('click', {
    bubbles: true,
    cancelable: true,
    composed: true,
  });
  host.shadowRoot!.querySelector('a')!.dispatchEvent(event);

  expect(event.defaultPrevented).toBe(true);
  expect(visit).toHaveBeenCalledWith(`${cpUrl}/entries`);
});

it.each([
  ['a front-end URL', `<a href="${location.origin}/images/puck.png">File</a>`],
  [
    'a front-end URL that starts like the control panel’s',
    `<a href="${location.origin}/administer">Administer</a>`,
  ],
  ['a link to the page itself', '<a href="#">Menu item</a>'],
  ['a link to the page itself, by an empty href', '<a href="">Menu item</a>'],
  [
    'an encoded action URL',
    `<a href="${cpUrl}/%61ctions/assets/icon">Icon</a>`,
  ],
  [
    'a link in editable content',
    `<div contenteditable="true"><a href="${cpUrl}/entries">Entries</a></div>`,
  ],
  [
    'a link in a slideout',
    `<div data-slideout-id="slideout-1"><a href="${cpUrl}/assets">Assets</a></div>`,
  ],
  [
    'a link in a dialog',
    `<dialog open><a href="${cpUrl}/assets">Assets</a></dialog>`,
  ],
  [
    'a path that can’t be decoded',
    `<a href="${cpUrl}/%61ctions/plugin/export/report%">Report</a>`,
  ],
  [
    'a link in a legacy slideout',
    `<div data-slideout><a href="${cpUrl}/assets">Assets</a></div>`,
  ],
  [
    'a link in a Garnish modal',
    `<div role="dialog" aria-modal="true"><a href="${cpUrl}/assets">Assets</a></div>`,
  ],
  [
    'a control panel path that can’t be decoded',
    `<a href="${cpUrl}/entries/report%">Report</a>`,
  ],
  ['an href that can’t be parsed', '<a href="http://[">Broken</a>'],
  ['an action URL', `<a href="${cpUrl}/actions/assets/download">Download</a>`],
  ['another site', '<a href="https://example.com/admin/entries">Elsewhere</a>'],
  ['a new tab', `<a href="${cpUrl}/entries" target="_blank">Entries</a>`],
  ['a download', `<a href="${cpUrl}/entries" download>Entries</a>`],
  [
    'an opt-out',
    `<div data-inertia="false"><a href="${cpUrl}/entries">Entries</a></div>`,
  ],
  ['a link within the page', `<a href="${cpUrl}/entries#details">Details</a>`],
])('leaves %s to the browser', (_case, html) => {
  expect(click(html)).toBe(false);
  expect(visit).not.toHaveBeenCalled();
});

it('leaves modified clicks, and clicks something else handled, to them', () => {
  expect(click(`<a href="${cpUrl}/entries">Entries</a>`, {metaKey: true})).toBe(
    false
  );

  document.body.innerHTML = `<a href="${cpUrl}/entries">Entries</a>`;
  document
    .querySelector('a')!
    .addEventListener('click', (event) => event.preventDefault());
  document
    .querySelector('a')!
    .dispatchEvent(new MouseEvent('click', {bubbles: true, cancelable: true}));

  expect(visit).not.toHaveBeenCalled();
});

it('leaves downloads and opt-outs on the web components around a link to the browser', () => {
  for (const attribute of ['download', 'data-inertia="false"']) {
    document.body.innerHTML = `<span data-host ${attribute}></span>`;
    const host = document.querySelector('[data-host]')!;
    host.attachShadow({
      mode: 'open',
    }).innerHTML = `<a href="${cpUrl}/utilities/project-config/download">Download</a>`;
    const event = new MouseEvent('click', {
      bubbles: true,
      cancelable: true,
      composed: true,
    });
    host.shadowRoot!.querySelector('a')!.dispatchEvent(event);

    expect(event.defaultPrevented).toBe(false);
  }

  expect(visit).not.toHaveBeenCalled();
});

it('lets handlers on the document handle a click first, whenever they were added', () => {
  const handle = (event: Event) => event.preventDefault();
  document.addEventListener('click', handle);

  try {
    expect(click(`<a href="${cpUrl}/entries">Entries</a>`)).toBe(true);
    expect(visit).not.toHaveBeenCalled();
  } finally {
    document.removeEventListener('click', handle);
  }
});

it('leaves links to a control panel on another host to the browser', () => {
  ConfigService.getInstance().initialize({
    baseCpUrl: 'https://cp.example.com/admin',
    actionUrl: 'https://cp.example.com/admin/actions',
  });

  expect(
    click('<a href="https://cp.example.com/admin/entries">Entries</a>')
  ).toBe(false);
  expect(visit).not.toHaveBeenCalled();
});

it('leaves CpLinks that don’t use Inertia to the browser', async () => {
  document.body.innerHTML = '<div id="host"></div>';
  const app = createApp({
    render: () =>
      h(
        CpLink,
        {href: `${cpUrl}/utilities/project-config/download`, inertia: false},
        () => 'Download'
      ),
  });
  app.mount('#host');
  await nextTick();

  const event = new MouseEvent('click', {bubbles: true, cancelable: true});
  document.querySelector('a')!.dispatchEvent(event);

  expect(event.defaultPrevented).toBe(false);
  expect(visit).not.toHaveBeenCalled();
  app.unmount();
});
