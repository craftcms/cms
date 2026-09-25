// `<craft-icon>` fetches its SVG from `/vendor/craft/icons`, which happy-dom
// resolves against its default `http://localhost:3000`. Nothing serves that in
// a test run, so every icon rendered anywhere became a failed request — logged
// as a 404 or an unhandled ECONNREFUSED, and never a result any test reads.
// Resolving to no icon keeps the requests off the network; `icon.test.ts`
// restores the real resolver to test it against a stubbed `fetch`.
import {nothing} from 'lit';
import {setIconResolver} from '../src/utilities/icons.js';

setIconResolver(() => nothing);
