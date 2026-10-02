import {actionClient, type CraftTextExpander} from '@craftcms/ui';
import {createApp, h, nextTick} from 'vue';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import type {ActivityEvent} from '@/modules/activity/composables/useActivityTimeline';
import ActivityTimelineComment from './ActivityTimelineComment.vue';

vi.mock('../../markdown-field/markdown-field', () => {
  customElements.define(
    'craft-markdown-field',
    class extends HTMLElement {
      connectedCallback() {
        const textarea = document.createElement('textarea');
        textarea.id = this.id;
        this.id += '-editor';
        this.append(textarea);
      }
    }
  );

  return {};
});

const container = document.createElement('div');
let app: ReturnType<typeof createApp>;

afterEach(() => {
  app.unmount();
  container.remove();
  vi.restoreAllMocks();
});

it('renders an existing comment with its activity metadata', async () => {
  const event: ActivityEvent = {
    id: 'comment-1',
    component: 'craft:activity-timeline-comment',
    props: {},
    icon: 'comment',
    occurredAt: '2026-09-15T09:41:00+00:00',
    formattedOccurredAt: {
      date: '2026-09-15',
      dateLabel: 'Sep 15, 2026',
      time: '9:41 AM',
      full: 'September 15, 2026 at 9:41 AM UTC',
    },
    actor: {label: 'Ada', url: null, deleted: false},
    impersonator: null,
    source: {label: 'Craft'},
    description: {text: 'Commented.', html: null},
    changes: [],
    comment: {
      html: '<p>Please clarify this.</p>',
      markdown: null,
      edited: true,
      deleted: false,
      canEdit: false,
      canDelete: false,
    },
  };

  document.body.append(container);
  app = createApp({
    render: () =>
      h(ActivityTimelineComment, {
        event,
        elementType: 'CraftCms\\Cms\\Entry\\Elements\\Entry',
        elementId: 12,
        siteId: 1,
      }),
  });
  app.mount(container);
  await nextTick();

  expect(container.textContent).toContain('Ada commented.');
  expect(container.textContent).toContain('Please clarify this.');
  expect(container.textContent).toContain('Edited');
  expect(container.querySelector('time')?.textContent).toContain('9:41 AM');
});

it('inserts mentions into the active composer without changing another comment', async () => {
  vi.spyOn(HTMLElement.prototype, 'offsetWidth', 'get').mockReturnValue(1);
  vi.spyOn(actionClient, 'get').mockResolvedValue({
    data: [{label: 'Ada Lovelace', value: '[@ada](craft-user:42)'}],
  });

  document.body.append(container);
  app = createApp({
    render: () =>
      [1, 2].map((elementId) =>
        h(ActivityTimelineComment, {
          elementType: 'CraftCms\\Cms\\Entry\\Elements\\Entry',
          elementId,
          siteId: 1,
        })
      ),
  });
  app.mount(container);
  await nextTick();

  const editors = [...container.querySelectorAll('textarea')];
  expect(editors).toHaveLength(2);
  expect(new Set(editors.map((editor) => editor.id)).size).toBe(2);

  for (const composer of container.querySelectorAll(
    '[data-activity-comment-draft]'
  )) {
    const editor = composer.querySelector('textarea')!;
    const label = composer.querySelector('label')!;
    const expander = composer.querySelector<CraftTextExpander>(
      'craft-text-expander'
    )!;
    await expander.updateComplete;

    expect(label.control).toBe(editor);
    expect(editor.getAttribute('aria-controls')).toBe(
      expander.querySelector('[role="listbox"]')!.id
    );
  }

  const [firstEditor, secondEditor] = editors as [
    HTMLTextAreaElement,
    HTMLTextAreaElement,
  ];
  firstEditor.value = 'Another comment';
  firstEditor.dispatchEvent(new InputEvent('input', {bubbles: true}));
  secondEditor.focus();
  secondEditor.value = 'Hello @ad';
  secondEditor.setSelectionRange(9, 9);
  secondEditor.dispatchEvent(new InputEvent('input', {bubbles: true}));

  const secondComposer = secondEditor.closest('[data-activity-comment-draft]')!;
  await vi.waitFor(() => {
    expect(
      secondComposer.querySelector('[role="option"]')?.textContent
    ).toContain('Ada Lovelace');
    expect(
      secondComposer
        .querySelector('[role="option"]')
        ?.getAttribute('aria-selected')
    ).toBe('true');
  });

  secondEditor.dispatchEvent(
    new KeyboardEvent('keydown', {key: 'Enter', bubbles: true})
  );
  await nextTick();

  expect(secondEditor.value).toBe('Hello [@ada](craft-user:42)');
  expect(firstEditor.value).toBe('Another comment');
});
