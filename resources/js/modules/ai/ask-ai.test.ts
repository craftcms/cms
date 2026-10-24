import {nextTick} from 'vue';
import {beforeEach, describe, expect, it} from 'vite-plus/test';
import {useAskAiConversation, type AskAiResult} from './ask-ai';

const entry = {
  elementType: 'Entry',
  canonicalId: 4,
  siteId: 1,
  draftId: null,
  isProvisionalDraft: false,
};

const result: AskAiResult = {
  proposals: [],
  summary: 'Shortened it.',
  findings: [],
  turn: {prompt: 'Shorter', answer: '{}'},
};

describe('useAskAiConversation', () => {
  beforeEach(() => {
    (globalThis as {Craft?: {systemUid: string}}).Craft = {systemUid: 'test'};
    sessionStorage.clear();
  });

  it('keeps the conversation across a refresh, without replies still being generated', async () => {
    const conversation = useAskAiConversation(entry, 1);
    conversation.value = [
      {id: 1, prompt: 'Shorter', result, applied: ['summary']},
      {id: 2, prompt: 'Even shorter', result: null, applied: []},
    ];
    await nextTick();

    const restored = useAskAiConversation(entry, 1).value;

    expect(restored.map((turn) => turn.prompt)).toEqual(['Shorter']);
    expect(restored[0]!.result).toEqual(result);
    expect(restored[0]!.applied).toEqual(['summary']);
    expect(useAskAiConversation({...entry, siteId: 2}, 1).value).toEqual([]);
  });

  it('keeps nothing without a key', async () => {
    useAskAiConversation({...entry, canonicalId: null}, 1).value = [
      {id: 1, prompt: 'Shorter', result, applied: []},
    ];
    await nextTick();

    expect(sessionStorage.length).toBe(0);
  });

  it('shares a conversation with the provisional draft autosave creates, but not with a named draft', async () => {
    useAskAiConversation(entry, 1).value = [
      {id: 1, prompt: 'Shorter', result, applied: []},
    ];
    await nextTick();

    expect(
      useAskAiConversation({...entry, draftId: 9, isProvisionalDraft: true}, 1)
        .value
    ).toHaveLength(1);
    expect(useAskAiConversation({...entry, draftId: 9}, 1).value).toEqual([]);
  });

  it('removes other users’ conversations, so another sign-in in the tab can’t read them', async () => {
    useAskAiConversation(entry, 1).value = [
      {id: 1, prompt: 'Shorter', result, applied: []},
    ];
    await nextTick();

    expect(useAskAiConversation(entry, 2).value).toEqual([]);
    expect(useAskAiConversation(entry, 1).value).toEqual([]);
  });
});
