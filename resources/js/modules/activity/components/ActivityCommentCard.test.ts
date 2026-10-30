import {createApp, h, nextTick, type App} from 'vue';
import {afterEach, expect, it} from 'vite-plus/test';
import type {ActivityTarget} from '@/modules/activity/composables/useActivityTimeline';
import ActivityCommentCard from './ActivityCommentCard.vue';

let app: App | null = null;

afterEach(() => {
  app?.unmount();
  app = null;
  document.body.innerHTML = '';
});

it.each<[string, ActivityTarget, string]>([
  [
    'another user, with their own colors',
    {
      type: 'user',
      id: 2,
      label: 'Grace',
      url: null,
      deleted: false,
      photoColors: ['teal', 'amber'],
    },
    'var(--color-teal-500)',
  ],
  [
    'an actor without colors, without any',
    {type: 'system', id: null, label: 'Craft', url: null, deleted: false},
    '',
  ],
])('tints a comment by %s', async (_, actor, startColor) => {
  const container = document.createElement('div');
  document.body.append(container);
  app = createApp(() =>
    h(ActivityCommentCard, {
      actor,
      impersonator: null,
      html: '<p>Looks good.</p>',
      occurredAt: '2026-09-15T09:41:00+00:00',
      formattedOccurredAt: {time: '9:41 AM', full: 'September 15, 2026'},
    })
  );
  app.mount(container);
  await nextTick();

  const card = container.querySelector<HTMLElement>('.activity-comment-card')!;

  expect(card.style.getPropertyValue('--photo-color-start')).toBe(startColor);
});
