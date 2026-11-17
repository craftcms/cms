import {actionClient} from '@craftcms/ui';
import {reactive, readonly, type DeepReadonly} from 'vue';

/** The response header carrying the session's expiry, as a Unix time. */
export const EXPIRES_HEADER = 'x-craft-session-expires';

/** How long before the session expires to warn about it, in seconds. */
export const WARNING_SECONDS = 120;

/** How long to wait before checking again when a check fails, in seconds. */
const RETRY_SECONDS = 60;

export type SessionPhase = 'active' | 'warning' | 'expired';

export interface SessionTimeoutState {
  /** When the session expires, as a Unix time, or `null` if it won't. */
  expiresAt: number | null;
  phase: SessionPhase;
}

export interface SessionInfo {
  isGuest: boolean;
  sessionExpiresAt: number | null;
}

type SessionMessage =
  | {type: 'expires'; expiresAt: number | null}
  | {type: 'signedIn'};

export interface SessionTimeoutDependencies {
  /** Reads the session's state without extending it. */
  fetchInfo(): Promise<SessionInfo>;
  /** Extends the session, resolving to its new expiry. */
  renew(): Promise<number | null>;
  /** The current time, in milliseconds. */
  now(): number;
  /** Shares expiry changes with the control panel's other tabs. */
  channel: Pick<BroadcastChannel, 'postMessage' | 'addEventListener'> | null;
}

function createChannel(): SessionTimeoutDependencies['channel'] {
  return typeof BroadcastChannel === 'undefined'
    ? null
    : new BroadcastChannel('craft:session');
}

const defaultDependencies = (): SessionTimeoutDependencies => ({
  async fetchInfo() {
    const {data} = await actionClient.get<SessionInfo>('users/session-info');
    return data;
  },
  async renew() {
    const {data} = await actionClient.post<{sessionExpiresAt: number | null}>(
      'users/renew-session'
    );
    return data.sessionExpiresAt;
  },
  now: () => Date.now(),
  channel: createChannel(),
});

/**
 * Reads the session's expiry from response headers.
 */
export function expiryFromHeaders(
  headers: Record<string, unknown> | undefined
): number | null | undefined {
  if (!headers) {
    return undefined;
  }

  const name = Object.keys(headers).find(
    (key) => key.toLowerCase() === EXPIRES_HEADER
  );
  const value = name === undefined ? undefined : Number(headers[name]);

  return value === undefined || Number.isNaN(value) ? undefined : value;
}

/**
 * Keeps track of when the signed-in user's session will expire, so they can be
 * warned beforehand and signed back in once it has.
 *
 * The expiry comes from the page, and from the `X-Craft-Session-Expires`
 * header on every response that extended the session. A couple of minutes
 * before it, the manager checks with `users/session-info`, which doesn't
 * extend the session, in case another tab has kept it alive. If it hasn't, the
 * phase moves to `warning`, and once the session is gone, to `expired`.
 */
export class SessionTimeoutManager {
  readonly state: DeepReadonly<SessionTimeoutState>;

  private readonly mutableState = reactive<SessionTimeoutState>({
    expiresAt: null,
    phase: 'active',
  });
  private timer: ReturnType<typeof setTimeout> | null = null;
  private checking: Promise<void> | null = null;

  constructor(
    private readonly deps: SessionTimeoutDependencies = defaultDependencies()
  ) {
    this.state = readonly(this.mutableState);
    deps.channel?.addEventListener('message', (event) => {
      this.receive((event as MessageEvent<SessionMessage>).data);
    });
  }

  /** Starts tracking from the expiry the page was rendered with. */
  start(expiresAt: number | null): void {
    this.apply(expiresAt);
  }

  /** Records a new expiry, as reported by a response. */
  update(expiresAt: number | null): void {
    if (expiresAt === this.mutableState.expiresAt) {
      return;
    }

    this.apply(expiresAt);
    this.deps.channel?.postMessage({type: 'expires', expiresAt});
  }

  /** Checks the session's state, without extending it. */
  check(): Promise<void> {
    this.checking ??= this.runCheck().finally(() => {
      this.checking = null;
    });

    return this.checking;
  }

  /** Extends the session, as “Keep me signed in” does. */
  async renew(): Promise<void> {
    this.update(await this.deps.renew());
  }

  /** Records that the user has signed back in. */
  async signedIn(): Promise<void> {
    this.mutableState.phase = 'active';
    this.deps.channel?.postMessage({type: 'signedIn'});
    await this.check();
  }

  /** Stops tracking. */
  stop(): void {
    this.clearTimer();
  }

  private async runCheck(): Promise<void> {
    let info: SessionInfo;

    try {
      info = await this.deps.fetchInfo();
    } catch {
      this.schedule(this.deps.now() + RETRY_SECONDS * 1000);
      return;
    }

    // A session that was never tracked, such as a remembered one, isn't
    // reported as expired.
    if (info.isGuest) {
      if (this.mutableState.expiresAt !== null) {
        this.clearTimer();
        this.mutableState.phase = 'expired';
      }
      return;
    }

    this.mutableState.expiresAt = info.sessionExpiresAt;
    this.reschedule(true);
  }

  private apply(expiresAt: number | null): void {
    this.mutableState.expiresAt = expiresAt;

    if (this.mutableState.phase !== 'expired') {
      this.mutableState.phase = 'active';
    }

    this.reschedule(false);
  }

  /**
   * Schedules the next check: a couple of minutes before the expiry, or, once
   * a check has confirmed it's that close, at the expiry itself.
   */
  private reschedule(confirmed: boolean): void {
    const {expiresAt} = this.mutableState;

    if (expiresAt === null || this.mutableState.phase === 'expired') {
      this.clearTimer();
      this.mutableState.phase =
        this.mutableState.phase === 'expired' ? 'expired' : 'active';
      return;
    }

    const expiresAtMs = expiresAt * 1000;
    const warnAtMs = expiresAtMs - WARNING_SECONDS * 1000;

    if (confirmed && this.deps.now() >= warnAtMs) {
      this.mutableState.phase = 'warning';
      this.schedule(expiresAtMs);
    } else {
      this.mutableState.phase = 'active';
      this.schedule(warnAtMs);
    }
  }

  private schedule(atMs: number): void {
    this.clearTimer();
    this.timer = setTimeout(
      () => void this.check(),
      Math.max(0, atMs - this.deps.now())
    );
  }

  private clearTimer(): void {
    if (this.timer !== null) {
      clearTimeout(this.timer);
      this.timer = null;
    }
  }

  private receive(message: SessionMessage): void {
    if (message.type === 'expires') {
      this.apply(message.expiresAt);
    } else if (message.type === 'signedIn') {
      this.mutableState.phase = 'active';
      void this.check();
    }
  }
}

export const sessionTimeoutManager = new SessionTimeoutManager();

/**
 * Notes the session's new expiry from a response's headers. Requests sent
 * without the clients `installSessionTimeout()` watches, such as streamed
 * `fetch()` ones, pass theirs here.
 */
export function noteHeaders(
  headers: Record<string, unknown> | undefined
): void {
  const expiresAt = expiryFromHeaders(headers);

  if (expiresAt !== undefined) {
    sessionTimeoutManager.update(expiresAt);
  }
}

/**
 * Checks the session after a 401 response, in case it has expired.
 */
export function noteStatus(status: number | undefined): void {
  if (status === 401) {
    void sessionTimeoutManager.check();
  }
}
