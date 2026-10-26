import {actionClient} from '@craftcms/ui';
import {createApp, defineComponent, h, nextTick} from 'vue';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import EntryMoverModal from './EntryMoverModal.vue';

const success = vi.fn();
const error = vi.fn();

vi.mock('@/modules/messages/useMessages', () => ({
  useMessages: () => ({success, error, notice: vi.fn()}),
}));

vi.mock('@/common/components/ModalForm.vue', () => ({
  default: defineComponent({
    props: ['isActive', 'title', 'submitLabel', 'submitDisabled', 'loading'],
    emits: ['submit', 'close'],
    setup(props, {slots, emit}) {
      return () =>
        h(
          'form',
          {onSubmit: (e: Event) => (e.preventDefault(), emit('submit'))},
          [
            h('h1', props.title),
            slots.default?.(),
            h(
              'button',
              {type: 'submit', disabled: props.submitDisabled},
              props.submitLabel
            ),
          ]
        );
    },
  }),
}));

const flush = async () => {
  for (let i = 0; i < 4; i++) {
    await Promise.resolve();
    await nextTick();
  }
};

describe('EntryMoverModal', () => {
  let app: ReturnType<typeof createApp>;
  let container: HTMLElement;
  const post = vi.spyOn(actionClient, 'post');
  const events: string[] = [];
  const request = {entryIds: [121], siteId: 1, currentSectionUid: 'exhibits'};

  function mount(): void {
    container = document.createElement('div');
    document.body.append(container);
    app = createApp(() =>
      h(EntryMoverModal, {
        request,
        onClose: () => events.push('close'),
        onMoved: () => events.push('moved'),
      })
    );
    app.mount(container);
  }

  beforeEach(() => {
    events.length = 0;
  });

  afterEach(() => {
    app.unmount();
    container.remove();
    vi.clearAllMocks();
  });

  it('moves the entries to the chosen section', async () => {
    post.mockImplementation(async (url: string) =>
      url.includes('move-to-section-modal-data')
        ? {data: {sections: [{id: 16, label: 'Test'}]}}
        : {data: {message: 'Entries have been moved to the “Test” section.'}}
    );
    mount();
    await flush();

    expect(post).toHaveBeenCalledWith(
      expect.stringContaining('entries/move-to-section-modal-data'),
      request,
      expect.anything()
    );

    const radio = container.querySelector('craft-radio') as HTMLElement & {
      label: string;
    };
    expect(radio.label).toBe('Test');
    expect(
      container.querySelector<HTMLButtonElement>('button[type=submit]')!
        .disabled
    ).toBe(true);

    const group = container.querySelector(
      'craft-radio-group'
    ) as HTMLElement & {
      modelValue: string;
    };
    group.modelValue = '16';
    group.dispatchEvent(new Event('model-value-changed'));
    await flush();

    container.querySelector('form')!.requestSubmit();
    await flush();

    expect(post).toHaveBeenLastCalledWith(
      expect.stringContaining('entries/move-to-section'),
      {entryIds: [121], sectionId: 16}
    );
    expect(success).toHaveBeenCalledWith(
      'Entries have been moved to the “Test” section.'
    );
    expect(events).toEqual(['moved']);
  });

  it('says when no section accepts the entries', async () => {
    post.mockResolvedValue({data: {sections: []}});
    mount();
    await flush();

    const empty = container.querySelector('craft-empty') as HTMLElement & {
      label: string;
    };
    expect(empty.getAttribute('label') ?? empty.label).toContain(
      'Couldn’t find any sections'
    );
    expect(container.querySelector('craft-radio-group')).toBeNull();
  });
});
