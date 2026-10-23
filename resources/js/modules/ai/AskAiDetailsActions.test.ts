import {
  createApp,
  defineComponent,
  h,
  nextTick,
  provide,
  shallowRef,
} from 'vue';
import {afterEach, describe, expect, it} from 'vite-plus/test';
import AskAiDetailsActions from './AskAiDetailsActions.vue';
import {
  AskAiEditorKey,
  type AskAiConversationTurn,
  type AskAiEditor,
} from './ask-ai';

describe('AskAiDetailsActions', () => {
  let container: HTMLElement;
  let unmount: () => void;

  async function mount(turns: AskAiConversationTurn[]) {
    const conversation = shallowRef(turns);
    container = document.createElement('div');
    document.body.append(container);
    const app = createApp(
      defineComponent({
        setup() {
          provide(AskAiEditorKey, {conversation} as unknown as AskAiEditor);

          return () => h(AskAiDetailsActions);
        },
      })
    );
    app.mount(container);
    unmount = () => app.unmount();
    await nextTick();

    return conversation;
  }

  afterEach(() => {
    unmount();
    container.remove();
  });

  it('offers a new conversation only once there is one, and empties it', async () => {
    const conversation = await mount([
      {id: 1, prompt: 'Shorter', result: null, applied: []},
    ]);
    const button = container.querySelector('craft-button')!;

    expect(button.textContent!.trim()).toBe('New conversation');

    button.dispatchEvent(new Event('click'));
    await nextTick();

    expect(conversation.value).toEqual([]);
    expect(container.querySelector('craft-button')).toBeNull();
  });
});
