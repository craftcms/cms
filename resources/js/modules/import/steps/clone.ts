import type {StepPayload} from '@/modules/import/mapping/types';

/**
 * A structural copy of one step, so a slideout can be cancelled without a trace.
 *
 * Round-tripped through JSON rather than `structuredClone()`, which can't clone the
 * reactive proxies the screens hold these in.
 */
export function cloneStep(step: StepPayload): StepPayload {
  return JSON.parse(JSON.stringify(step)) as StepPayload;
}

/** A structural copy of a list of steps, as {@link cloneStep} copies one. */
export function cloneSteps(steps: StepPayload[]): StepPayload[] {
  return JSON.parse(JSON.stringify(steps)) as StepPayload[];
}
