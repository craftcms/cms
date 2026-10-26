import {actionClient} from '@craftcms/ui';
import {computed, nextTick, onScopeDispose, watch, type Ref} from 'vue';
import {useFetch} from '@/common/composables/useFetch';
import {usePinnedScroll} from '@/common/composables/usePinnedScroll';

export interface ActivityTarget {
  label: string;
  url: string | null;
  deleted: boolean;
}

export interface ActivityChange {
  label: string;
  old: unknown;
  new: unknown;
}

export interface ActivityComment {
  html: string | null;
  markdown: string | null;
  edited: boolean;
  deleted: boolean;
  canEdit: boolean;
  canDelete: boolean;
}

export interface ActivityEvent {
  id: string;
  component: string;
  props: Record<string, unknown>;
  icon: string | null;
  occurredAt: string;
  formattedOccurredAt: {
    date: string;
    dateLabel: string;
    time: string;
    full: string;
  };
  actor: ActivityTarget;
  impersonator: ActivityTarget | null;
  origin?: string | null;
  source: {label: string};
  description: {text: string | null; html: string | null};
  changes: ActivityChange[];
  comment?: ActivityComment | null;
}

interface ActivityTimelineResponse {
  events: ActivityEvent[];
}

export interface ActivityTimelineProps {
  active: boolean;
  url: string;
  elementType: string;
  elementId: number | null;
  siteId: number | null;
  refreshToken?: number;
}

export function useActivityTimeline(
  props: ActivityTimelineProps,
  timeline: Ref<HTMLElement | null>
) {
  const {
    data,
    state: status,
    execute,
    abort,
  } = useFetch<ActivityTimelineResponse>(
    computed(() => props.url),
    {
      method: 'post',
      client: actionClient,
      immediate: false,
      refetch: false,
      onSuccess: () => void scrollToEnd(),
    }
  );
  const events = computed(() => data.value?.events ?? []);
  const hasLoaded = computed(() => data.value !== null);

  onScopeDispose(abort);

  async function load(): Promise<void> {
    if (props.elementId === null) {
      return;
    }

    await execute({
      elementType: props.elementType,
      elementId: props.elementId,
      siteId: props.siteId,
    });
  }

  function addOrUpdateEvent(event: ActivityEvent): void {
    const index = events.value.findIndex(({id}) => id === event.id);
    const updatedEvents = [...events.value];

    if (index === -1) {
      updatedEvents.push(event);
    } else {
      updatedEvents[index] = event;
    }

    data.value = {events: updatedEvents};
  }

  /**
   * Keeps the newest activity in view. The timeline keeps growing after it
   * loads — each event is a lazily loaded component that renders in afterwards
   * — so a single scroll once it loads lands short of the end. Instead it stays
   * pinned as the content grows, until the reader scrolls away from the end.
   */
  const timelineScroll = usePinnedScroll(
    timeline,
    // `events` is read so the rail is observed once it has rendered.
    () =>
      events.value && timeline.value
        ? [
            timeline.value,
            ...Array.from(timeline.value.children).filter(
              (child): child is HTMLElement => child instanceof HTMLElement
            ),
          ]
        : [],
    {pinned: false}
  );

  async function scrollToEnd(): Promise<void> {
    timelineScroll.pin();
    await nextTick();
    timelineScroll.stick();
  }

  watch(
    () => props.active,
    (active) => {
      if (active && status.value === 'idle') {
        void load();
      } else if (active && hasLoaded.value) {
        // Hiding the panel reset its scroll position.
        void scrollToEnd();
      }
    },
    {immediate: true}
  );

  watch(
    () => props.refreshToken,
    () => {
      if (hasLoaded.value) {
        void load();
      }
    }
  );

  const dayGroups = computed(() => {
    const groups = new Map<string, {label: string; events: ActivityEvent[]}>();

    for (const event of events.value) {
      const key = event.formattedOccurredAt.date;
      const group = groups.get(key) ?? {
        label: event.formattedOccurredAt.dateLabel,
        events: [],
      };

      group.events.push(event);
      groups.set(key, group);
    }

    return [...groups.entries()].map(([key, group]) => ({key, ...group}));
  });

  return {
    addOrUpdateEvent,
    dayGroups,
    events,
    hasLoaded,
    load,
    scrollToEnd,
    status,
  };
}
