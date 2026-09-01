import {actionClient} from '@craftcms/ui';
import {reactive, readonly, type DeepReadonly} from 'vue';

export interface ElevatedSessionOptions {
  minimumRemainingSeconds?: number;
  force?: boolean;
}

export interface ElevatedSessionResponse {
  confirmed: boolean;
  timeout: number | false;
  loginName?: string;
  alternativeLoginMethods?: CraftCms.Cms.View.HtmlFragment | null;
}

export interface ElevatedSessionState {
  active: boolean;
  checking: boolean;
  loginName: string;
  alternativeLoginMethods: CraftCms.Cms.View.HtmlFragment | null;
}

export type ElevatedSessionRequest = (
  options: Required<ElevatedSessionOptions>
) => Promise<ElevatedSessionResponse>;

const DEFAULT_MINIMUM_REMAINING_SECONDS = 5;

async function requestConfirmation(
  options: Required<ElevatedSessionOptions>
): Promise<ElevatedSessionResponse> {
  const {data} = await actionClient.post<ElevatedSessionResponse>(
    'users/confirm-password',
    options
  );

  return data;
}

export class ElevatedSessionManager {
  readonly state: DeepReadonly<ElevatedSessionState>;

  private readonly mutableState = reactive<ElevatedSessionState>({
    active: false,
    checking: false,
    loginName: '',
    alternativeLoginMethods: null,
  });
  private pendingMinimum = DEFAULT_MINIMUM_REMAINING_SECONDS;
  private pendingForce = false;
  private pendingConfirmation: Promise<boolean> | null = null;
  private finishPrompt: ((confirmed: boolean) => void) | null = null;

  constructor(
    private readonly request: ElevatedSessionRequest = requestConfirmation
  ) {
    this.state = readonly(this.mutableState);
  }

  require(options: ElevatedSessionOptions = {}): Promise<boolean> {
    this.pendingMinimum = Math.max(
      this.pendingMinimum,
      options.minimumRemainingSeconds ?? DEFAULT_MINIMUM_REMAINING_SECONDS
    );
    this.pendingForce ||= options.force ?? false;

    this.pendingConfirmation ??= Promise.resolve()
      .then(() => this.checkConfirmation())
      .finally(() => {
        this.pendingConfirmation = null;
        this.pendingMinimum = DEFAULT_MINIMUM_REMAINING_SECONDS;
        this.pendingForce = false;
      });

    return this.pendingConfirmation;
  }

  async run<T>(
    callback: () => T | Promise<T>,
    options: ElevatedSessionOptions = {}
  ): Promise<T | undefined> {
    if (!(await this.require(options))) {
      return undefined;
    }

    try {
      return await callback();
    } catch (error) {
      if (
        !(error instanceof Object) ||
        !('response' in error) ||
        !(error.response instanceof Object) ||
        !('status' in error.response) ||
        error.response.status !== 423
      ) {
        throw error;
      }

      if (!(await this.require({...options, force: true}))) {
        return undefined;
      }

      return callback();
    }
  }

  confirm(): void {
    this.finish(true);
  }

  cancel(): void {
    this.finish(false);
  }

  private async checkConfirmation(): Promise<boolean> {
    this.mutableState.checking = true;

    try {
      const requestedMinimum = this.pendingMinimum;
      const requestedForce = this.pendingForce;
      const response = await this.request({
        minimumRemainingSeconds: requestedMinimum,
        force: requestedForce,
      });

      if (
        response.confirmed &&
        requestedForce === this.pendingForce &&
        (response.timeout === false || response.timeout >= this.pendingMinimum)
      ) {
        return true;
      }

      if (response.confirmed) {
        return await this.checkConfirmation();
      }

      this.mutableState.loginName = response.loginName ?? '';
      this.mutableState.alternativeLoginMethods =
        response.alternativeLoginMethods ?? null;

      return new Promise<boolean>((resolve) => {
        this.finishPrompt = resolve;
        this.mutableState.active = true;
      });
    } finally {
      this.mutableState.checking = false;
    }
  }

  private finish(confirmed: boolean): void {
    if (!this.finishPrompt) {
      return;
    }

    const resolve = this.finishPrompt;
    this.finishPrompt = null;
    this.mutableState.active = false;
    resolve(confirmed);
  }
}

export const elevatedSessionManager = new ElevatedSessionManager();
