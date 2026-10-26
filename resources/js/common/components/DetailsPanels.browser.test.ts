import '../../../css/cp.css';
import {setIconResolver} from '@craftcms/ui/utilities/icons';
import {nothing} from 'lit';
import {
  createApp,
  defineComponent,
  h,
  nextTick,
  provide,
  ref,
  type App,
} from 'vue';
import {afterEach, beforeAll, expect, it, vi} from 'vite-plus/test';
import {
  ScreenContextKey,
  ScreenDetailsOverlayKey,
  ScreenDetailsRailKey,
} from '@/common/composables/screen';
import {
  ScreenPanelsKey,
  createScreenPanels,
  type ScreenPanels,
} from '@/common/composables/screenPanels';
import DetailsPanels from './DetailsPanels.vue';
import ScreenPanel from './ScreenPanel.vue';

let app: App | undefined;
let registry: ScreenPanels;
/** Whether the page overlays the column, which tests flip as a page would narrow. */
const overlaid = ref(true);

beforeAll(() => setIconResolver(() => nothing));

afterEach(() => {
  app?.unmount();
  app = undefined;
  document.body.innerHTML = '';
});

function dialog(): HTMLDialogElement | null | undefined {
  return document
    .querySelector('craft-dialog')
    ?.shadowRoot?.querySelector('dialog');
}

async function mount(): Promise<void> {
  document.body.innerHTML = '<div id="details"></div><div id="rail"></div>';
  overlaid.value = true;
  registry = createScreenPanels();

  app = createApp(
    defineComponent({
      setup() {
        provide(ScreenContextKey, {mode: 'page'});
        provide(ScreenDetailsRailKey, '#rail');
        provide(ScreenDetailsOverlayKey, overlaid);
        provide(ScreenPanelsKey, registry);

        return () => [
          h(DetailsPanels, {registry}),
          h(ScreenPanel, {id: 'notes', label: 'Notes', icon: 'note'}, () =>
            h('input', {class: 'notes-input', 'aria-label': 'Notes'})
          ),
        ];
      },
    })
  );
  app.mount('#details');
  await vi.waitFor(() => expect(registry.panels.value).toHaveLength(1));
}

it('opens a panel that overlays a page in a modal dialog, with focus in the panel', async () => {
  await mount();

  registry.open('notes');

  await vi.waitFor(() => expect(dialog()?.matches(':modal')).toBe(true));
  expect(
    document.querySelector('craft-dialog')?.contains(document.activeElement)
  ).toBe(true);
  expect(document.querySelector('craft-dialog .notes-input')).not.toBeNull();
});

it('closes the panel when the dialog closes itself', async () => {
  await mount();
  registry.open('notes');
  await vi.waitFor(() => expect(dialog()?.open).toBe(true));

  document
    .querySelector('craft-dialog')!
    .dispatchEvent(new CustomEvent('craft-hide', {bubbles: true}));
  await nextTick();

  expect(registry.openPanelId.value).toBeNull();
});

it('stays open when a menu inside a panel closes', async () => {
  await mount();
  registry.open('notes');
  await vi.waitFor(() => expect(dialog()?.open).toBe(true));

  document
    .querySelector('.notes-input')!
    .dispatchEvent(
      new CustomEvent('craft-hide', {bubbles: true, composed: true})
    );
  await nextTick();

  expect(registry.openPanelId.value).toBe('notes');
  expect(dialog()?.open).toBe(true);
});

it('moves an open panel back into the column as the page widens, keeping its state', async () => {
  await mount();
  registry.open('notes');
  await vi.waitFor(() => expect(dialog()?.open).toBe(true));
  const input = document.querySelector<HTMLInputElement>('.notes-input')!;
  input.value = 'Unsaved';

  overlaid.value = false;

  await vi.waitFor(() =>
    expect(document.querySelector('craft-dialog')).toBeNull()
  );
  const moved = document.querySelector<HTMLInputElement>(
    '#details .notes-input'
  );
  expect(moved).toBe(input);
  expect(moved?.value).toBe('Unsaved');
  expect(registry.openPanelId.value).toBe('notes');
});
