import {createApp, defineComponent, h, nextTick, type App} from 'vue';
import {setIconResolver} from '@craftcms/ui/utilities/icons';
import {nothing} from 'lit';
import {afterEach, beforeAll, expect, it, vi} from 'vite-plus/test';
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

beforeAll(() => setIconResolver(() => nothing));

let app: App | undefined;
let container: HTMLElement | undefined;

function mount(html: string | null, controls = false): void {
  container = document.createElement('div');
  document.body.append(container);
  app = createApp({
    render: () =>
      h(MetadataDetails, {html}, controls ? {default: () => h('input')} : {}),
  });
  app.mount(container);
}

afterEach(() => {
  app?.unmount();
  container?.remove();
});

it('shows the metadata in an Info panel', async () => {
  mount('<dl>ID 1</dl>');
  await nextTick();

  expect(container!.querySelector('craft-disclosure > button')?.id).toBe(
    'details-panel-info'
  );
  expect(container!.querySelector('.details-html')?.textContent).toBe(
    '<dl>ID 1</dl>'
  );
});

it('renders nothing without metadata', async () => {
  mount(null);
  await nextTick();

  expect(container!.querySelector('craft-disclosure')).toBeNull();
});

it.each(['<dl>ID 1</dl>', null])(
  'keeps sidebar controls with metadata %s',
  async (html) => {
    mount(html, true);
    await nextTick();

    const control = container!.querySelector('input');
    const details = container!.querySelector('.details-html');

    expect(control).not.toBeNull();
    expect(container!.querySelector('hr') !== null).toBe(html !== null);
    if (html === null) {
      expect(details).toBeNull();
      return;
    }
    expect(
      control!.compareDocumentPosition(details!) &
        Node.DOCUMENT_POSITION_FOLLOWING
    ).toBeTruthy();
  }
);
