import {actionClient, apiClient, ConfigService} from '@craftcms/ui';
import type {HttpClient} from '@craftcms/ui/utilities/api/http';
import {http, router} from '@inertiajs/vue3';
import {createApp} from 'vue';
import SessionTimeoutHost from './SessionTimeoutHost.vue';
import {noteHeaders, noteStatus, sessionTimeoutManager} from './manager';

let installed = false;

function watchClient(client: HttpClient): void {
  client.interceptors.response.use(
    (response) => {
      noteHeaders(response.headers);
      return response;
    },
    (error) => {
      noteStatus(error?.response?.status);
      return Promise.reject(error);
    }
  );
}

/**
 * Starts tracking the session's expiry: from the page's config, then from the
 * `X-Craft-Session-Expires` header on responses to Inertia, the action and API
 * clients, and the legacy `Craft.sendActionRequest()`. A 401 response prompts
 * a check, in case the session has already expired.
 */
export function installSessionTimeout(): void {
  if (installed) {
    return;
  }

  installed = true;
  sessionTimeoutManager.start(
    ConfigService.getInstance().get('sessionExpiresAt', null)
  );

  watchClient(actionClient);
  watchClient(apiClient);

  // SAFETY: The legacy bundle owns `Craft._httpClient`, one of our HttpClients.
  const legacyClient = (window as any).Craft?._httpClient as
    | HttpClient
    | undefined;
  if (legacyClient?.interceptors) {
    watchClient(legacyClient);
  }

  http.onResponse((response) => {
    noteHeaders(response.headers);
    return response;
  });

  router.on('httpException', (event) => {
    noteStatus(event.detail.response.status);
  });
}

/**
 * Mounts the session prompts on pages without the Inertia shell, which hosts
 * them itself.
 */
export function mountSessionTimeoutHost(): void {
  if (document.querySelector('[data-session-timeout-host]')) {
    return;
  }

  const host = document.createElement('div');
  host.dataset.sessionTimeoutHost = '';
  document.body.append(host);
  createApp(SessionTimeoutHost).mount(host);
}

export {sessionTimeoutManager, SessionTimeoutHost};
