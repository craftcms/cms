/**
 * Shows how each running import is going in a notification, on whichever page
 * the user is on: from the page loads that report a run going, and from the
 * "Import started." message of a run started in the background.
 */
import '@craftcms/ui/components/progress-bar/progress-bar';
import {t} from '@craftcms/ui';
import {router} from '@inertiajs/vue3';
import {html, render} from 'lit';
import {effectScope, watch, type EffectScope, type ShallowRef} from 'vue';
import {
  registerMessageOutlet,
  showError,
  showPersistentNotice,
  showSuccess,
  type CpMessage,
} from '@/modules/messages/messages';
import {useRunState, type RunState} from './run-state';

/** A plan with a run going, as every page is told about it. */
export interface RunningImport {
  handle: string;
  name: string;
  run: RunState | null;
}

/** Where the server sends the message for a run started in the background. */
const OUTLET = 'import-runs';
const DISMISSED_KEY = 'Craft-import-runs.dismissed';

interface Followed {
  name: string;
  scope: EffectScope;
  state: ShallowRef<RunState | null>;
  details: HTMLElement;
  close: (() => void) | null;
  /** Set while the notification is closed by code, rather than by the user. */
  closing: boolean;
}

const followed = new Map<string, Followed>();
/** Runs already seen to the end, so a page's stale props don't start them over. */
const finished = new Set<string>();

function dismissed(): string[] {
  try {
    return JSON.parse(sessionStorage.getItem(DISMISSED_KEY) ?? '[]');
  } catch {
    return [];
  }
}

function dismiss(runId: string): void {
  try {
    sessionStorage.setItem(
      DISMISSED_KEY,
      JSON.stringify([...dismissed(), runId].slice(-50))
    );
  } catch {
    // it shows again on the next page, which is no worse than before
  }
}

function isOnPage(url: string): boolean {
  return new URL(url, location.href).pathname === location.pathname;
}

function runLink(run: RunState) {
  return html`<a
    href=${run.url}
    @click=${(event: MouseEvent) => {
      if (
        event.button ||
        event.ctrlKey ||
        event.metaKey ||
        event.shiftKey ||
        event.altKey
      ) {
        return;
      }
      event.preventDefault();
      router.visit(run.url);
    }}
    >${t('View run')}</a
  >`;
}

function renderProgress(entry: Followed, run: RunState): void {
  const pending = run.status === 'pending';
  const percent = new Intl.NumberFormat(
    document.documentElement.lang || undefined,
    {style: 'percent'}
  ).format(run.progress / 100);

  render(
    html`<div class="flex flex-col items-start gap-2">
      <craft-progress-bar
        class="w-full"
        .progress=${run.progress}
        ?pending=${pending}
        smooth
        label=${t('Progress')}
      ></craft-progress-bar>
      <div class="flex flex-wrap items-center gap-2">
        <span>${pending ? t('Waiting to run') : percent}</span>
        ${runLink(run)}
      </div>
    </div>`,
    entry.details
  );
}

function closeNotice(entry: Followed): void {
  if (!entry.close) {
    return;
  }

  entry.closing = true;
  entry.close();
  entry.close = null;
  entry.closing = false;
}

function stop(handle: string): void {
  const entry = followed.get(handle);

  if (entry) {
    closeNotice(entry);
    entry.scope.stop();
    followed.delete(handle);
  }
}

/** Brings a plan's notification in line with its run and the page the user is on. */
function update(handle: string): void {
  const entry = followed.get(handle);
  const run = entry?.state.value;

  if (!entry || !run) {
    return;
  }

  if (finished.has(run.id) || dismissed().includes(run.id)) {
    stop(handle);
    return;
  }

  if (run.status === 'successful' || run.status === 'failed') {
    finished.add(run.id);
    stop(handle);

    if (!isOnPage(run.url)) {
      const details = document.createElement('div');
      render(runLink(run), details);
      const show = run.status === 'successful' ? showSuccess : showError;
      show(
        run.status === 'successful'
          ? t('“{name}” finished importing.', {name: entry.name})
          : t('“{name}” finished importing, with problems.', {
              name: entry.name,
            }),
        {details}
      );
    }
    return;
  }

  // its own page already shows how it's going
  if (isOnPage(run.url)) {
    closeNotice(entry);
    return;
  }

  renderProgress(entry, run);

  if (!entry.close) {
    entry.close = showPersistentNotice(
      t('Importing “{name}” data', {name: entry.name}),
      entry.details,
      () => {
        if (!entry.closing) {
          dismiss(run.id);
          entry.close = null;
          stop(handle);
        }
      }
    );
  }
}

function follow(
  {handle, name}: {handle: string; name: string},
  run: RunState | null
): void {
  if (followed.has(handle) || (run && finished.has(run.id))) {
    return;
  }

  const scope = effectScope(true);
  const state = scope.run(() =>
    useRunState(() => handle, run, {immediate: !run})
  )!;
  const details = document.createElement('div');
  details.tabIndex = -1;
  followed.set(handle, {
    name,
    scope,
    state,
    details,
    close: null,
    closing: false,
  });
  scope.run(() => watch(state, () => update(handle), {immediate: true}));
}

/** Starts following the runs a page reports, and shows or hides each run's notification for the page. */
function handlePage(page: {props: Record<string, unknown>}): void {
  const running = page.props.runningImports as RunningImport[] | undefined;
  running?.forEach((plan) => follow(plan, plan.run));
  [...followed.keys()].forEach(update);
}

export function setUpImportRunNotifications(): void {
  registerMessageOutlet(OUTLET, (message: CpMessage) => {
    const plan = message.settings?.importPlan as
      | {handle: string; name: string}
      | undefined;

    if (plan) {
      follow(plan, null);
    } else {
      showSuccess(message.message);
    }
  });

  router.on('success', (event) => handlePage(event.detail.page));
  router.on('navigate', (event) => handlePage(event.detail.page));
}
