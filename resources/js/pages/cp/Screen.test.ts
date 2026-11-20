import {detectPlatform} from '@tanstack/hotkeys';
import {createApp, h, type App} from 'vue';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import LayoutSlotOutlet from '@/common/components/LayoutSlotOutlet.vue';
import {provideLayoutSlotRegistry} from '@/common/composables/layoutSlots';
import {
  createScreenPropsStore,
  ScreenContentReadyKey,
  ScreenPropsStoreKey,
} from '@/common/composables/screen';
import Screen from './Screen.vue';

vi.mock('@craftcms/ui', () => import('@craftcms/ui/utilities/dom'));

const mod = detectPlatform() === 'mac' ? 'metaKey' : 'ctrlKey';

let app: App;
let host: HTMLElement;

afterEach(() => {
  app?.unmount();
  host?.remove();
});

it.each(['actionButton', 'actionMenu', 'contextMenu', 'all'])(
  'mounts %s in its header region before releasing legacy ready JS',
  async (selection) => {
    const fragments = {
      actionButton: '<a href="/new">New</a>',
      actionMenu: '<button type="button">More actions</button>',
      contextMenu: '<button type="button">Choose site</button>',
    };
    const props = Object.fromEntries(
      Object.entries(fragments).filter(
        ([name]) => selection === 'all' || name === selection
      )
    );
    let readyText: string | undefined;

    host = document.createElement('div');
    document.body.append(host);
    app = createApp({
      setup() {
        provideLayoutSlotRegistry();

        return () =>
          h('div', [
            h('header', [
              h(LayoutSlotOutlet, {name: 'context-menu'}),
              h(LayoutSlotOutlet, {name: 'content-actions'}),
            ]),
            h(Screen, props),
          ]);
      },
    });
    app.provide(ScreenPropsStoreKey, createScreenPropsStore());
    app.provide(ScreenContentReadyKey, () => {
      readyText ??= host.querySelector('header')?.textContent ?? '';
    });
    app.mount(host);

    const expected = {
      actionButton: 'New',
      actionMenu: 'More actions',
      contextMenu: 'Choose site',
    };
    await vi.waitFor(() => {
      for (const name of Object.keys(props)) {
        expect(readyText).toContain(expected[name as keyof typeof expected]);
      }
    });

    if (props.actionButton) {
      const link = host.querySelector('header a');
      expect(link?.getAttribute('href')).toBe('/new');
    }
    if (props.contextMenu) {
      expect(
        host.querySelector('[data-layout-slot="context-menu"]')?.textContent
      ).toBe('Choose site');
    }
    if (props.actionMenu) {
      expect(
        host.querySelector('[data-layout-slot="content-actions"]')?.textContent
      ).toContain('More actions');
    }
  }
);

it.each([
  {
    kind: 'a full-page form',
    fullPageForm: true,
    slot: 'primary-action',
    other: 'content-actions',
  },
  {
    kind: 'a screen with no form',
    fullPageForm: false,
    slot: 'content-actions',
    other: 'primary-action',
  },
])(
  'puts $kind\u2019s action button in $slot',
  async ({fullPageForm, slot, other}) => {
    host = document.createElement('div');
    document.body.append(host);
    app = createApp({
      setup() {
        provideLayoutSlotRegistry();

        return () =>
          h('div', [
            h(LayoutSlotOutlet, {name: 'content-actions'}),
            h(LayoutSlotOutlet, {name: 'primary-action'}, () =>
              h('span', 'Save')
            ),
            h(Screen, {
              fullPageForm,
              actionButton: '<input type="button" value="Save & Continue">',
            }),
          ]);
      },
    });
    app.provide(ScreenPropsStoreKey, createScreenPropsStore());
    app.provide(ScreenContentReadyKey, () => {});
    app.mount(host);

    const region = (name: string) =>
      host.querySelector(`[data-layout-slot="${name}"]`);

    await vi.waitFor(() => {
      expect(region(slot)?.querySelector('input')?.value).toBe(
        'Save & Continue'
      );
    });

    expect(region(other)?.querySelector('input')).toBeFalsy();
  }
);

it('leaves the shell\u2019s own save button alone when a form brings none', async () => {
  host = document.createElement('div');
  document.body.append(host);
  app = createApp({
    setup() {
      provideLayoutSlotRegistry();

      return () =>
        h('div', [
          h(LayoutSlotOutlet, {name: 'primary-action'}, () =>
            h('span', 'Save')
          ),
          h(Screen, {fullPageForm: true, content: '<p>Fields</p>'}),
        ]);
    },
  });
  app.provide(ScreenPropsStoreKey, createScreenPropsStore());
  app.provide(ScreenContentReadyKey, () => {});
  app.mount(host);

  await vi.waitFor(() => {
    expect(host.textContent).toContain('Fields');
  });

  expect(host.textContent).toContain('Save');
});

