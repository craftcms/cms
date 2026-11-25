import './globals';
import {createApp} from 'vue';
import ElevatedSessionHost from './ElevatedSessionHost.vue';
import {ElevatedSessionForm} from './elevated-session-form';
import CraftElevatedSessionForm from './elevated-session-form.ce';
import {defineElement} from '@/common/web-components';
import {
  elevatedSessionManager,
  ElevatedSessionManager,
  type ElevatedSessionOptions,
} from './manager';

// Register the declarative custom element, so Twig can emit
// `<craft-elevated-session-form>` instead of the manual global constructor.
defineElement('craft-elevated-session-form', CraftElevatedSessionForm);

export function mountElevatedSessionHost(): void {
  if (document.querySelector('[data-elevated-session-host]')) {
    return;
  }

  const host = document.createElement('div');
  host.dataset.elevatedSessionHost = '';
  document.body.append(host);
  createApp(ElevatedSessionHost).mount(host);
}

/** Answers `@craftcms/ui` actions that ask for an elevated session. */
export function answerElevatedSessionRequests(): void {
  window.addEventListener('craft:require-elevated-session', (event) => {
    const {detail} = event as CustomEvent<{confirmed?: Promise<boolean>}>;
    detail.confirmed = elevatedSessionManager.require();
  });
}

export function usePasswordConfirmation() {
  return {
    require: (options?: ElevatedSessionOptions) =>
      elevatedSessionManager.require(options),
    run: <T>(
      callback: () => T | Promise<T>,
      options?: ElevatedSessionOptions
    ) => elevatedSessionManager.run(callback, options),
  };
}

export {
  ElevatedSessionForm,
  CraftElevatedSessionForm,
  ElevatedSessionHost,
  elevatedSessionManager,
  ElevatedSessionManager,
};
export type {ElevatedSessionOptions};
