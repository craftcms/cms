import {effectScope, nextTick} from 'vue';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';

const router = vi.hoisted(() => {
  const listeners = new Map<string, Set<() => void>>();

  return {
    on: vi.fn((event: string, handler: () => void) => {
      const handlers = listeners.get(event) ?? new Set<() => void>();

      handlers.add(handler);
      listeners.set(event, handlers);

      return () => handlers.delete(handler);
    }),
    fire: (event: string) =>
      listeners.get(event)?.forEach((handler) => handler()),
    count: () =>
      [...listeners.values()].reduce(
        (total, handlers) => total + handlers.size,
        0
      ),
    listeners,
  };
});

vi.mock('@inertiajs/vue3', () => ({router}));

const {useFieldHighlight} = await import('./useFieldHighlight');

/**
 * Points the URL at a fragment without firing `hashchange`.
 *
 * Assigning `location.hash` dispatches the event asynchronously, which would
 * re-run the composable at an unpredictable moment mid-test. The event has its
 * own test below, dispatched by hand.
 */
function setHash(hash: string): void {
  window.history.replaceState(
    null,
    '',
    hash === '' ? window.location.pathname : hash
  );
}

/** A field the way `FieldNode` renders one: the id on the host, not the input. */
function renderField(id: string): HTMLElement {
  const field = document.createElement('craft-field');
  field.id = id;
  field.append(document.createElement('input'));
  document.body.append(field);

  return field;
}

describe('useFieldHighlight', () => {
  let scope: ReturnType<typeof effectScope>;

  beforeEach(() => {
    vi.useFakeTimers();
    router.listeners.clear();
    document.body.innerHTML = '';
    setHash('');
    scope = effectScope();
  });

  afterEach(() => {
    scope.stop();
    vi.useRealTimers();
  });

  it('highlights the field a fragment names, then lets go', async () => {
    const field = renderField('form-maintenanceMode');
    setHash('#form-maintenanceMode');

    scope.run(() => useFieldHighlight());
    await nextTick();

    expect(field.hasAttribute('data-highlighted')).toBe(true);

    // Well past the timeout, whatever it's currently tuned to — the point is
    // that the highlight lets go on its own, not how long it lingers.
    vi.advanceTimersByTime(60_000);

    expect(field.hasAttribute('data-highlighted')).toBe(false);
  });

  it('highlights the field a control sits inside', async () => {
    const field = renderField('form-maintenanceMode');
    // Old links may point at the input rather than the field around it.
    field.querySelector('input')!.id = 'form-maintenanceMode-input';
    setHash('#form-maintenanceMode-input');

    scope.run(() => useFieldHighlight());
    await nextTick();

    expect(field.hasAttribute('data-highlighted')).toBe(true);
  });

  it('waits for the page an Inertia visit is still swapping in', async () => {
    setHash('#form-maintenanceMode');

    scope.run(() => useFieldHighlight());
    await nextTick();

    // Nothing to highlight yet: the visit settles before Vue patches the DOM.
    const field = renderField('form-maintenanceMode');

    router.fire('navigate');
    await nextTick();

    expect(field.hasAttribute('data-highlighted')).toBe(true);
  });

  it('follows a link to the page already on screen', async () => {
    const field = renderField('form-maintenanceMode');

    scope.run(() => useFieldHighlight());
    await nextTick();

    // Inertia replaces the history entry rather than pushing one when a visit
    // only adds a fragment to the current URL, and skips `navigate` when it
    // does — which is every click of the maintenance-mode badge from Settings.
    setHash('#form-maintenanceMode');
    router.fire('success');
    await nextTick();

    expect(field.hasAttribute('data-highlighted')).toBe(true);
  });

  it('leaves the page alone when the fragment names something else', async () => {
    const main = document.createElement('main');
    main.id = 'main';
    document.body.append(main);
    setHash('#main');

    scope.run(() => useFieldHighlight());
    await nextTick();

    expect(document.querySelector('[data-highlighted]')).toBeNull();
  });

  it('follows a fragment change on a page that never reloads', async () => {
    const field = renderField('form-maintenanceMode');

    scope.run(() => useFieldHighlight());
    await nextTick();

    expect(field.hasAttribute('data-highlighted')).toBe(false);

    setHash('#form-maintenanceMode');
    window.dispatchEvent(new Event('hashchange'));
    await nextTick();

    expect(field.hasAttribute('data-highlighted')).toBe(true);
  });

  it('stops listening once its scope is gone', async () => {
    const field = renderField('form-maintenanceMode');
    setHash('#form-maintenanceMode');

    scope.run(() => useFieldHighlight());
    await nextTick();
    scope.stop();

    expect(field.hasAttribute('data-highlighted')).toBe(false);
    expect(router.count()).toBe(0);
  });
});
