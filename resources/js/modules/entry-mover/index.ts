import {EntryMover} from './entry-mover';
import {registerCraftGlobals} from '@/common/craft-global';

// Assign the legacy `Craft` global: `new Craft.EntryMover(...)` is emitted by
// `MoveToSection.php`. Plain modern ES class — nothing subclasses it via the
// legacy `.extend()` API.
registerCraftGlobals({EntryMover});

export {EntryMover};
