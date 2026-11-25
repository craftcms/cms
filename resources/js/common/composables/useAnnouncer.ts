import {announce, announcement, type AnnounceOptions} from '@/common/announcer';

/**
 * Announces messages to screen readers, in the topmost modal layer's live
 * region when one is open.
 *
 * The announcement state is shared globally, so only one announcement can be active at a time.
 * New announcements will replace any existing ones.
 *
 * @example
 * const {announce} = useAnnouncer();
 * announce('Changes saved successfully');
 *
 * @example
 * // With custom timeout (3 seconds)
 * const {announce} = useAnnouncer({timeout: 3000});
 * announce('Quick notification');
 */
export function useAnnouncer(options: Partial<AnnounceOptions> = {}) {
  return {
    announcement,
    announce: (message: string | null) => announce(message, options),
  };
}
