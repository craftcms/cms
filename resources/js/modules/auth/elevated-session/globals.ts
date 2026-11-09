import {ElevatedSessionForm} from './elevated-session-form';
import {elevatedSessionManager} from './manager';
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
