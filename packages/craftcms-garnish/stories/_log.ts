/**
 * Shared event-logging helper for @craftcms/garnish stories.
 *
 * Backed by Storybook's built-in **Actions** panel (`storybook/actions`) rather
 * than a hand-rolled DOM panel: every logged event shows up in the "Actions" tab
 * under the addons panel, grouped by `tag`. Drag/menu/modal stories use it to
 * surface the events the real widgets fire (`show`, `hide`, `dragStart`,
 * `dropTargetChange`, …) as you interact.
 *
 * Usage in a story `render()`:
 *
 * ```ts
 * const log = createEventLog();
 * const main = document.createElement('div');
 * // …build the demo, call log.log('modal', 'show') from event handlers…
 * return storyLayout(main);
 * ```
 */

import {action} from 'storybook/actions';

/** A subscribable lifecycle object (Garnish `Base` / draggers expose `on`). */
export interface Subscribable {
  on(events: string, handler: () => void): void;
}

export interface EventLog {
  /**
   * Log an event to the Actions panel under `tag` with a human-readable message.
   */
  log(tag: string, message: string): void;
}

// Memoize one Storybook action per tag so the panel groups events by tag.
const actions = new Map<string, ReturnType<typeof action>>();
function actionFor(tag: string): ReturnType<typeof action> {
  let fn = actions.get(tag);
  if (!fn) {
    fn = action(tag);
    actions.set(tag, fn);
  }
  return fn;
}

/** Create a logger that writes events into Storybook's Actions panel. */
export function createEventLog(): EventLog {
  return {
    log: (tag, message) => actionFor(tag)(message),
  };
}

/**
 * Wire a dragger's lifecycle into the log. `drag` fires once per RAF frame, so
 * it is coalesced to a single "drag (moving…)" line per gesture to keep the
 * Actions panel readable; `dragStart` / `dragStop` always log.
 */
export function wireDragEvents(
  log: EventLog,
  dragger: Subscribable,
  tag: string,
  label: string
): void {
  let dragging = false;
  dragger.on('dragStart', () => {
    dragging = false;
    log.log(tag, `${label}: dragStart`);
  });
  dragger.on('drag', () => {
    if (!dragging) {
      dragging = true;
      log.log(tag, `${label}: drag (moving…)`);
    }
  });
  dragger.on('dragStop', () => log.log(tag, `${label}: dragStop`));
}

/**
 * Wrap a story's demo column in the `.pg-story` container the global styles
 * expect. Events surface in Storybook's Actions panel, so there is no in-canvas
 * log panel to mount.
 */
export function storyLayout(main: HTMLElement): HTMLElement {
  main.classList.add('pg-story');
  return main;
}
