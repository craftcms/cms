import {actionClient} from '@craftcms/ui';
import {useEventListener} from '@vueuse/core';
import {onScopeDispose, shallowRef, watch, type ShallowRef} from 'vue';
import {runState as runStateAction} from '@actions/Import/ImportPlansController';

export type RunStatus = 'pending' | 'running' | 'completed' | 'failed';

/** Why an item of a step couldn't be imported. */
export interface RunItemError {
  /** The item's position in the step's data, from 1. */
  item: number;
  message: string;
  /** The element it would have saved, if it already existed. */
  elementId: number | null;
  /** Each validation error that kept it from saving, with where it is as labels. */
  problems: Array<{path: string[]; message: string}>;
}

export interface StepRunState {
  status: RunStatus;
  /** How far through its items, from 0 to 100. */
  progress: number;
  /** Why its first few failed items couldn't be imported. */
  errors: RunItemError[];
  /** How many of its items failed, including those past `errors`. */
  errorCount: number;
}

/** Where an import plan's latest run has got, overall and step by step. */
export interface RunState {
  status: RunStatus;
  /** How far the whole run has got, from 0 to 100. */
  progress: number;
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
