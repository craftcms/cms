import {getActionUrl} from '@craftcms/ui';
import AssistantController from '@/actions/CraftCms/Cms/Ai/Http/Controllers/AssistantController';
import {actionFetch} from './assistant';

/** A conversation in the chat history. */
export interface AssistantConversation {
  id: string;
  title: string;
  /** When its latest message was sent, as an ISO 8601 date. */
  updatedAt: string;
}

/**
 * Conversations from the same day in the user’s time zone, under a heading
 * for that day: “Today”, “Yesterday”, or the date.
 */
export interface AssistantConversationGroup {
  heading: string;
  conversations: AssistantConversation[];
}

/** Fetches the user’s conversations, latest first, grouped by day. */
export async function fetchConversations(): Promise<
  AssistantConversationGroup[]
> {
  const response = await actionFetch(
    getActionUrl(AssistantController.conversations.url())
  );

  return ((await response.json()) as {groups: AssistantConversationGroup[]})
    .groups;
}

/**
 * How long ago a date was, in its largest whole unit: short, such as “11 min”,
 * for display, and long, such as “11 minutes ago”, for assistive tech.
 */
export function formatAge(
  date: Date,
  now: Date = new Date()
): {short: string; long: string} {
  const seconds = Math.max(0, (now.getTime() - date.getTime()) / 1000);
  const [value, unit] = ageUnit(seconds);

  return {
    short: new Intl.NumberFormat(undefined, {
      style: 'unit',
      unit,
      unitDisplay: 'short',
    }).format(value),
    long: new Intl.RelativeTimeFormat(undefined, {numeric: 'auto'}).format(
      -value,
      unit
    ),
  };
}

function ageUnit(
  seconds: number
): [number, 'minute' | 'hour' | 'day' | 'week'] {
  const minutes = Math.floor(seconds / 60);
  const hours = Math.floor(minutes / 60);
  const days = Math.floor(hours / 24);

  if (days >= 7) {
    return [Math.floor(days / 7), 'week'];
  }

  if (days >= 1) {
    return [days, 'day'];
  }

  if (hours >= 1) {
    return [hours, 'hour'];
  }

  return [minutes, 'minute'];
}
