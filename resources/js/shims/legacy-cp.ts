// Tailwind Reset (probably not necessary)
import '../../legacy/tailwindreset/dist/tailwind_reset';

// Animation blocker
import '../../legacy/animationblocker/dist/AnimationBlocker';

// Axios (should be removed)
import '../../legacy/axios/dist/axios';

// D3 (hopefully remove)
// import '../../legacy/d3/dist/'

// Velocity
// import '../../legacy/velocity/dist/velocity';

// XRegex
// import '../../legacy/xregexp/dist/xregexp-all.js';

// IFrame resizer
import '../../legacy/iframeresizer/dist/iframeResizer';

// Fabric
// import '../../legacy/fabric/dist/fabric';

// JQuery (+ Garnish and plugins)
import './legacy-jquery.js';

// CP — DON'T import the webpack-built legacy CP bundle through Vite:
//   1. It's already loaded as a classic <script> by CpAsset on every CP
//      request (via the legacy asset pipeline), so importing it here would
//      double-execute.
//   2. Rolldown trips on for-of `const` bindings in that file
//      (ILLEGAL_REASSIGNMENT) — likely a false positive, but still a hard
//      build failure.
// import '../../legacy/cp/dist/cp';
