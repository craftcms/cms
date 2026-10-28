import {setIconResolver} from '@craftcms/ui/utilities/icons';
import {nothing} from 'lit';
import {afterEach, beforeAll, describe, expect, it, vi} from 'vite-plus/test';
import {
  createApp,
  defineComponent,
  h,
  nextTick,
  provide,
  ref,
  type App,
} from 'vue';
import {
  ScreenContextKey,
  ScreenDetailsOverlayKey,
  ScreenDetailsRailKey,
  type ScreenMode,
} from '@/common/composables/screen';
import {
  ScreenPanelsKey,
  createScreenPanels,
  type ScreenPanels,
} from '@/common/composables/screenPanels';
import DetailsPanels from './DetailsPanels.vue';
import ScreenPanel from './ScreenPanel.vue';

vi.mock('@craftcms/ui', () => ({t: (message: string) => message}));

let app: App | undefined;

beforeAll(() => setIconResolver(() => nothing));

afterEach(() => {
  app?.unmount();
  app = undefined;
  document.body.innerHTML = '';
  window.history.replaceState(null, '', '/');
  localStorage.clear();
});

let registry: ScreenPanels;
/** Whether the page’s panels are mounted, which tests flip to change pages. */
const pagePanels = ref(true);
/** Whether the shell’s chat panel is mounted. */
const chatPanel = ref(false);
/** Whether a page panel that comes first in the rail is mounted, after the others. */
const latePanel = ref(false);
/** Whether the shell is overlaying the column, which tests can flip. */
const overlay = ref(false);

/** The page’s panels, as a page adds them. */
const PagePanels = defineComponent({
  setup: () => () => [
    h(ScreenPanel, {id: 'info', label: 'Info', icon: 'info'}, () =>
      h('p', 'Info content')
    ),
    h(ScreenPanel, {id: 'history', label: 'History', icon: 'clock'}, () =>
      h('div', [
        h('p', 'History content'),
        h('button', {type: 'button', class: 'inner'}, 'Inner'),
      ])
    ),
  ],
});

async function mount(
  rail: string | null,
  {
    syncLocationHash = false,
    overlaid = false,
    screenMode = 'page' as ScreenMode,
    chat = false,
  } = {}
): Promise<HTMLElement> {
  document.body.innerHTML = '<div id="details"></div><div id="rail"></div>';
  overlay.value = overlaid;
  pagePanels.value = true;
  chatPanel.value = chat;
  latePanel.value = false;
  registry = createScreenPanels();

  app = createApp(
    defineComponent({
      setup() {
        provide(ScreenContextKey, {mode: screenMode});
        provide(ScreenDetailsRailKey, rail);
        provide(ScreenDetailsOverlayKey, overlay);
        provide(ScreenPanelsKey, registry);

        return () => [
          h(DetailsPanels, {registry, syncLocationHash}),
          pagePanels.value ? h(PagePanels, {key: pageKey.value}) : null,
          latePanel.value
            ? h(
                ScreenPanel,
                {id: 'late', label: 'Late', icon: 'clock', order: -1},
                () => h('p', 'Late content')
              )
            : null,
          chatPanel.value
            ? h(
                ScreenPanel,
                {
                  id: 'chat',
                  label: 'Chat',
                  icon: 'sparkles',
                  order: -100,
                  persistent: true,
                  initialFocus: () =>
                    document.querySelector<HTMLElement>('.chat-box'),
                },
                () => h('textarea', {class: 'chat-box'})
              )
            : null,
        ];
      },
    })
  );
  app.mount('#details');
  await settle();

  return document.getElementById('details')!;
}

/** Changes `PagePanels`’ key, so its panels go and come back as a new page’s. */
const pageKey = ref(0);

async function settle(): Promise<void> {
  for (let i = 0; i < 4; i++) {
    await nextTick();
  }
}

function trigger(id: string): HTMLButtonElement {
  return document.querySelector<HTMLButtonElement>(
    `button#details-panel-${id}`
  )!;
}

function panel(id: string): HTMLElement {
  return document.getElementById(`details-panel-${id}-panel`)!;
}

function heading(id: string): HTMLElement {
  return panel(id).querySelector<HTMLElement>('h3')!;
}

it('puts the triggers in the rail and the panels in place', async () => {
  const details = await mount('#rail');
  const rail = document.getElementById('rail')!;
  const group = rail.querySelector('[role="group"]');

  expect(group?.getAttribute('aria-label')).toBe('Details');
  expect(
    [...rail.querySelectorAll('craft-disclosure > button')].map((button) =>
      button.getAttribute('aria-controls')
    )
  ).toEqual(['details-panel-info-panel', 'details-panel-history-panel']);
  expect(details.querySelector('craft-disclosure')).toBeNull();
  expect(panel('info').textContent).toContain('Info content');
  expect(panel('history').textContent).toContain('History content');
  expect(panel('info').getAttribute('aria-labelledby')).toBe(
    heading('info').id
  );
});

