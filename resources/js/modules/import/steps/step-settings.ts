import {t} from '@craftcms/ui';
import {hasMapping} from '@/modules/import/mapping/paths';
import {matchModeLabel} from '@/modules/import/mapping/matching';
import type {ImportStep} from '@/modules/import/mapping/types';
import type {StepSummary} from './step-slideout';

/** A step's settings as the server summed them up, plus what it does with items that match. */
export function stepSettingRows(
  step: ImportStep | null | undefined,
  summary: StepSummary | null | undefined
): Array<{label: string; value: string}> {
  const settings =
    !step || Array.isArray(step.settings) ? {} : (step.settings ?? {});
  const matching = Object.values(
    (settings.matchCriteria ?? {}) as Record<string, unknown>
  ).some((value) => hasMapping(value));
  const mode = settings.matchMode as string | null | undefined;

  return [
    ...(summary?.settings ?? []),
    ...(matching && mode && matchModeLabel(mode)
      ? [{label: t('If matched'), value: matchModeLabel(mode)!}]
      : []),
  ];
}

/**
 * Where a step's items go, as the list of plans names it beside the step's type: its
 * settings but the site, e.g. “Guides › Guide”. A summed-up setting carries its key.
 */
export function stepDestination(
  settings: ReadonlyArray<{key?: string; value: string}>
): string {
  return settings
    .filter((setting) => setting.key !== 'site')
    .map((setting) => setting.value)
    .join(' › ');
}
