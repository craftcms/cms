import {formatDuration, t} from '@craftcms/ui';
import type {ItemOutcome, RunState, RunStatus} from './run-state';

export const statusLabels: Record<RunStatus, () => string> = {
  pending: () => t('Pending'),
  running: () => t('Running'),
  successful: () => t('Successful'),
  failed: () => t('Failed'),
};

export const outcomeLabels: Record<ItemOutcome, () => string> = {
  created: () => t('Created'),
  updated: () => t('Updated'),
  unchanged: () => t('Unchanged'),
  skipped: () => t('Skipped'),
  failed: () => t('Failed'),
};

/** How long a run took, or has taken so far; `null` before it starts. */
export function runDuration(
  run: Pick<RunState, 'dateStarted' | 'dateFinished'>,
  now = Date.now()
): string | null {
  if (!run.dateStarted) {
    return null;
  }

  const end = run.dateFinished ? Date.parse(run.dateFinished) : now;

  return formatDuration((end - Date.parse(run.dateStarted)) / 1000);
}

/** The run's item counts, added up across its step runs. */
export function runCounts(run: RunState): Record<ItemOutcome, number> {
  const totals: Record<ItemOutcome, number> = {
    created: 0,
    updated: 0,
    unchanged: 0,
    skipped: 0,
    failed: 0,
  };

  for (const step of Object.values(run.steps)) {
    for (const outcome of Object.keys(totals) as ItemOutcome[]) {
      totals[outcome] += step.counts[outcome] ?? 0;
    }
  }

  return totals;
}

/** Who started a run, and from where. */
export function runStartedBy(run: Pick<RunState, 'trigger' | 'user'>): string {
  const how =
    run.trigger === 'cp'
      ? t('Control panel')
      : run.trigger === 'cli'
        ? t('Command line')
        : null;

  if (run.user && how) {
    return t('{user} via {how}', {user: run.user, how});
  }

  return run.user ?? how ?? '—';
}
