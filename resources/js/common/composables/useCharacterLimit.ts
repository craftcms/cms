import {t} from '@craftcms/ui';
import {computed, type ComputedRef, type Ref} from 'vue';

/**
 * The longest comment the server accepts, for activity comments and workflow
 * notes alike. Matches `ActivityCommentRequest::MaxLength`.
 */
export const COMMENT_MAX_LENGTH = 10_000;

/**
 * How far some text runs past a length the server accepts, and the message
 * saying so.
 *
 * Counted in code points, as the server's `mb_strlen` does, rather than the
 * UTF-16 units `String#length` counts, which would put an emoji at two.
 */
export function useCharacterLimit(
  text: Readonly<Ref<string>>,
  maxLength: number
): {
  overage: ComputedRef<number>;
  overageMessage: ComputedRef<string | null>;
} {
  const overage = computed(() =>
    Math.max(0, [...text.value].length - maxLength)
  );
  const overageMessage = computed(() =>
    overage.value > 0
      ? t(
          '{num, number} {num, plural, =1{character} other{characters}} over the limit',
          {num: overage.value}
        )
      : null
  );

  return {overage, overageMessage};
}
