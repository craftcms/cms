import {actionClient} from '@craftcms/ui';
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
  pageUrl?: string | null;
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
      axiosInstance: actionClient,
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

  async function scrollToEnd(): Promise<void> {
    await nextTick();

    if (timeline.value !== null) {
      timeline.value.scrollTop = timeline.value.scrollHeight;
    }
  }

  watch(
    () => props.active,
    (active) => {
      if (active && status.value === 'idle') {
        void load();
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
