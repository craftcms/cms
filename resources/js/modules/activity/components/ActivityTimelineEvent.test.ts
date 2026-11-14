import {createApp, h, nextTick} from 'vue';
import {afterEach, expect, it} from 'vite-plus/test';
import type {ActivityEvent} from '@/modules/activity/composables/useActivityTimeline';
import ActivityTimelineEvent from './ActivityTimelineEvent.vue';

const container = document.createElement('div');
let app: ReturnType<typeof createApp>;

afterEach(() => {
  app.unmount();
  container.remove();
});

async function mountEvent(event: Partial<ActivityEvent>): Promise<void> {
  document.body.append(container);
  app = createApp({
    render: () =>
      h(ActivityTimelineEvent, {
        event: {
          id: '1',
          type: 'CraftCms\\Cms\\Activity\\EventTypes\\ElementCreated',
          component: 'craft:activity-timeline-event',
          props: {},
          icon: 'plus',
          occurredAt: '2026-11-13T08:40:00+00:00',
          formattedOccurredAt: {
            date: '2026-11-13',
            dateLabel: 'Nov 13, 2026',
            time: '8:40 AM',
            full: 'November 13, 2026 at 8:40 AM UTC',
          },
          actor: {label: 'Craft CMS', url: null, deleted: false},
          impersonator: null,
          source: {label: 'Craft'},
          description: {text: 'Created', html: null},
          changes: [],
          ...event,
        } as ActivityEvent,
        elementType: 'CraftCms\\Cms\\Entry\\Elements\\Entry',
        elementId: 12,
        siteId: 1,
        last: true,
      }),
  });
  app.mount(container);
  await nextTick();
}

function origin(): string {
  return (
    container
      .querySelector('.activity-timeline__origin')
      ?.textContent?.replace(/\s+/g, ' ')
      .trim() ?? ''
  );
}

it('says what caused an event', async () => {
  await mountEvent({origin: 'MCP'});

  expect(origin()).toBe('via MCP');
});

it('links the operation an event was part of, when the viewer can see it', async () => {
  await mountEvent({
    origin: 'Import',
    operation: {label: 'Guides', url: '/admin/import/guides'},
  });

  expect(origin()).toBe('via Import: Guides');
  expect(
    container
      .querySelector('.activity-timeline__origin a')
      ?.getAttribute('href')
  ).toBe('/admin/import/guides');
});

it('names an operation the viewer can’t see without linking it', async () => {
  await mountEvent({origin: 'Import', operation: {label: 'Guides', url: null}});

  expect(origin()).toBe('via Import: Guides');
  expect(container.querySelector('.activity-timeline__origin a')).toBeNull();
});
