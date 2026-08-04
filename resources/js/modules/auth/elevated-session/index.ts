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
import {registerCraftGlobals} from '@/common/craft-global';

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

// Assign onto the legacy `Craft` global so the Twig/PHP-emitted
// `new Craft.ElevatedSessionForm(...)` (EmailField, _password/_team/_permissions
// screens) and `Craft.elevatedSessionManager` keep working. The manager global is
// a thin `LegacyElevatedSessionManager` shim over the modern reactive manager.
registerCraftGlobals({
  ElevatedSessionManager: LegacyElevatedSessionManager,
  elevatedSessionManager: new LegacyElevatedSessionManager(),
  ElevatedSessionForm,
});

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