it('stays folded to the rail when the shell mounts it overlaid', async () => {
  await mount('#rail', {overlaid: true});

  expect(panel('info').hidden).toBe(true);
  expect(trigger('info').getAttribute('aria-expanded')).toBe('false');
});

// Resizing a slideout narrow and back shouldn't lose the person's panel.
it('reopens the panel the shell folded away once there’s room again', async () => {
  await mount('#rail');

  trigger('history').click();
  await settle();

  overlay.value = true;
  await settle();
  expect(panel('history').hidden).toBe(true);

  overlay.value = false;
  await settle();
  expect(panel('history').hidden).toBe(false);
  expect(panel('info').hidden).toBe(true);
});

it('leaves a panel closed that the person closed', async () => {
  await mount('#rail');

  trigger('info').click();
  await settle();

  overlay.value = true;
  await settle();
  overlay.value = false;
  await settle();

  expect(panel('info').hidden).toBe(true);
  expect(trigger('info').getAttribute('aria-expanded')).toBe('false');
});

it('starts folded to the rail in a slideout', async () => {
  await mount('#rail', {screenMode: 'slideout'});

  expect(panel('info').hidden).toBe(true);
  expect(trigger('info').getAttribute('aria-expanded')).toBe('false');
});

describe('in slideouts', () => {
  async function reopenSlideout(): Promise<void> {
    app?.unmount();
    await mount('#rail', {screenMode: 'slideout'});
  }

  it('opens the next slideout on the panel the person left open', async () => {
    await mount('#rail', {screenMode: 'slideout'});
    trigger('history').click();
    await settle();

    await reopenSlideout();

    expect(panel('history').hidden).toBe(false);
    expect(trigger('history').getAttribute('aria-expanded')).toBe('true');
  });

  it('keeps the next slideout folded once the person folds the column', async () => {
    await mount('#rail', {screenMode: 'slideout'});
    trigger('history').click();
    await settle();
    trigger('history').click();
    await settle();

    await reopenSlideout();

    expect(panel('history').hidden).toBe(true);
    expect(panel('info').hidden).toBe(true);
  });

  it('falls back to the first panel when the remembered one is missing', async () => {
    localStorage.setItem('Slideout.detailsPanel', JSON.stringify('missing'));

    await mount('#rail', {screenMode: 'slideout'});

    expect(panel('info').hidden).toBe(false);
  });

  it('doesn’t remember a panel the shell folded for lack of room', async () => {
    await mount('#rail', {screenMode: 'slideout'});
    trigger('history').click();
    await settle();

    overlay.value = true;
    await settle();

    await reopenSlideout();

    expect(panel('history').hidden).toBe(false);
  });

  it('leaves pages to the URL hash', async () => {
    localStorage.setItem('Slideout.detailsPanel', JSON.stringify(null));

    await mount('#rail');

    expect(panel('info').hidden).toBe(false);
  });
});

it('leaves the URL hash alone as the panels unmount', async () => {
  window.history.replaceState(null, '', '/#info');
  await mount('#rail', {syncLocationHash: true});

  app!.unmount();
  app = undefined;
  await settle();

  expect(window.location.hash).toBe('#info');
});

it('starts open on a page with room beside the content', async () => {
  await mount('#rail');

  expect(panel('info').hidden).toBe(false);
});

it('labels each trigger with a tooltip', async () => {
  await mount('#rail');

  expect(
    [...document.querySelectorAll('#rail craft-tooltip')].map((tooltip) => [
      tooltip.getAttribute('for'),
      tooltip.textContent?.trim(),
    ])
  ).toEqual([
    ['details-panel-info', 'Info'],
    ['details-panel-history', 'History'],
  ]);
});

it('keeps the triggers beside their panels without a rail', async () => {
  const details = await mount(null);

  expect(document.getElementById('rail')!.childElementCount).toBe(0);
  expect(details.querySelectorAll('craft-disclosure > button')).toHaveLength(2);
  expect(details.contains(panel('info'))).toBe(true);
});

