// TEMPORARY: loads jQuery + jQuery UI (and any other legacy globals) for
// server-rendered HTML on Inertia pages that still contains inline jQuery code.
//
// To disable: comment out the import of this file in resources/js/cp.ts.
// To remove for good: delete resources/js/shims/ and that import line.
//
// Note on ordering: ES module imports are hoisted and fully evaluated before
// any module-body statement, so the `window.jQuery = $` assignment lives in
// its own module (./jquery) that is imported first. That guarantees the global
// is set before any plugin below evaluates — important for plugins that read
// the global jQuery at load time.
import './jquery';

import '../../legacy/jquerytouchevents/dist/jquery.mobile-events.js';
import '../../legacy/jqueryui/dist/jquery-ui.js';
import '../../legacy/jquerypayment/dist/jquery.payment.js';
import '../../legacy/fileupload/dist/jquery.fileupload.js';
import '../../legacy/timepicker/dist/jquery.timepicker.js';

// jQuery UI base theme. Leave commented unless the server HTML actually needs
// jQuery UI's own widget styling — Craft's CP styles many of these already, and
// the base theme can conflict.
// import 'jquery-ui/dist/themes/base/jquery-ui.css';

// Add additional legacy jQuery plugins below as needed, e.g.:
// import 'some-jquery-plugin';

import '../../legacy/garnish/dist/garnish.js';

// Selectize (deprecated)
import '../../legacy/selectize/dist/selectize.js';
import '../../legacy/selectize/dist/css/selectize.css';
