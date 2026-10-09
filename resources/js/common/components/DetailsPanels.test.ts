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
import DetailsPanels from './DetailsPanels.vue';

vi.mock('@craftcms/ui', () => ({t: (message: string) => message}));

let app: App | undefined;

beforeAll(() => setIconResolver(() => nothing));

afterEach(() => {
  app?.unmount();
  app = undefined;
  document.body.innerHTML = '';
  window.history.replaceState(null, '', '/');
});

const panels = [
  {id: 'info', label: 'Info', icon: 'info', slot: 'info'},
  {id: 'history', label: 'History', icon: 'clock', slot: 'history'},
];

const exposed = ref<{select(panelId: string): void} | null>(null);

async function mount(
  rail: string | null,
  {
    syncLocationHash = false,
    overlaid = false,
    screenMode = 'page' as ScreenMode,
  } = {}
): Promise<HTMLElement> {
  document.body.innerHTML = '<div id="details"></div><div id="rail"></div>';

  app = createApp(
    defineComponent({
      setup() {
        provide(ScreenContextKey, {mode: screenMode});
        provide(ScreenDetailsRailKey, rail);
        provide(ScreenDetailsOverlayKey, ref(overlaid));

        return () =>
          h(
            DetailsPanels,
            {panels, syncLocationHash, ref: exposed},
            {
              info: () => h('p', 'Info content'),
              history: () =>
                h('div', [
                  h('p', 'History content'),
                  h('button', {type: 'button', class: 'inner'}, 'Inner'),
                ]),
            }
          );
      },
    })
  );
  app.mount('#details');
  await settle();

  return document.getElementById('details')!;
}

async function settle(): Promise<void> {
  await nextTick();
  await nextTick();
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

it('starts folded to the rail in a slideout', async () => {
  await mount('#rail', {screenMode: 'slideout'});

  expect(panel('info').hidden).toBe(true);
  expect(trigger('info').getAttribute('aria-expanded')).toBe('false');
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

it('moves focus into a panel its host selects', async () => {
  await mount('#rail');

  exposed.value!.select('history');
  await settle();

  expect(panel('history').hidden).toBe(false);
  expect(document.activeElement).toBe(heading('history'));
});