describe.each([
  {mode: 'rail', rail: '#rail'},
  {mode: 'slideout', rail: null},
])('in $mode mode', ({rail}) => {
  it('opens the first panel without moving focus', async () => {
    await mount(rail);

    expect(trigger('info').getAttribute('aria-expanded')).toBe('true');
    expect(trigger('history').getAttribute('aria-expanded')).toBe('false');
    expect(panel('info').hidden).toBe(false);
    expect(panel('history').hidden).toBe(true);
    expect(document.activeElement).toBe(document.body);
  });

  it('opens one panel at a time and moves focus to its heading', async () => {
    await mount(rail);

    trigger('history').click();
    await settle();

    expect(trigger('history').getAttribute('aria-expanded')).toBe('true');
    expect(trigger('info').getAttribute('aria-expanded')).toBe('false');
    expect(panel('history').hidden).toBe(false);
    expect(panel('info').hidden).toBe(true);
    expect(document.activeElement).toBe(heading('history'));
  });

  it('closes the open panel from its trigger, leaving focus there', async () => {
    await mount(rail);

    trigger('info').focus();
    trigger('info').click();
    await settle();

    expect(trigger('info').getAttribute('aria-expanded')).toBe('false');
    expect(panel('info').hidden).toBe(true);
    expect(document.activeElement).toBe(trigger('info'));
  });

  it('closes on Escape from inside the panel and returns focus to its trigger', async () => {
    await mount(rail);

    trigger('history').click();
    await settle();
    panel('history')
      .querySelector<HTMLElement>('.inner')!
      .dispatchEvent(
        new KeyboardEvent('keydown', {key: 'Escape', bubbles: true})
      );
    await settle();

    expect(panel('history').hidden).toBe(true);
    expect(trigger('history').getAttribute('aria-expanded')).toBe('false');
    expect(document.activeElement).toBe(trigger('history'));
  });

  it('leaves Escape to a control inside the panel that handled it', async () => {
    await mount(rail);

    const event = new KeyboardEvent('keydown', {
      key: 'Escape',
      bubbles: true,
      cancelable: true,
    });
    event.preventDefault();
    heading('info').dispatchEvent(event);
    await settle();

    expect(panel('info').hidden).toBe(false);
  });

  it('closes from the panel’s close button and returns focus to its trigger', async () => {
    await mount(rail);

    panel('info')
      .querySelector<HTMLElement>('craft-button[aria-label="Close {tab}"]')!
      .click();
    await settle();

    expect(panel('info').hidden).toBe(true);
    expect(document.activeElement).toBe(trigger('info'));
  });
});

it('opens a panel from the URL hash without moving focus', async () => {
  window.history.replaceState(null, '', '#history');
  await mount('#rail', {syncLocationHash: true});

  expect(panel('history').hidden).toBe(false);
  expect(document.activeElement).toBe(document.body);
});

it('moves focus into a panel its screen opens', async () => {
  await mount('#rail');

  registry.open('history');
  await settle();

  expect(panel('history').hidden).toBe(false);
  expect(document.activeElement).toBe(heading('history'));
});

describe('with panels that stay from page to page', () => {
  it('adds them to the rail, but opens on the page’s own first panel', async () => {
    await mount('#rail', {chat: true});

    expect(
      [...document.querySelectorAll('#rail craft-disclosure > button')].map(
        (button) => button.id
      )
    ).toEqual([
      'details-panel-chat',
      'details-panel-info',
      'details-panel-history',
    ]);
    expect(panel('info').hidden).toBe(false);
  });

  it('opens one on request, with focus where the panel wants it, once it’s there', async () => {
    await mount('#rail');

    registry.open('chat');
    await settle();
    expect(panel('info').hidden).toBe(false);

    chatPanel.value = true;
    await settle();

    expect(panel('chat').hidden).toBe(false);
    expect(document.activeElement?.className).toBe('chat-box');
  });

  it('keeps one open on the next page', async () => {
    await mount('#rail', {chat: true});
    trigger('chat').click();
    await settle();

    pageKey.value++;
    await settle();

    expect(panel('chat').hidden).toBe(false);
    expect(panel('info').hidden).toBe(true);
  });

  it('folds the column on a page without panels of its own', async () => {
    await mount('#rail', {chat: true});

    pagePanels.value = false;
    await settle();

    expect(trigger('chat').getAttribute('aria-expanded')).toBe('false');
    expect(document.querySelector('#details section:not([hidden])')).toBeNull();
  });
});

it('opens the next page on its own first panel', async () => {
  await mount('#rail');
  trigger('history').click();
  await settle();

  pageKey.value++;
  await settle();

  expect(panel('info').hidden).toBe(false);
  expect(panel('history').hidden).toBe(true);
});

it('opens on a page panel that arrives later but comes first, until one is chosen', async () => {
  await mount('#rail');

  latePanel.value = true;
  await settle();
  expect(panel('late').hidden).toBe(false);

  latePanel.value = false;
  await settle();
  trigger('history').click();
  await settle();
  latePanel.value = true;
  await settle();

  expect(panel('history').hidden).toBe(false);
});
