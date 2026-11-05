import './auth.scss';
import './globals';
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
 * settings can't be expressed as element attributes, so the class is reached
 * through the `Craft.AuthMethodSetup` global.
 */

export {
  AuthMethodSetup,
  type AuthMethodSetupSettings,
  AuthMethodSetupSlideout,
  type AuthMethodSetupSlideoutData,
  RecoveryCodesSetup,
};