it('switches legacy tab panes without jQuery', async () => {
  expect((window as any).jQuery).toBeUndefined();

  host = document.createElement('div');
  document.body.append(host);
  app = createApp({
    setup() {
      provideLayoutSlotRegistry();

      return () =>
        h('div', [
          h(LayoutSlotOutlet, {name: 'content-tabs'}),
          h(Screen, {
            tabs: `<div class="pane-tabs"><div role="tablist">
              <a role="tab" id="tab-one" href="#pane-one" class="sel" aria-selected="true" tabindex="0">One</a>
              <a role="tab" id="tab-two" href="#pane-two" aria-selected="false" tabindex="-1">Two</a>
            </div></div>`,
            content: `<div id="pane-one">First</div><div id="pane-two" class="hidden">Second</div>`,
          }),
        ]);
    },
  });
  app.provide(ScreenPropsStoreKey, createScreenPropsStore());
  app.provide(ScreenContentReadyKey, () => {});
  app.mount(host);

  await vi.waitFor(() => {
    expect(host.querySelector('#tab-two')).not.toBeNull();
    expect(host.querySelector('#pane-two')).not.toBeNull();
  });

  host.querySelector<HTMLElement>('#tab-two')!.click();

  await vi.waitFor(() => {
    expect(host.querySelector('#pane-two')!.classList.contains('hidden')).toBe(
      false
    );
  });
  expect(host.querySelector('#pane-one')!.classList.contains('hidden')).toBe(
    true
  );
  expect(host.querySelector('#tab-two')!.getAttribute('aria-selected')).toBe(
    'true'
  );
});

function mountForm(props: Record<string, unknown>): HTMLFormElement {
  host = document.createElement('div');
  document.body.append(host);
  app = createApp({
    setup() {
      provideLayoutSlotRegistry();

      return () =>
        h('form', {id: 'main-form'}, [
          h(Screen, {content: '<p>Fields</p>', ...props}),
        ]);
    },
  });
  app.provide(ScreenPropsStoreKey, createScreenPropsStore());
  app.provide(ScreenContentReadyKey, () => {});
  app.mount(host);

  return host.querySelector('form')!;
}

function pressSave(shift = false): KeyboardEvent {
  const event = new KeyboardEvent('keydown', {
    key: shift ? 'S' : 's',
    code: 'KeyS',
    [mod]: true,
    shiftKey: shift,
    bubbles: true,
    cancelable: true,
  });
  document.body.dispatchEvent(event);

  return event;
}

function postedParams(form: HTMLFormElement): Record<string, string> {
  return Object.fromEntries(
    [...form.querySelectorAll<HTMLInputElement>('input[type=hidden]')].map(
      (input) => [input.name, input.value]
    )
  );
}

it('saves a full-page form on Mod+S', () => {
  const submit = vi
    .spyOn(HTMLFormElement.prototype, 'requestSubmit')
    .mockImplementation(() => {});
  const form = mountForm({
    fullPageForm: true,
    mainFormAttributes: {
      data: {saveshortcut: true, 'saveshortcut-redirect': 'encrypted-url'},
    },
  });

  expect(pressSave().defaultPrevented).toBe(true);
  expect(submit).toHaveBeenCalledTimes(1);
  expect(postedParams(form)).toEqual({redirect: 'encrypted-url'});
  expect(pressSave(true).defaultPrevented).toBe(false);

  submit.mockRestore();
});

it('saves with the form action a shortcut is assigned to', () => {
  const submit = vi
    .spyOn(HTMLFormElement.prototype, 'requestSubmit')
    .mockImplementation(() => {});
  const form = mountForm({
    fullPageForm: true,
    formActions: [
      {
        label: 'Save and add another',
        action: 'plugin/save',
        redirect: 'next',
        params: {addAnother: '1'},
        shortcut: true,
        shift: true,
      },
    ],
  });

  expect(pressSave().defaultPrevented).toBe(false);
  expect(pressSave(true).defaultPrevented).toBe(true);
  expect(submit).toHaveBeenCalledTimes(1);
  expect(postedParams(form)).toEqual({
    action: 'plugin/save',
    redirect: 'next',
    addAnother: '1',
  });

  submit.mockRestore();
});

it.each([
  ['a screen with no form', {fullPageForm: false}],
  [
    'a form that opted out',
    {fullPageForm: true, mainFormAttributes: {data: {saveshortcut: false}}},
  ],
])('leaves Mod+S to the browser on %s', (_, props) => {
  const submit = vi.spyOn(HTMLFormElement.prototype, 'requestSubmit');
  mountForm(props);

  expect(pressSave().defaultPrevented).toBe(false);
  expect(submit).not.toHaveBeenCalled();

  submit.mockRestore();
});

it('leaves the save to the legacy CP when it’s loaded', () => {
  const submitPrimaryForm = vi.fn();
  const form = mountForm({fullPageForm: true});
  vi.stubGlobal('Craft', {cp: {$primaryForm: [form], submitPrimaryForm}});

  expect(pressSave().defaultPrevented).toBe(true);
  expect(submitPrimaryForm).toHaveBeenCalledWith({
    redirect: undefined,
    retainScroll: false,
  });
  expect(postedParams(form)).toEqual({});

  vi.unstubAllGlobals();
});
