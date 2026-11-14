type Phase = 'booting' | 'booted';

const queues: Record<Phase, Array<() => void>> = {booting: [], booted: []};
const reached: Record<Phase, boolean> = {booting: false, booted: false};

function on(phase: Phase, callback: () => void): void {
  if (reached[phase]) {
    callback();
    return;
  }

  queues[phase].push(callback);
}

/**
 * Runs `callback` before the CP's Inertia app mounts, or straight away if it
 * already has.
 */
export function onBooting(callback: () => void): void {
  on('booting', callback);
}

/**
 * Runs `callback` once the CP's Inertia app has mounted and its first page is
 * showing, or straight away if it already is.
 */
export function onBooted(callback: () => void): void {
  on('booted', callback);
}

/** @internal Called by the CP's boot. */
export function runLifecyclePhase(phase: Phase): void {
  reached[phase] = true;
  const callbacks = queues[phase];
  queues[phase] = [];
  callbacks.forEach((callback) => callback());
}
