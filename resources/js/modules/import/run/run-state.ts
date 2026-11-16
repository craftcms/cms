import {actionClient} from '@craftcms/ui';
import {useEventListener} from '@vueuse/core';
import {onScopeDispose, shallowRef, watch, type ShallowRef} from 'vue';
import {runState as runStateAction} from '@actions/Import/ImportPlansController';

export type RunStatus = 'pending' | 'running' | 'successful' | 'failed';

/** What happened to an item in a run. */
export type ItemOutcome =
  | 'created'
  | 'updated'
  | 'unchanged'
  | 'skipped'
  | 'failed';

/** The record a run keeps of an item that failed. */
export interface RunFailure {
  /** The item's position in the step's data, from 1. */
  item: number;
  message: string;
  /** The element it would have saved, if it already existed. */
  elementId: number | null;
  /** Each validation error that kept it from saving, with where it is as labels. */
  problems: Array<{path: string[]; message: string}>;
  /** The item as the source had it. */
  data: unknown;
}

/** One step's share of a run. */
export interface StepRunState {
  label: string;
  status: RunStatus;
  /** How far through its items, from 0 to 100. */
  progress: number;
  /** How many items it has, once its source has been read. */
  total: number | null;
  source: {name: string | null; size: number | null};
  /** How many items came to each outcome. */
  counts: Record<ItemOutcome, number>;
  /** Its first failed items; the rest are only counted. Left out where only the status is needed. */
  failures?: RunFailure[];
}

/** One execution of a whole import plan, step run by step run. */
export interface RunState {
  id: string;
  status: RunStatus;
  /** How far the whole run has got, from 0 to 100. */
  progress: number;
  /** How it was started, if by a person: from the control panel or the command line. */
  trigger: 'cp' | 'cli' | null;
  /** The username of whoever started it. */
  user: string | null;
  dateCreated: string;
  dateStarted: string | null;
  dateFinished: string | null;
  /** By step uid; only the steps the run includes. */
  steps: Record<string, StepRunState>;
}

const POLL_INTERVAL = 2000;

/** The id of where a step shows how its last run went. */
export function stepRunId(stepUid: string): string {
  return `step-run-${stepUid}`;
}

/**
 * Follows an import plan's latest run while it's running, and checks for a new one
 * whenever the page comes back into view, as a run can be started from elsewhere.
 */
export function useRunState(
  handle: () => string | null,
  initial: RunState | null
): ShallowRef<RunState | null> {
  const state = shallowRef(initial);
  let timer: ReturnType<typeof setTimeout> | undefined;

  async function refresh(): Promise<void> {
    const planHandle = handle();

    if (!planHandle) {
      return;
    }

    try {
      const {data} = await actionClient.get<{runState: RunState | null}>(
        runStateAction.url(),
        {params: {handle: planHandle}}
      );
      state.value = data.runState;
    } catch {
      // the last known state stands until the next check
    }
  }

  function schedule(): void {
    clearTimeout(timer);

    if (
      state.value?.status === 'running' ||
      state.value?.status === 'pending'
    ) {
      timer = setTimeout(() => void refresh(), POLL_INTERVAL);
    }
  }

  watch(state, schedule, {immediate: true});
  useEventListener(document, 'visibilitychange', () => {
    if (document.visibilityState === 'visible') {
      void refresh();
    }
  });
  onScopeDispose(() => clearTimeout(timer));

  return state;
}
