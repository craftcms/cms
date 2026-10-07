import {afterEach, expect, it, vi} from 'vite-plus/test';
import {createApp, defineComponent, h, nextTick, provide, type App} from 'vue';
import {ScreenDetailsRailKey} from '@/common/composables/screen';
import DetailsTabs from './DetailsTabs.vue';

vi.mock('@craftcms/ui', () => ({t: (message: string) => message}));

let app: App | undefined;

afterEach(() => {
  app?.unmount();
  app = undefined;
  document.body.innerHTML = '';
});

const tabs = [
  {id: 'info', label: 'Info', icon: 'info', slot: 'info'},
  {id: 'history', label: 'History', icon: 'clock', slot: 'history'},
];

async function mount(rail: string | null): Promise<HTMLElement> {
  document.body.innerHTML = '<div id="details"></div><div id="rail"></div>';

  app = createApp(
    defineComponent({
      setup() {
        provide(ScreenDetailsRailKey, rail);

        return () =>
          h(
            DetailsTabs,
            {tabs},
            {
              info: () => h('p', 'Info content'),
              history: () => h('p', 'History content'),
            }
          );
      },
    })
  );
  app.mount('#details');
  await nextTick();
  await nextTick();

  return document.getElementById('details')!;
}

it('puts the strip in the rail and drives its panels in place', async () => {
  const details = await mount('#rail');
  const rail = document.getElementById('rail')!;
  const tabElements = [...rail.querySelectorAll('craft-tab')];

  expect(rail.querySelector('craft-tabs')).not.toBeNull();
  expect(details.querySelector('craft-tabs')).toBeNull();
  expect(tabElements.map((tab) => tab.getAttribute('controls'))).toEqual([
    'details-tab-info-panel',
    'details-tab-history-panel',
  ]);
  expect(
    details.querySelector('#details-tab-info-panel')?.textContent
  ).toContain('Info content');
  expect(
    details.querySelector('#details-tab-history-panel')?.textContent
  ).toContain('History content');
});

it('keeps the strip and its panels together without a rail', async () => {
  const details = await mount(null);
  const strip = details.querySelector('craft-tabs')!;

  expect(document.getElementById('rail')!.childElementCount).toBe(0);
  expect(strip.querySelectorAll(':scope > [slot="panel"]')).toHaveLength(2);
  expect(strip.querySelector('craft-tab')?.hasAttribute('controls')).toBe(
    false
  );
});
