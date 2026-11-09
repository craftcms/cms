import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import {computeAccessibleName} from 'dom-accessibility-api';
import {Slideout} from './slideout';

/** Contents like the authenticator app setup's: a link comes before the field. */
const setupContents = `
  <div class="slideout__body">
    <h1>Authenticator App Setup</h1>
    <h2>Step 1: Install an authenticator app.</h2>
    <a href="#duo">Duo Mobile</a>
    <label>Setup Key <input readonly value="ABC123"></label>
    <label>Verification Code <input></label>
  </div>`;

function focusedName(): string {
  return document.activeElement
    ? computeAccessibleName(document.activeElement)
    : '';
}

let slideout: Slideout | null = null;

beforeEach(() => {
  // The CP's config, which every control panel page sets
  vi.stubGlobal('Craft', {slideoutPosition: 'end'});
  // happy-dom doesn't implement it. Hidden, or inside something hidden,
  // stands in for not visible.
  const proto: object = HTMLElement.prototype;
  if (!('checkVisibility' in proto)) {
    Object.defineProperty(proto, 'checkVisibility', {
      configurable: true,
      value(this: HTMLElement) {
        return !this.closest('[hidden]');
      },
    });
  }
});

afterEach(() => {
  slideout?.close();
  slideout?.destroy();
  slideout = null;
  vi.unstubAllGlobals();
  document.body.innerHTML = '';
});

it('is named by its heading', () => {
  slideout = new Slideout(setupContents);

  const dialog = document.querySelector('[role="dialog"]')!;
  expect(computeAccessibleName(dialog)).toBe('Authenticator App Setup');
});

it('focuses its heading when it opens, with "Disable autofocus" on', async () => {
  slideout = new Slideout(setupContents);

  await vi.waitFor(() => expect(document.activeElement?.tagName).toBe('H1'));
  expect(focusedName()).toBe('Authenticator App Setup');
});

it('focuses its first field when it opens, with autofocus preferred', async () => {
  vi.stubGlobal('Craft', {slideoutPosition: 'end', autofocusPreferred: true});

  slideout = new Slideout(setupContents);

  // After the opening transition, and then a frame for the field.
  await vi.waitFor(() => expect(focusedName()).toBe('Verification Code'));
});
