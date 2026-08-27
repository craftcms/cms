import {actionClient} from '@craftcms/ui';
import {computed, nextTick, ref, watch, type Ref} from 'vue';

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
  const events = ref<ActivityEvent[]>([]);
  const status = ref<'idle' | 'loading' | 'loaded' | 'error'>('idle');

  function requestData() {
    return {
      elementType: props.elementType,
      elementId: props.elementId,
      siteId: props.siteId,
    };
  }

  async function load(): Promise<void> {
    if (props.elementId === null) {
      return;
    }

    const refreshing = status.value === 'loaded';

    if (!refreshing) {
      status.value = 'loading';
    }

    try {
      const {data} = await actionClient.post<ActivityTimelineResponse>(
        props.url,
        requestData()
      );

      events.value = data.events;
      status.value = 'loaded';
      await scrollToEnd();
    } catch {
      if (!refreshing) {
        status.value = 'error';
      }
    }
  }

  function addOrUpdateEvent(event: ActivityEvent): void {
    const index = events.value.findIndex(({id}) => id === event.id);

    if (index === -1) {
      events.value.push(event);

      return;
    }

    events.value[index] = event;
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
      if (status.value === 'loaded') {
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
    load,
    scrollToEnd,
    status,
  };
}
