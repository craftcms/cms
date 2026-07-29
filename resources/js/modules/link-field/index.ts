import {LinkField} from './link-field';
import {LinkInput} from './link-input';
import {registerCraftGlobals} from '@/common/craft-global';

// Assign the legacy `Craft` globals: `new Craft.LinkField(...)` is emitted by
// `Link.php` and `new Craft.LinkInput(...)` by `BaseTextLinkType.php`. Plain
// modern ES classes — nothing subclasses them via the legacy `.extend()` API.
registerCraftGlobals({LinkField, LinkInput});

export {LinkField, LinkInput};
