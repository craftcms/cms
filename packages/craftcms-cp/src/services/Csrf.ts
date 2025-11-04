import {actionClient} from '../utilities/api/actionClient.js';

interface SessionInfoResponseData {
  isGuest: boolean;
  timeout: number;
  // CSRF data if enabled
  csrfTokenName?: string;
  csrfTokenValue?: string;
  // User data if available
  id?: number;
  uid?: string;
  username?: string;
  email?: string;
}

export class Csrf {
  tokenName: string | null;
  tokenValue: string | null;
  refreshPromise: Promise<string | null> | null = null;

  constructor() {
    this.tokenName = null;
    this.tokenValue = null;
    this.refreshPromise = null;
  }

  async getToken() {
    if (!this.tokenValue) {
      await this.refreshToken();
    }

    return this.tokenValue;
  }

  async refreshToken() {
    if (this.refreshPromise) {
      return this.refreshPromise;
    }

    this.refreshPromise = actionClient
      .get<SessionInfoResponseData>('users/session-info')
      .then(({data}) => {
        const {csrfTokenName, csrfTokenValue} = data;
        this.tokenName = csrfTokenName ?? null;
        this.tokenValue = csrfTokenValue ?? null;
        return this.tokenValue;
      })
      .finally(() => {
        this.refreshPromise = null;
      });

    return this.refreshPromise;
  }

  clearToken() {
    this.tokenValue = null;
  }
}
