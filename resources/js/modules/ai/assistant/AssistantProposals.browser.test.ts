import '@craftcms/ui/components/badge/badge';
import '@craftcms/ui/components/button/button';
import '@craftcms/ui/components/card/card';
import '@craftcms/ui/components/disclosure/disclosure';
import '@craftcms/ui/components/icon/icon';
import {createApp} from 'vue';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import {userEvent} from 'vite-plus/test/browser/context';
import AssistantProposals from './AssistantProposals.vue';
import {
  registerAssistantEditor,
  type ApplyAssistantProposalDetail,
  type AssistantProposal,
} from './context';

function proposal(key: string, label: string): AssistantProposal {
  return {
    key,
    ref: null,
    label,
    element: 'Lighthouses',
    elementId: 42,
    canonicalId: 42,
    siteId: 1,
    cpEditUrl: '/admin/entries/42',
    path: ['fields', key],
    value: 'New',
    diff: {current: 'Old', proposed: 'New'},
    preview: null,
    node: null,
  };
}

const proposals = [proposal('summary', 'Summary'), proposal('body', 'Body')];

/** Takes every change, as a field in the open editor would. */
function acceptChanges(event: Event): void {
  (event as CustomEvent<ApplyAssistantProposalDetail>).detail.applied = true;
}

let unregister = () => {};
let unmount = () => {};

beforeEach(() => {
  window.Craft = {systemUid: 'proposals-browser-test'} as never;
  localStorage.clear();
  unregister = registerAssistantEditor({
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
  });
  window.addEventListener('craft:apply-ai-assistant-proposal', acceptChanges);
});

afterEach(() => {
  unmount();
  unregister();
  window.removeEventListener(
    'craft:apply-ai-assistant-proposal',
    acceptChanges
  );
  document.body.innerHTML = '';
  delete (window as {Craft?: unknown}).Craft;
});

function mount(): HTMLElement {
  const host = document.createElement('div');
  document.body.append(host);
  const app = createApp(AssistantProposals, {
    proposals,
    threadId: 'thread-1',
    callId: 'call-1',
  });
  app.mount(host);
  unmount = () => app.unmount();

  return host;
}

/** Each card’s toggle, and whether its change is showing. */
function cards(host: HTMLElement): {toggle: HTMLElement; open: boolean}[] {
  return [...host.querySelectorAll('article')].map((article) => ({
    toggle: article.querySelector<HTMLElement>('h3 button')!,
    open: article
      .querySelector<HTMLElement>('craft-text-diff')!
      .checkVisibility(),
  }));
}

it('shows changes until they’re applied, then collapses them, after reloads too', async () => {
  let host = mount();

  await vi.waitFor(() =>
    expect(
      cards(host).map(({toggle, open}) => [
        toggle.getAttribute('aria-expanded'),
        open,
      ])
    ).toEqual([
      ['true', true],
      ['true', true],
    ])
  );

  await userEvent.click(cards(host)[1]!.toggle);
  await vi.waitFor(() => expect(cards(host)[1]!.open).toBe(false));
  expect(cards(host)[1]!.toggle.getAttribute('aria-expanded')).toBe('false');
  await userEvent.click(cards(host)[1]!.toggle);
  await vi.waitFor(() => expect(cards(host)[1]!.open).toBe(true));

  await userEvent.click(
    host.querySelector<HTMLElement>('article craft-button')!
  );

  await vi.waitFor(() =>
    expect(cards(host).map(({open}) => open)).toEqual([false, true])
  );
  expect(cards(host)[0]!.toggle.getAttribute('aria-expanded')).toBe('false');
  expect(document.activeElement).toBe(cards(host)[0]!.toggle);

  unmount();
  host = mount();

  await vi.waitFor(() =>
    expect(cards(host).map(({open}) => open)).toEqual([false, true])
  );
});
