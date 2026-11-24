/** Reads layout or computed styles and returns the DOM writes that depend on them. */
export type Measure = () => (() => void) | void;

const queued = new Set<Measure>();
let frame = 0;

/**
 * Runs `measure` in the next animation frame, together with every other
 * measure queued for it: all of their reads first, then all of the writes they
 * return. A page full of components then costs one style recalculation per
 * frame, rather than one for each component that reads styles while the
 * others are still changing the DOM.
 *
 * Queuing the same function again before the frame runs it once, so pass a
 * function the component keeps, such as an arrow function property.
 */
export function scheduleMeasure(measure: Measure): void {
  queued.add(measure);
  frame ||= requestAnimationFrame(flush);
}

function flush(): void {
  frame = 0;
  const measures = Array.from(queued);
  queued.clear();
  const writes = measures.map((measure) => measure());
  for (const write of writes) {
    write?.();
  }
}
