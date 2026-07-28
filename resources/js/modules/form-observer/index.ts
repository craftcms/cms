import {FormObserver} from './form-observer';

// Assign the legacy `Craft` global: `new Craft.FormObserver(...)` is still
// called from the cp bundle by `Craft.ElementEditor` and
// `Craft.ContentBlockEditor` for draft autosave. Plain modern ES class —
// nothing subclasses it via legacy `.extend()`.
const craft = (window as any).Craft ?? ((window as any).Craft = {});
craft.FormObserver = FormObserver;

export {FormObserver};
