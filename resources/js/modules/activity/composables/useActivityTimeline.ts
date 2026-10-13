import {actionClient} from '@craftcms/ui';
import {useEventListener, useResizeObserver} from '@vueuse/core';
import {computed, nextTick, onScopeDispose, watch, type Ref} from 'vue';
import {useFetch} from '@/common/composables/useFetch';

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
   * Whether the newest activity is kept in view. The timeline keeps growing
   * after it loads — each event is a lazily loaded component that renders in
   * afterwards — so a single scroll once it loads lands short of the end.
   * Instead it stays pinned as the content grows, until the reader scrolls
   * away from the end.
   */
  let pinnedToEnd = false;
  /** Where pinning last left the scroll position. */
  let pinnedScrollTop = 0;

  function pinToEnd(): void {
    if (pinnedToEnd && timeline.value !== null) {
      timeline.value.scrollTop = timeline.value.scrollHeight;
      pinnedScrollTop = timeline.value.scrollTop;
    }
  }

  async function scrollToEnd(): Promise<void> {
    pinnedToEnd = true;
    await nextTick();
    pinToEnd();
  }

  // `events` is read so the rail is observed once it has rendered.
  useResizeObserver(
    () =>
      events.value && timeline.value
        ? [
            timeline.value,
            ...Array.from(timeline.value.children).filter(
              (child): child is HTMLElement => child instanceof HTMLElement
            ),
          ]
        : [],
    pinToEnd
  );

  useEventListener(
    timeline,
    'scroll',
    () => {
      const element = timeline.value;

      if (element === null) {
        return;
      }

      // The scroll pinning sets off arrives late, after more may have rendered
      // below it, so only a move up from where pinning left it lets go.
      const atEnd =
        element.scrollHeight - element.scrollTop - element.clientHeight < 2;
      pinnedToEnd =
        atEnd || (pinnedToEnd && element.scrollTop >= pinnedScrollTop - 1);
    },
    {passive: true}
  );

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
