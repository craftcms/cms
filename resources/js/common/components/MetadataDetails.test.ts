import {createApp, defineComponent, h, nextTick, type App} from 'vue';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import MetadataDetails from './MetadataDetails.vue';

vi.mock('@craftcms/ui', () => ({t: (message: string) => message}));

vi.mock('@/common/components/DynamicHtmlRenderer.vue', () => ({
  default: defineComponent({
    props: {html: String},
    setup: (props) => () => h('div', {class: 'details-html'}, props.html),
  }),
}));

vi.mock('@/common/components/LayoutSlot.vue', () => ({
  default: defineComponent({
    setup:
      (_, {slots}) =>
      () =>
        h('div', slots.default?.()),
  }),
}));

let app: App | undefined;
let container: HTMLElement | undefined;

function mount(html: string | null): void {
  container = document.createElement('div');
  document.body.append(container);
  app = createApp(MetadataDetails, {html});
  app.mount(container);
}

afterEach(() => {
  app?.unmount();
  container?.remove();
});

it('shows the metadata in an Info tab', async () => {
  mount('<dl>ID 1</dl>');
  await nextTick();

  expect(container!.querySelector('craft-tab')?.id).toBe('details-tab-info');
  expect(container!.querySelector('.details-html')?.textContent).toBe(
    '<dl>ID 1</dl>'
  );
});

it('renders nothing without metadata', async () => {
  mount(null);
  await nextTick();

  expect(container!.querySelector('craft-tabs')).toBeNull();
});
