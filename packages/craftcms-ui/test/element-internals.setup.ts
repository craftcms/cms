// happy-dom ships no `ElementInternals` — not in any release — so a
// form-associated custom element cannot even be constructed there. This
// polyfill supplies the platform feature so the form-control components can be
// unit-tested; browsers have shipped the real thing since March 2023.
import 'element-internals-polyfill';
