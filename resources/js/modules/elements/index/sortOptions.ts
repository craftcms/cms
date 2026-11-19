import {t} from '@craftcms/ui';
import type {SortOption} from '@/modules/elements/types/view-state';

/** The sort options worth offering: Score only while it can apply. */
export function visibleSortOptions(
  options: SortOption[],
  search: string,
  sortField: string
): SortOption[] {
  const rest = options.filter((option) => option.value !== 'score');

  return search || sortField === 'score'
    ? [
        {label: t('Score'), value: 'score', defaultDir: 'desc' as const},
        ...rest,
      ]
    : rest;
}

/** Whether a sort has an intrinsic direction. */
export function isSortDirectionLocked(sortField: string): boolean {
  return ['score', 'sortOrder'].includes(sortField);
}
