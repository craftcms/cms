import '../../../../../css/cp.css';
import {setIconResolver} from '@craftcms/ui/utilities/icons';
import {nothing} from 'lit';
import {
  createApp,
  defineComponent,
  h,
  nextTick,
  provide,
  type App,
  type VNode,
} from 'vue';
import {afterEach, beforeAll, expect, it, vi} from 'vite-plus/test';
import DetailsPanels from '@/common/components/DetailsPanels.vue';
import ScreenPanel from '@/common/components/ScreenPanel.vue';
import {
  ScreenPanelsKey,
  createScreenPanels,
  type ScreenPanels,
} from '@/common/composables/screenPanels';
import {useResizable} from '@/common/composables/useResizable';
import ContentDetails from './ContentDetails.vue';

let app: App | undefined;

beforeAll(() => setIconResolver(() => nothing));

afterEach(() => {
  app?.unmount();
  app = undefined;
  document.body.innerHTML = '';
});

/** A panel that scrolls itself and pins a footer, like the activity timeline. */
const SelfScrollingPanel = defineComponent({
  setup: () => () =>
    h(
      'div',
      {style: 'display: flex; flex-direction: column; block-size: 100%'},
      [
        h(
          'div',
          {style: 'overflow-y: auto'},
          h('div', {style: 'height: 2000px'})
        ),
        h('div', {'data-footer': ''}, 'Footer'),
      ]
    ),
});

function mount(
  content: (registry: ScreenPanels) => VNode | VNode[]
): HTMLElement {
  const host = document.createElement('div');
  host.style.height = '400px';
  host.style.width = '350px';
  document.body.append(host);
  app = createApp(
    defineComponent({
      setup() {
        const resizer = useResizable({
          target: null,
          minWidth: 200,
          maxWidth: 600,
        });
        const registry = createScreenPanels();
        provide(ScreenPanelsKey, registry);

        return () => h(ContentDetails, {resizer}, () => content(registry));
      },
    })
  );
  app.mount(host);
  return host;
}

/** Shows one panel, as a page adds it, in the column. */
async function mountPanel(content: () => VNode): Promise<HTMLElement> {
  const host = mount((registry) => [
    h(DetailsPanels, {registry}),
    h(ScreenPanel, {id: 'panel', label: 'Panel', icon: 'info'}, content),
  ]);

  await vi.waitFor(() => expect(host.querySelector('h3')).not.toBeNull());
  await nextTick();

  return host;
}

it('keeps the panel header in place while its content scrolls', async () => {
  const host = await mountPanel(() => h('div', {style: 'height: 2000px'}));
  const column = host.querySelector<HTMLElement>('.cp-details')!;
  const heading = host.querySelector<HTMLElement>('h3')!;
  const body = host.querySelector<HTMLElement>('section > :last-child')!;
  const headingTop = heading.getBoundingClientRect().top;

  expect(body.scrollHeight).toBeGreaterThan(body.clientHeight);
  body.scrollTop = body.scrollHeight;

  expect(body.scrollTop).toBeGreaterThan(0);
  expect(heading.getBoundingClientRect().top).toBe(headingTop);
  expect(column.scrollHeight).toBe(column.clientHeight);
});

it('gives a self-scrolling panel the height left under the header', async () => {
  const host = await mountPanel(() => h(SelfScrollingPanel));
  const body = host.querySelector<HTMLElement>('section > :last-child')!;
  const footer = host.querySelector<HTMLElement>('[data-footer]')!;

  expect(body.scrollHeight).toBe(body.clientHeight);
  expect(footer.getBoundingClientRect().bottom).toBeLessThanOrEqual(
    host.getBoundingClientRect().bottom
  );
});

it('scrolls content that isn’t a details panel', () => {
  const host = mount(() => h('div', {style: 'height: 2000px'}));
  const column = host.querySelector<HTMLElement>('.cp-details')!;

  expect(column.scrollHeight).toBeGreaterThan(column.clientHeight);
});
