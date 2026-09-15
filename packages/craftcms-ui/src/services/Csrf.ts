import axios from 'axios';
import {ConfigService} from './Config.js';
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
        if (csrfTokenName && csrfTokenValue) {
          const config = ConfigService.getInstance();
          config.set('csrfTokenName', csrfTokenName);
          config.set('csrfTokenValue', csrfTokenValue);
          axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfTokenValue;

          const craft = (window as {Craft?: {csrfTokenValue?: string}}).Craft;
          if (craft) {
            craft.csrfTokenValue = csrfTokenValue;
          }

          document
            .querySelectorAll<HTMLInputElement>('input[type="hidden"]')
            .forEach((input) => {
              if (input.name === csrfTokenName) {
                input.value = input.defaultValue = csrfTokenValue;
              }
            });
        }
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
