/**
 * Shared event-log helper for @craftcms/garnish stories.
 *
 * Mirrors the old playground's bottom-right "Event log" panel, but mounts the
 * panel INSIDE a story so it scopes to that story's canvas. Drag/menu/modal
 * stories use it to surface the events the real widgets fire (`show`, `hide`,
 * `dragStart`, `dropTargetChange`, …) as you interact.
 *
 * Usage in a story `render()`:
 *
 * ```ts
 * const log = createEventLog();
 * const main = document.createElement('div');
 * // …build the demo, call log.log('modal', 'show') from event handlers…
 * return storyLayout(main, log);
 * ```
 */

/** A subscribable lifecycle object (Garnish `Base` / draggers expose `on`). */
export interface Subscribable {
  on(events: string, handler: () => void): void;
}

export interface EventLog {
  /** The panel element to mount in the story (via {@link storyLayout}). */
  readonly panel: HTMLElement;
  /** Append a line. `tag` is the green `[tag]` prefix; set `isError` to flag it red. */
  log(tag: string, message: string, isError?: boolean): void;
  /** Clear all lines. */
  clear(): void;
}

/** Build an event-log panel + its `log()`/`clear()` API. */
export function createEventLog(initialMessage?: string): EventLog {
  const panel = document.createElement('aside');
  panel.className = 'pg-log';
  panel.innerHTML = `
    <div class="pg-log-head">
      <strong>Event log</strong>
      <button type="button" data-log-clear>clear</button>
    </div>
    <ol class="pg-log-list"></ol>`;

  const list = panel.querySelector<HTMLOListElement>('.pg-log-list')!;

  const log: EventLog['log'] = (tag, message, isError = false) => {
    const li = document.createElement('li');
    if (isError) {
      li.className = 'pg-log-error';
    }

    const time = document.createElement('time');
    time.textContent = new Date().toLocaleTimeString(undefined, {
      hour12: false,
    });

    const tagEl = document.createElement('span');
    tagEl.className = 'pg-log-tag';
    tagEl.textContent = `[${tag}] `;

    li.append(time, tagEl, document.createTextNode(message));
    list.appendChild(li);
    list.scrollTop = list.scrollHeight;
  };

  const clear = (): void => {
    list.innerHTML = '';
  };

  panel
    .querySelector<HTMLButtonElement>('[data-log-clear]')!
    .addEventListener('click', clear);

  if (initialMessage) {
    log('ready', initialMessage);
  }

  return {panel, log, clear};
}

/**
 * Wire a dragger's lifecycle into the log. `drag` fires once per RAF frame, so
 * it is coalesced to a single "drag (moving…)" line per gesture to keep the log
 * readable; `dragStart` / `dragStop` always log.
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
 * Compose a story's demo column and (optionally) its event-log panel into the
 * `.pg-story` flex layout the global styles expect.
 */
export function storyLayout(main: HTMLElement, log?: EventLog): HTMLElement {
  const wrapper = document.createElement('div');
  wrapper.className = 'pg-story';

  if (!main.classList.contains('pg-story-main')) {
    main.classList.add('pg-story-main');
  }
  wrapper.appendChild(main);

  if (log) {
    wrapper.appendChild(log.panel);
  }
  return wrapper;
}
