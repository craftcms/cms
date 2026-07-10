import {createApp} from 'vue';
import ElevatedSessionHost from './ElevatedSessionHost.vue';
import {ElevatedSessionForm} from './elevated-session-form';
import {
  elevatedSessionManager,
  ElevatedSessionManager,
  type ElevatedSessionOptions,
} from './manager';

class LegacyElevatedSessionManager {
  static minSafeElevatedSessionTimeout = 5;

  get fetchingTimeout(): boolean {
    return elevatedSessionManager.state.checking;
  }

  async requireElevatedSession(
    onSuccess: () => void,
    onCancel?: () => void,
    minimumRemainingSeconds = LegacyElevatedSessionManager.minSafeElevatedSessionTimeout
  ): Promise<void> {
    const confirmed = await elevatedSessionManager.require({
      minimumRemainingSeconds,
    });

    if (confirmed) {
      onSuccess();
    } else {
      onCancel?.();
    }
  }

  showLoginModal(): void {
    void elevatedSessionManager.require({force: true});
  }
}

const craft = (window as any).Craft ?? ((window as any).Craft = {});
craft.ElevatedSessionManager = LegacyElevatedSessionManager;
craft.elevatedSessionManager = new LegacyElevatedSessionManager();
craft.ElevatedSessionForm = ElevatedSessionForm;

export function mountElevatedSessionHost(): void {
  if (document.querySelector('[data-elevated-session-host]')) {
    return;
  }

  const host = document.createElement('div');
  host.dataset.elevatedSessionHost = '';
  document.body.append(host);
  createApp(ElevatedSessionHost).mount(host);
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
  ElevatedSessionHost,
  elevatedSessionManager,
  ElevatedSessionManager,
};
export type {ElevatedSessionOptions};
