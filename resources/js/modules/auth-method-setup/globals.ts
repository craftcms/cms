import {AuthMethodSetup} from './auth-method-setup';
import {registerCraftGlobals} from '@/common/craft-global';

// Plain modern class — nothing subclasses it via the legacy `.extend()` API.
// The slideout subclass rides along as `Craft.AuthMethodSetup.Slideout` (a
// static on the class).
registerCraftGlobals({AuthMethodSetup});
