import './auth.scss';
import {
  AuthMethodSetup,
  type AuthMethodSetupSettings,
} from './auth-method-setup';
import {
  AuthMethodSetupSlideout,
  type AuthMethodSetupSlideoutData,
} from './auth-method-setup-slideout';
import {RecoveryCodesSetup} from './recovery-codes-setup';

/**
 * No custom element here: the auth-method listing is booted from `{% js %}`
 * template code (`new Craft.AuthMethodSetup({onRefresh})`), whose callback
 * settings can't be expressed as element attributes. This file's job is the
 * `Craft.*` globals and the module stylesheet.
 */

const craft = (window as any).Craft ?? ((window as any).Craft = {});

// Plain modern classes — nothing subclasses these via the legacy `.extend()`
// API. The slideout subclass rides along as `Craft.AuthMethodSetup.Slideout`
// (a static on the class).
craft.AuthMethodSetup = AuthMethodSetup;
craft.RecoveryCodesSetup = RecoveryCodesSetup;

export {
  AuthMethodSetup,
  type AuthMethodSetupSettings,
  AuthMethodSetupSlideout,
  type AuthMethodSetupSlideoutData,
  RecoveryCodesSetup,
};
