import {createApp, nextTick} from 'vue';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import AssistantProposals from './AssistantProposals.vue';
import {
  registerAssistantEditor,
  type ApplyAssistantProposalDetail,
  type AssistantEditor,
  type AssistantProposal,
} from './context';

const editor: AssistantEditor = {
  id: 'editor-1',
  params: () => ({
    elementType: 'CraftCms\\Cms\\Entry\\Elements\\Entry',
    elementId: 42,
    draftId: null,
    siteId: 1,
    ownerId: null,
    fieldId: null,
    canonicalId: 42,
  }),
  label: () => 'Lighthouses',
  scope: () => [],
  values: () => ({}),
  container: () => null,
};

function proposal(
  elementId: number,
  canonicalId = elementId
): AssistantProposal {
  return {
    key: `e1:summary-${elementId}`,
    ref: null,
    label: 'Summary',
    element: 'Lighthouses',
    elementId,
    canonicalId,
    siteId: 1,
    cpEditUrl: `/admin/entries/${elementId}`,
    path: ['fields', 'summary'],
    value: 'New summary',
    diff: {current: 'Old summary', proposed: 'New summary'},
    preview: null,
    node: null,
  };
}

/** Takes every change, as a field in the open editor would. */
function acceptChanges(event: Event): void {
  (event as CustomEvent<ApplyAssistantProposalDetail>).detail.applied = true;
}

describe('AssistantProposals', () => {
  let container: HTMLElement;
  let app: ReturnType<typeof createApp>;
  let unregister: () => void;
  const status = vi.fn();

  beforeEach(() => {
    window.Craft = {systemUid: 'proposals-test'} as never;
    localStorage.clear();
    container = document.createElement('div');
    document.body.append(container);
    unregister = registerAssistantEditor(editor);
    window.addEventListener('craft:apply-ai-assistant-proposal', acceptChanges);
  });

  afterEach(() => {
    app.unmount();
    container.remove();
    unregister();
    window.removeEventListener(
      'craft:apply-ai-assistant-proposal',
      acceptChanges
    );
    status.mockReset();
    delete (window as {Craft?: unknown}).Craft;
  });

  function mount(proposals: AssistantProposal[]): void {
    app = createApp(AssistantProposals, {
      proposals,
      threadId: 'thread-1',
      callId: 'call-1',
      onStatus: status,
    });
    app.mount(container);
  }

  it('applies a change to its editor, says so, and still shows it as applied after the page reloads', async () => {
    mount([proposal(42)]);

    container.querySelector<HTMLElement>('craft-button')!.click();
    await nextTick();

    expect(status).toHaveBeenCalledWith('Applied 1 change.');

    app.unmount();
    mount([proposal(42)]);

    expect(container.textContent).toContain('Applied');
    expect(container.querySelector('craft-button')).toBeNull();
  });

  it('applies a change proposed for a draft to the editor of its element, as saving the draft makes it the element', async () => {
    mount([proposal(321, 42)]);

    container.querySelector<HTMLElement>('craft-button')!.click();
    await nextTick();

    expect(status).toHaveBeenCalledWith('Applied 1 change.');
  });

  it('links to the element when no editor of it is open', () => {
    mount([proposal(7)]);

    const link = container.querySelector('a');

    expect(container.querySelector('craft-button')).toBeNull();
    expect(link?.textContent?.trim()).toBe(
      'Open “Lighthouses” to apply this change.'
    );
    expect(link?.getAttribute('href')).toBe('/admin/entries/7');
  });
});
