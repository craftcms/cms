import {actionClient} from '@craftcms/ui';
import {createApp, defineComponent, h, nextTick} from 'vue';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import UiFormView from './UiFormView.vue';

vi.mock('@/modules/ui/UiRenderer.vue', () => ({
  default: defineComponent({
    props: ['payload', 'errors'],
    setup(_props, {expose}) {
      expose({canSubmit: () => true, currentValues: () => ({})});
      return () => h('div');
    },
  }),
}));

const flush = async () => {
  await Promise.resolve();
  await nextTick();
  await nextTick();
};

describe('UiFormView', () => {
  let app: ReturnType<typeof createApp>;
  let container: HTMLElement;
  const get = vi.spyOn(actionClient, 'get');

  async function mount(response: Record<string, unknown>): Promise<void> {
    get.mockResolvedValue({data: {ui: {nodes: [], values: {}}, ...response}});
    container = document.createElement('div');
    document.body.append(container);
    app = createApp(() =>
      h(UiFormView, {
        item: {id: 'publish', label: 'Publish'},
        view: {type: 'ui', modalUrl: 'things/modal', actionUrl: 'things'},
      })
    );
    app.mount(container);
    await flush();
  }

  beforeEach(() => {
    (window as any).Craft = {cp: {displayError: vi.fn()}};
  });

  afterEach(() => {
    app.unmount();
    container.remove();
    vi.clearAllMocks();
  });

  it('explains why it can’t be submitted in a tooltip on its submit button', async () => {
    await mount({
      submitDisabled: true,
      submitDisabledReason: '2 drafts are blocking publication.',
    });

    const submit = container.querySelector('craft-button[type="submit"]')!;
    const tooltip = container.querySelector('craft-tooltip')!;

    expect(submit.hasAttribute('focusable-when-disabled')).toBe(true);
    expect(tooltip.getAttribute('for')).toBe(submit.id);
    expect(tooltip.textContent?.trim()).toBe(
      '2 drafts are blocking publication.'
    );
  });

  it('drops the reason once the form can be submitted', async () => {
    await mount({submitDisabled: false, submitDisabledReason: 'Stale.'});

    expect(container.querySelector('craft-tooltip')).toBeNull();
  });
});
