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
